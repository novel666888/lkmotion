<?php
/**
 * ValuationLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;


use common\models\CarLevel;
use common\models\ChargeRule;
use common\models\ChargeRuleDetail;
use common\models\ServiceType;
use common\services\CConstant;
use yii\base\Exception;
use yii\helpers\ArrayHelper;


class ValuationLogic
{

    const I18N_CATEGORY = 'valuation';

    use LogicTrait;

    /**
     * lists --
     * @author JerryZhang
     * @param $params
     * @param $pager
     * @return array|\yii\db\ActiveRecord[]
     * @cache No
     */
    public static function lists($params, $pager)
    {
        $list = ChargeRule::lists($params, $pager);
        $rule_ids = ArrayHelper::getColumn($list, 'id');
        if (!empty($rule_ids)) {
            $ids = ChargeRuleDetail::getIdsByRuleId($rule_ids);
            $detail_info = ChargeRuleDetail::showBatch($ids);
            $detail_info = ArrayHelper::index($detail_info, null, 'rule_id');
            foreach ($list as &$v) {
                $v['period_rule'] = isset($detail_info[$v['id']]) ? $detail_info[$v['id']] : [];
            }
        }

        return $list;
    }

    /**
     * add --
     * @author JerryZhang
     * @param array $data
     * @param array $data_rule
     * @return bool
     * @cache Yes
     */
    public static function add($data, $data_rule)
    {
        $rule_id = ChargeRule::add($data);
        if ($rule_id && $data['base_kilo'] == 0 && $data['base_minutes'] == 0 && !empty($data_rule)) {
            foreach ($data_rule as $v) {
                $v['rule_id'] = $rule_id;
                ChargeRuleDetail::add($v);
            }
        }

        if ($rule_id) {
            self::refreshActiveStatus($data['city_code'], $data['service_type_id'], $data['channel_id'], $data['car_level_id']);
        }

        return $rule_id;
    }

    /**
     * edit --
     * @author JerryZhang
     * @param $id
     * @param $data
     * @param $data_rule
     * @return bool
     * @cache Yes
     */
    public static function edit($id, $data, $data_rule)
    {
        $res = ChargeRule::edit($id, $data);
        if ($res) {
            ChargeRuleDetail::removeByRuleId($id);
            if (!empty($data_rule)) {
                foreach ($data_rule as $v) {
                    $v['rule_id'] = $id;
                    ChargeRuleDetail::add($v);
                }
            }
            if (isset($data['city_code']) && isset($data['service_type_id']) && isset($data['channel_id']) && isset($data['car_level_id'])) {
                self::refreshActiveStatus($data['city_code'], $data['service_type_id'], $data['channel_id'], $data['car_level_id']);
            }
        }

        return $res;
    }

    /**
     * refreshActiveStatus --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $channel_id
     * @param $car_level_id
     * @cache No
     * @throws Exception
     */
    public static function refreshActiveStatus($city_code, $service_type_id, $channel_id, $car_level_id)
    {
        $params['city_code'] = $city_code;
        $params['service_type_id'] = $service_type_id;
        $params['channel_id'] = $channel_id;
        $params['car_level_id'] = $car_level_id;
        $params['active_status'] = ChargeRule::ACTIVE_STATUS_VALID;
        $params['is_unuse'] = ChargeRule::IS_UNUSE_NO;
        $data = ChargeRule::lists($params);

        $valid_id = 0;
        $invalid_ids = [];
        $new_time = 0;
        $now_time = time();

        foreach ($data as $v) {
            $effective_time = strtotime($v['effective_time']);
            if ($effective_time <= $now_time) {
                if ($effective_time > $new_time) {
                    $new_time = $effective_time;
                    $valid_id && $invalid_ids[$valid_id] = $valid_id;
                    $valid_id = $v['id'];
                } else {
                    $invalid_ids[$v['id']] = $v['id'];
                }
            }
        }

        if (!empty($invalid_ids)) {
            foreach ($invalid_ids as $id) {
                $res = ChargeRule::edit($id, ['active_status' => ChargeRule::ACTIVE_STATUS_INVALID]);
                if (!$res) {
                    throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.active_status.refresh_fail'), 1);
                }
            }
        }
    }

    /**
     * main_lists --
     * @author JerryZhang
     * @param $params
     * @param array $pager
     * @return array|\yii\db\ActiveRecord[]
     * @cache No
     */
    public static function main_lists($params, $pager = [])
    {
        $query = self::main_get_query($params);

        if (!empty($pager['page']) && !empty($pager['page_size'])) {
            $query->limit($pager['page_size']);
            $query->offset(($pager['page'] - 1) * $pager['page_size']);
        }

        $list = $query->asArray()->all();

        return $list;
    }

    /**
     * main_get_total_count --
     * @author JerryZhang
     * @param $params
     * @return int|string
     * @cache No
     */
    public static function main_get_total_count($params)
    {
        $query = self::main_get_query($params);

        return intval($query->count());
    }

    /**
     * main_get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function main_get_query($params)
    {

        $query = ChargeRule::find();

        $query->select('city_code,service_type_id,channel_id,car_level_id,max(update_time) as update_time, is_unuse, operator_id');


        if (isset($params['is_unuse']) && $params['is_unuse'] > -1) {
            $query->andWhere(['is_unuse' => $params['is_unuse']]);
        }
        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        if (!empty($params['service_type_id'])) {
            $query->andWhere(['service_type_id' => $params['service_type_id']]);
        }
        if (!empty($params['channel_id'])) {
            $query->andWhere(['channel_id' => $params['channel_id']]);
        }
        if (!empty($params['car_level_id'])) {
            $query->andWhere(['car_level_id' => $params['car_level_id']]);
        }

        $query->orderBy('`city_code` + 0 ASC');
        $query->groupBy('city_code,service_type_id,channel_id,car_level_id');

        return $query;
    }

    /**
     * switchRule --
     * @author JerryZhang
     * @param $is_unuse
     * @param $city_code
     * @param $service_type_id
     * @param $channel_id
     * @param $car_level_id
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function switchRule($is_unuse, $city_code, $service_type_id, $channel_id, $car_level_id, $operator_id)
    {
        $params['city_code'] = $city_code;
        $params['service_type_id'] = $service_type_id;
        $params['channel_id'] = $channel_id;
        $params['car_level_id'] = $car_level_id;
        $data = ChargeRule::lists($params);

        foreach ($data as $v) {
            if (isset($v)) {
                $res = ChargeRule::edit($v['id'], ['is_unuse' => $is_unuse, 'operator_id'=>$operator_id]);
                if (!$res) {
                    throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.operation.fail'), 1);
                }
            }
        }

        self::refreshActiveStatus($city_code, $service_type_id, $channel_id, $car_level_id);

        return true;
    }

    /**
     * switchRuleByCityCode --根据城市编码开关城市计价规则
     * @author JerryZhang
     * @param $is_unuse
     * @param $city_code
     * @return bool
     * @cache No
     */
    public static function switchRuleByCityCode($is_unuse, $city_code,$operator_id)
    {
        return self::switchRule($is_unuse, $city_code, 0, 0, 0,$operator_id);
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $car_level_id
     * @param $channel_id
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function checkRepeat($city_code, $service_type_id, $car_level_id, $channel_id){
        $data = ChargeRule::getCheckData($city_code, $service_type_id, $car_level_id, $channel_id);

        if(!empty($data)){
            throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.exist'), 1);
        }

        return true;
    }


    /**
     * checkConflict --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $car_level_id
     * @param $channel_id
     * @param $effective_time
     * @param $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function checkConflict($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $id = 0){
        $data = ChargeRule::getCheckData($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $id);

        if(!empty($data)){
            throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.conflict'), 1);
        }

        return true;
    }

    public static function getNowRuleInfo($city_code,$service_type_id,$car_level_id, $channel_id){
        $params['city_code'] = $city_code;
        $params['service_type_id'] = $service_type_id;
        $params['car_level_id'] = $car_level_id;
        $params['channel_id'] = $channel_id;
        $params['effective_time'] = date('Y-m-d H:i:s');
        $params['is_unuse'] = ChargeRule::IS_UNUSE_NO;
        $params['active_status'] = ChargeRule::ACTIVE_STATUS_VALID;
        $pager['page'] = 1;
        $pager['page_size'] = 1;
        $list = ChargeRule::lists($params, $pager);
        $data = array_shift($list);
        if (empty($data)) {
            throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.get_fail'), 1);
        }

        $data['period_rule'] = self::getDetailByRuleId($data['id']);

        return $data;
    }

    public static function getDetailByRuleId($rule_id){

        $data = [];
        $ids = ChargeRuleDetail::getIdsByRuleId($rule_id);
        if(!empty($ids)){
            $data = ChargeRuleDetail::showBatch($ids);
        }

        return $data;
    }

    public static function getAllNowRuleInfoByCityCode($city_code, $channel_id = CConstant::CHANNEL_CODE_SELF, $check_city = true){

        $service_type_ids = ServiceLogic::serviceTypeStatus($city_code, $check_city);
        if(empty($service_type_ids)){
            return [];
        }

        $params['city_code'] = $city_code;
        $params['channel_id'] = $channel_id;
        $params['service_type_id'] = $service_type_ids;
        $params['effective_time'] = date('Y-m-d H:i:s');
        $params['is_unuse'] = ChargeRule::IS_UNUSE_NO;
        $params['active_status'] = ChargeRule::ACTIVE_STATUS_VALID;
        $list = ChargeRule::lists($params);
        $service_type_ids = ArrayHelper::getColumn($list, 'service_type_id');
        $service_type_info = ServiceType::showBatch($service_type_ids);
        $car_level_ids = ArrayHelper::getColumn($list, 'car_level_id');
        $car_level_info = CarLevel::showBatch($car_level_ids);//var_dump($car_level_info);die;

        $tag_rule_info = TagRuleLogic::getFillTagRuleInfo($city_code, $service_type_ids);

        foreach ($list as &$v){
            $v['service_type_name'] = isset($service_type_info[$v['service_type_id']])? $service_type_info[$v['service_type_id']]['service_type_name']:'未知';
            $v['car_level_name'] = isset($car_level_info[$v['car_level_id']])? $car_level_info[$v['car_level_id']]['label']:'未知';
            $v['car_level_icon'] = isset($car_level_info[$v['car_level_id']])? $car_level_info[$v['car_level_id']]['icon']:'';
            $v['period_rule'] = array_values(self::getDetailByRuleId($v['id']));
            $v['tag_info'] = isset($tag_rule_info[$v['service_type_id']]) ? $tag_rule_info[$v['service_type_id']] : [];
        }

        return $list;
    }

    /**
     * checkSettingByCityCode --根据城市编码校验计价规则配置信息
     * @author JerryZhang
     * @param $city_code
     * @return array
     * @cache No
     */
    public static function checkSettingByCityCode($city_code){
        $result = [
            'code' => 0,
            'error_message' => [],
            'detail' => [],
        ];
        try{
            $now_rules = self::getAllNowRuleInfoByCityCode($city_code, CConstant::CHANNEL_CODE_SELF, false);
            if(empty($now_rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.not_setting'), 100001);
            }
            $result['detail'] = $now_rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        return $result;
    }

}