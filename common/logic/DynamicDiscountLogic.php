<?php
/**
 * DynamicDiscountLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;


use common\models\CarLevel;
use common\models\DynamicDiscountCity;
use common\models\DynamicDiscountInfo;
use common\models\DynamicDiscountRule;
use common\models\ServiceType;
use yii\base\Exception;
use yii\helpers\ArrayHelper;


class DynamicDiscountLogic
{

    const CACHE_PREFIX = 'dynamic_discount';
    const I18N_CATEGORY = 'dynamic_discount';

    use LogicTrait;

    /**
     * showBatch --
     * @author JerryZhang
     * @param $id
     * @return array
     * @cache Yes
     */
    public static function showBatch($id)
    {
        if (empty($id)) {
            return [];
        }

        $data_rule = DynamicDiscountRule::showBatch($id);
        $data_info = DynamicDiscountInfo::showBatchByRuleId($id);
        $data_city = DynamicDiscountCity::showBatchByRuleId($id);

        foreach ($data_rule as &$v) {
            $v['city'] = $data_city[$v['id']];
            $v['info'] = $data_info[$v['id']];
        }

        return $data_rule;
    }

    /**
     * add --
     * @author JerryZhang
     * @param $data_rule
     * @param $data_info
     * @param $data_city
     * @return bool
     * @cache Yes
     */
    public static function add($data_rule, $data_info, $data_city)
    {
        $rule_id = DynamicDiscountRule::add($data_rule);
        if ($rule_id) {
            foreach ($data_info as $v) {
                $v['dynamic_discount_rule_id'] = $rule_id;
                DynamicDiscountInfo::add($v);
            }
            foreach ($data_city as $v) {
                $data['city_code'] = $v;
                $data['dynamic_discount_rule_id'] = $rule_id;
                DynamicDiscountCity::add($data);
            }
        }

        return $rule_id;
    }

    /**
     * edit --
     * @author JerryZhang
     * @param $id
     * @param $data_rule
     * @param $data_info
     * @param $data_city
     * @return bool
     * @cache Yes
     */
    public static function edit($id, $data_rule, $data_info, $data_city)
    {
        $res = DynamicDiscountRule::edit($id, $data_rule);
        if ($res) {
            if (!empty($data_info)) {
                DynamicDiscountInfo::removeByRuleId($id);
                DynamicDiscountInfo::add($data_info);
            }
            if (!empty($data_city)) {
                DynamicDiscountCity::removeByRuleId($id);
                DynamicDiscountCity::add($data_city);
            }
        }

        return $res;
    }

    /**
     * lists --
     * @author JerryZhang
     * @param $params
     * @param array $pager
     * @return array|\yii\db\ActiveRecord[]
     * @cache Yes
     */
    public static function lists($params, $pager = [])
    {

        if (!empty($params['city_code'])) {
            $rule_ids = DynamicDiscountCity::getRuleIdByCityCode($params['city_code']);
            if (empty($rule_ids)) {
                return [];
            }
            $params['id'] = $rule_ids;
        }

        $query = self::get_query($params);
        if (!empty($pager['page']) && !empty($pager['page_size'])) {
            $query->limit($pager['page_size']);
            $query->offset(($pager['page'] - 1) * $pager['page_size']);
        }

        $query->select('id');
        $ids = $query->asArray()->column();

        $list = self::showBatch($ids);

        return $list;
    }

    /**
     * get_total_count --
     * @author JerryZhang
     * @param $params
     * @return int|string
     * @cache No
     */
    public static function get_total_count($params)
    {

        if (!empty($params['city_code'])) {
            $rule_ids = DynamicDiscountCity::getRuleIdByCityCode($params['city_code']);
            if (empty($rule_ids)) {
                return 0;
            }
            $params['id'] = $rule_ids;
        }

        $query = self::get_query($params);

        return $query->count();
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = DynamicDiscountRule::find();

        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }
        if ($params['is_unuse'] > -1) {
            $query->andWhere(['is_unuse' => $params['is_unuse']]);
        }
        if (!empty($params['service_type'])) {
            $query->andWhere('find_in_set(' . $params['service_type'] . ', service_type)');
        }
        if (!empty($params['car_level'])) {
            $query->andWhere('find_in_set(' . $params['car_level'] . ', car_level)');
        }
        if (!empty($params['now_status'])) {
            $now = date('Y-m-d H:i:s');
            if ($params['now_status'] == 1) {
                $query->andWhere(['>', 'valid_start_time', $now]);
            }
            if ($params['now_status'] == 2) {
                $query->andWhere(['<=', 'valid_start_time', $now]);
                $query->andWhere(['>=', 'valid_end_time', $now]);
            }
            if ($params['now_status'] == 3) {
                $query->andWhere(['<', 'valid_end_time', $now]);
            }
        }

        $query->orderBy(['id' => SORT_DESC]);

        return $query;
    }

    public static function fillData(&$data)
    {
        if (empty($data)) {
            return;
        }

        $service_type_arr = ArrayHelper::getColumn($data, 'service_type');
        $service_type_ids = array_unique(array_reduce($service_type_arr, 'array_merge', []));
        $service_type_info = ServiceType::showBatch($service_type_ids);
        $car_level_arr = ArrayHelper::getColumn($data, 'car_level');
        $car_level_ids = array_unique(array_reduce($car_level_arr, 'array_merge', []));
        $car_level_info = CarLevel::showBatch($car_level_ids);

        foreach ($data as &$v) {
            if (!empty($v['service_type'])) {
                $temp = [];
                foreach ($v['service_type'] as $v1) {
                    $temp[$v1] = !empty($service_type_info[$v1]) ? $service_type_info[$v1]['service_type_name'] : '未知';
                }
                $v['service_type'] = $temp;
            }
            if (!empty($v['car_level'])) {
                $temp = [];
                foreach ($v['car_level'] as $v1) {
                    $temp[$v1] = !empty($car_level_info[$v1]) ? $car_level_info[$v1]['label'] : '未知';
                }
                $v['car_level'] = $temp;
            }
        }

    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city
     * @param $service_type
     * @param $car_level
     * @param $type_select
     * @param $type_value
     * @param $valid_start_time
     * @param $valid_end_time
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function checkRepeat($city, $service_type, $car_level, $type_select, $type_value, $valid_start_time, $valid_end_time, $id = false)
    {
        //获取城市重复的规则id
        $rule_ids = DynamicDiscountCity::getRuleIdByCityCode($city);
        if ($id && in_array($id, $rule_ids)) {
            $rule_ids = array_diff($rule_ids, [$id]);
        }
        if (empty($rule_ids)) {
            return true;
        }

        //获取重复城市中日期设置类型重复的数据
        $data = DynamicDiscountRule::getCheckData($rule_ids, $type_select);
        if (empty($data)) {
            return true;
        }
        foreach ($data as $k => $v) {
            //检索重复城市、重复日期设置类型中日期重复的数据
            if ($type_select == DynamicDiscountRule::DATE_TYPE_WEEK_SET
                && ($v['valid_start_time'] > $valid_end_time || $v['valid_end_time'] < $valid_start_time)
            ) {
                unset($data[$k]);
                continue;
            }
            if ($type_select == DynamicDiscountRule::DATE_TYPE_SPECIAL_DATE_SET) {
                $v['special_date_set'] = explode(',', $v['special_date_set']);
                if (empty(array_intersect($v['special_date_set'], $type_value))) {
                    unset($data[$k]);
                    continue;
                }
            }

            //检索重复城市、重复日期设置类型、重复日期中服务类型、车辆级别重复的数据
            $v['service_type'] = explode(',', $v['service_type']);
            $v['car_level'] = explode(',', $v['car_level']);

            if (empty(array_intersect($v['service_type'], $service_type))
                || empty(array_intersect($v['car_level'], $car_level))
            ) {
                unset($data[$k]);
                continue;
            }
        }

        if (!empty($data)) {
            throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.conflict'), 1);
        }

        return true;
    }

}