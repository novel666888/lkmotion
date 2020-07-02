<?php

namespace common\models;

use common\services\traits\ModelTrait;
use common\util\Cache;
use Yii;

/**
 * This is the model class for table "tbl_charge_rule_detail".
 *
 * @property int $id
 * @property int $rule_id
 * @property int $start 时间段开始
 * @property int $end 时间段结束
 * @property double $per_kilo_price 超公里单价
 * @property double $per_minute_price 超时间单价
 * @property int $is_delete 是否删除 0不删除 1删除
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class ChargeRuleDetail extends BaseModel
{

    const IS_DELETE_YES = 1;//是否删除-删除
    const IS_DELETE_NO = 0;//是否删除-不删除

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_charge_rule_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rule_id', 'start', 'end', 'per_kilo_price', 'per_minute_price'], 'required'],
            [['rule_id', 'start', 'end', 'is_delete'], 'integer'],
            [['per_kilo_price', 'per_minute_price'], 'number'],
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
            'rule_id' => 'Rule ID',
            'start' => 'Start',
            'end' => 'End',
            'per_kilo_price' => 'Per Kilo Price',
            'per_minute_price' => 'Per Minute Price',
            'is_delete' => 'Is Delete',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function getIdsByRuleId($rule_id)
    {
        $query = self::find();
        $query->select('id');
        $query->where(['rule_id' => $rule_id, 'is_delete' => self::IS_DELETE_NO]);
        return $query->asArray()->column();
    }

    public static function removeByRuleId($rule_id)
    {

        $ids = self::getIdsByRuleId($rule_id);

        $data['is_delete'] = self::IS_DELETE_YES;
        foreach ($ids as $v) {
            if (isset($v)) {
                self::edit($v, $data);
                Cache::delete(self::getTableSchema()->fullName, $v);
            }
        }

        return true;

    }

}
