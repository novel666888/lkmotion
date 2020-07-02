<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_order_rule_price_detail".
 *
 * @property string $id
 * @property int $order_id 订单id
 * @property string $category 价格类型：0预约，1实际
 * @property int $start_hour 时间段-开始
 * @property int $end_hour 时间段-结束
 * @property double $per_kilo_price 超公里单价(每公里单价)
 * @property double $per_minute_price 超时间单价(每分钟单价)
 * @property double $duration 该时间段的时间统计
 * @property double $time_price 该时间段的时间价格合计
 * @property double $distance 该时间段的距离统计
 * @property double $distance_price 该时间段的距离价格合计
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class OrderRulePriceDetail extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_order_rule_price_detail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'category', 'start_hour', 'end_hour', 'per_kilo_price', 'per_minute_price', 'duration', 'time_price', 'distance', 'distance_price'], 'required'],
            [['order_id', 'start_hour', 'end_hour'], 'integer'],
            [['per_kilo_price', 'per_minute_price', 'duration', 'time_price', 'distance', 'distance_price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['category'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'category' => 'Category',
            'start_hour' => 'Start Hour',
            'end_hour' => 'End Hour',
            'per_kilo_price' => 'Per Kilo Price',
            'per_minute_price' => 'Per Minute Price',
            'duration' => 'Duration',
            'time_price' => 'Time Price',
            'distance' => 'Distance',
            'distance_price' => 'Distance Price',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
