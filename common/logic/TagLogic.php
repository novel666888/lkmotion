<?php
/**
 * TagLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;


use common\models\TagInfo;
use common\models\TagRuleInfo;
use yii\base\Exception;
use yii\helpers\ArrayHelper;


class TagLogic
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
        $list = TagInfo::lists($params, $pager);

        return $list;
    }

    /**
     * get_total_count --
     * @author JerryZhang
     * @param $params
     * @return int|string
     * @cache No
     */
    public static function get_total_count($params){
        return TagInfo::get_total_count($params);
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
        $rule_id = TagInfo::add($data);

        return $rule_id;
    }

    /**
     * edit --
     * @author JerryZhang
     * @param $id
     * @param $data
     * @return bool
     * @cache Yes
     */
    public static function edit($id, $data)
    {
        $res = TagInfo::edit($id, $data);

        return $res;
    }

    /**
     * safeEdit --
     * @author JerryZhang
     * @param $id
     * @param $data
     * @return bool
     * @cache No
     * @throws Exception
     */
    public static function safeEdit($id, $data){
        $info_org = TagInfo::showBatch($id);
        $info_org = array_shift($info_org);

        if($data['status'] == TagInfo::STATUS_DENY && $info_org['status'] != $data['status']){
            $count = TagRuleInfo::find()->select('id')->where(['tag_id' => $id])->count();
            if($count){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.operate.is_using'), 100031);
            }
        }

        return self::edit($id, $data);
    }

    /**
     * fillTagInfo --
     * @author JerryZhang
     * @param $data
     * @cache No
     */
    public static function fillTagInfo(&$data){
        if (empty($data)) {
            return;
        }

        $tag_ids = array_unique(ArrayHelper::getColumn($data, 'tag_id'));
        $tag_info = TagInfo::showBatch($tag_ids);

        $static_uri = \Yii::$app->params['ossFileUrl'];
        foreach ($data as &$v) {
            $v['tag_name'] = !empty($tag_info[$v['tag_id']]) ? $tag_info[$v['tag_id']]['tag_name'] : '未知';
            $v['tag_img'] = !empty($tag_info[$v['tag_id']]) ? $static_uri . $tag_info[$v['tag_id']]['tag_img'] : '';
        }
    }

    public static function showBatch($tag_ids){
        return TagInfo::showBatch($tag_ids);
    }
}