<?php
/**
 * TagRuleLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;


use common\models\TagInfo;
use common\models\TagRuleInfo;
use common\util\Common;
use yii\base\Exception;
use yii\helpers\ArrayHelper;


class TagRuleLogic
{

    const I18N_CATEGORY = 'tag_rule';

    use LogicTrait;

    /**
     * lists --
     * @author JerryZhang
     * @param $params
     * @param $pager
     * @return array|\yii\db\ActiveRecord[]
     * @cache No
     */
    public static function lists($params, $pager = [])
    {
        $list = TagRuleInfo::lists($params, $pager);

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
        return TagRuleInfo::get_total_count($params);
    }

    /**
     * add --
     * @author JerryZhang
     * @param $data
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function add($data)
    {
        $rule_id = TagRuleInfo::add($data);

        return $rule_id;
    }

    /**
     * edit --
     * @author JerryZhang
     * @param $id
     * @param $data
     * @return bool
     * @cache Yes
     * @throws Exception
     */
    public static function edit($id, $data)
    {
        $res = TagRuleInfo::edit($id, $data);

        return $res;
    }

    /**
     * checkConflict --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $tag_id
     * @param $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function checkRepeat($city_code, $service_type_id, $tag_id, $id = 0)
    {
        $data = TagRuleInfo::checkData($city_code, $service_type_id, $tag_id, $id);

        if (!empty($data)) {
            throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.set.repeat'), 1);
        }

        return true;
    }

    /**
     * getTagRuleInfo --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @return array
     * @cache No
     */
    public static function getTagRuleInfo($city_code, $service_type_id)
    {
        $params['city_code'] = $city_code;
        $params['service_type_id'] = $service_type_id;
        $params['status'] = TagRuleInfo::STATUS_NORMAL;
        $list = self::lists($params);
        TagLogic::fillTagInfo($list);

        return array_values($list);
    }

    /**
     * getTagRuleInfoByCityCode --
     * @author JerryZhang
     * @param $city_code
     * @return array|\yii\db\ActiveRecord[]
     * @cache No
     */
    public static function getTagRuleInfoByCityCode($city_code)
    {
        $params['city_code'] = $city_code;
        $params['status'] = TagRuleInfo::STATUS_NORMAL;
        $list = self::lists($params);
        TagLogic::fillTagInfo($list);
        $list = Common::getCertainColumnFromTowDimensionalArray($list, ['service_type_id', 'tag_id', 'tag_name', 'tag_desc']);
        $list = ArrayHelper::index($list, null, ['service_type_id']);

        return $list;
    }

    /**
     * getFillTagRuleInfo --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @return array
     * @cache No
     */
    public static function getFillTagRuleInfo($city_code, $service_type_id)
    {
        $list = self::getTagRuleInfo($city_code, $service_type_id);
        $list = Common::getCertainColumnFromTowDimensionalArray($list, ['city_code', 'service_type_id', 'tag_id', 'tag_name', 'tag_price', 'tag_img', 'tag_desc']);
        $list = ArrayHelper::index($list, null, ['service_type_id']);
        return $list;
    }

    /**
     * getInfoByCityCode --
     * @author JerryZhang
     * @param $city_code
     * @return array|\yii\db\ActiveRecord[]
     * @cache No
     */
    public static function getInfoByCityCode($city_code)
    {
        $query = TagRuleInfo::find();
        $query->select(['tag_id']);
        $query->andWhere(['city_code' => $city_code, 'status' => TagRuleInfo::STATUS_NORMAL])->groupBy('tag_id');

        $list = $query->asArray()->all();
        TagLogic::fillTagInfo($list);
        return $list;
    }

    public static function getServiceTypeIdByCityCodeAndTagId($city_code, $tag_id){
        $query = TagRuleInfo::find();
        $query->select(['service_type_id']);
        $query->andWhere(['city_code' => $city_code, 'tag_id'=>$tag_id]);

        return $query->asArray()->column();
    }

}