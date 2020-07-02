<?php

namespace common\models;

use common\services\traits\ModelTrait;

/**
 * This is the model class for table "tbl_dynamic_discount_rule".
 *
 * @property int $id 自增主键
 * @property string $service_type 服务类型
 * @property string $car_level 车辆级别
 * @property string $time_set 时段设置
 * @property string $date_type 日期类型 1星期 2特殊日期
 * @property string $week_set 星期设置
 * @property string $special_date_set 特殊日期设置
 * @property string $discount_max_price 折扣封顶金额
 * @property string $valid_start_time 生效开始时间
 * @property string $valid_end_time 生效结束时间
 * @property int $is_unuse 是否不可用 0不可用 1可用
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class DynamicDiscountRule extends BaseModel
{

    const DATE_TYPE_WEEK_SET = 1;//日期类型-星期
    const DATE_TYPE_SPECIAL_DATE_SET = 2;//日期类型-特殊日期
    const IS_UNUSE_YES = 1;//是否不可用-可用
    const IS_UNUSE_NO = 0;//是否不可用-不可用

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_dynamic_discount_rule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['discount_max_price'], 'number'],
            [['valid_start_time', 'valid_end_time', 'create_time', 'update_time'], 'safe'],
            [['date_type', 'is_unuse'], 'integer'],
            [['service_type', 'car_level', 'time_set', 'week_set', 'special_date_set'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_type' => 'Service Type',
            'car_level' => 'Car Level',
            'time_set' => 'Time Set',
            'date_type' => 'Date Type',
            'week_set' => 'Week Set',
            'special_date_set' => 'Special Date Set',
            'discount_max_price' => 'Discount Max Price',
            'valid_start_time' => 'Valid Start Time',
            'valid_end_time' => 'Valid End Time',
            'is_unuse' => 'Is Unuse',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function getCheckData($rule_ids, $date_type){

        $query = self::find();
        $query->select('*');
        $query->andWhere(['is_unuse' => self::IS_UNUSE_NO, 'id' => $rule_ids, 'date_type' =>$date_type]);

        return $query->asArray()->all();

    }

}
