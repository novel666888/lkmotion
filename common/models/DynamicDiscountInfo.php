<?php

namespace common\models;

use common\services\traits\ModelTrait;
use common\util\Cache;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "tbl_dynamic_discount_info".
 *
 * @property int $id 自增主键
 * @property int $dynamic_discount_rule_id 动态折扣关联id
 * @property string $km 里程
 * @property string $discount 折扣
 * @property int $is_delete 是否删除 0不删除 1删除
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class DynamicDiscountInfo extends BaseModel
{

    const IS_DELETE_YES = 1;//是否删除-删除
    const IS_DELETE_NO = 0;//是否删除-不删除

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_dynamic_discount_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['dynamic_discount_rule_id', 'is_delete'], 'integer'],
            [['km', 'discount'], 'required'],
            [['km', 'discount'], 'number'],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'dynamic_discount_rule_id' => 'Dynamic Discount Rule ID',
            'km' => 'Km',
            'discount' => 'Discount',
            'is_delete' => 'Is Delete',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * showBatchByRuleId --
     * @author JerryZhang
     * @param $rule_id
     * @return array
     * @cache No
     */
    public static function showBatchByRuleId($rule_id)
    {
        $query = self::find();
        $query->select('*');
        $query->andWhere(['dynamic_discount_rule_id' => $rule_id]);
        $data_db = $query->asArray()->all();

        return ArrayHelper::index($data_db, null, 'dynamic_discount_rule_id');
    }

    public static function getIdsByRuleId($rule_id)
    {
        $query = self::find();
        $query->select('id');
        $query->where(['dynamic_discount_rule_id' => $rule_id]);
        return $query->asArray()->column();
    }

    public static function removeByRuleId($rule_id)
    {

        $ids = self::getIdsByRuleId($rule_id);

        $data['is_delete'] = self::IS_DELETE_YES;
        foreach ($ids as $v) {
            if (isset($v)) {
                self::edit($v, $data);
                Cache::delete(self::tableName(), $v);
            }
        }

        return true;

    }

}
