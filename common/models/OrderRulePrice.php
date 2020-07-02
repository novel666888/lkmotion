<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%order_rule_price}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property string $category 订单类型：0预估订单，1实时订单
 * @property double $total_price 总价
 * @property double $total_distance 总距离（公里）
 * @property double $total_time 总时间（分钟）
 * @property string $city_code 城市编码
 * @property string $city_name 城市名称
 * @property int $service_type_id 服务类型id
 * @property string $service_type_name 服务类型名称
 * @property int $channel_id 渠道id
 * @property string $channel_name 渠道名称
 * @property int $car_level_id 车辆级别id
 * @property string $car_level_name 车辆级别名称
 * @property double $base_price 基础价格
 * @property double $base_kilo 基础价格包含公里数
 * @property double $base_minute 基础价格包含时长数（分钟）
 * @property double $lowest_price 最低消费
 * @property string $night_start 夜间时间段开始
 * @property string $night_end 夜间时间段结束
 * @property double $night_per_kilo_price 夜间超公里加收单价
 * @property double $night_per_minute_price 夜间超时间加收单价
 * @property double $night_price 夜间服务费
 * @property double $night_distance 夜间行驶总里程（KM）
 * @property double $night_time 夜间行驶总时间
 * @property double $beyond_start_kilo 远途起算公里
 * @property double $beyond_per_kilo_price 远途单价
 * @property double $beyond_distance 远途距离，超出远途的公里数
 * @property double $beyond_price 远途费
 * @property double $per_kilo_price 超公里单价(每公里单价)
 * @property double $path (不包含起步)行驶总里程（KM）
 * @property double $path_price (不包含起步)行驶总里程价格
 * @property double $per_minute_price (不包含起步)超时间单价(每分钟单价)
 * @property double $duration (不包含起步)行驶时长（分钟）
 * @property double $duration_price (不包含起步)行驶时长价格
 * @property double $rest_duration 其他时段时长合计
 * @property double $rest_duration_price 其他时段时长费用合计
 * @property double $rest_distance 其他时段距离合计
 * @property double $rest_distance_price 其他时段距离费用合计
 * @property double $road_price 过路费
 * @property double $parking_price 停车费
 * @property double $other_price 其它费用
 * @property double $cancel_price 取消费用
 * @property double $dynamic_discount_rate 动态调价率
 * @property double $dynamic_discount 动态调价金额
 * @property double $supplement_price 最低消费补足
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class OrderRulePrice extends BaseModel
{
    use ModelTrait;
    const PRICE_TYPE_FORECAST = 0;
    const PRICE_TYPE_ACTUAL = 1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_rule_price}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'category', 'total_price', 'total_distance', 'total_time', 'city_code', 'city_name', 'service_type_id', 'service_type_name', 'channel_id', 'channel_name', 'car_type_id', 'car_type_name', 'base_price', 'base_kilo', 'base_minutes', 'lowest_price', 'night_start', 'night_end', 'night_per_kilo_price', 'night_per_minute_price', 'night_price', 'night_distance', 'night_time', 'beyond_start_kilo', 'beyond_per_kilo_price', 'beyond_distance', 'beyond_price', 'per_kilo_price', 'path', 'path_price', 'per_minute_price', 'duration', 'duration_price'], 'required'],
            [['order_id', 'service_type_id', 'channel_id', 'car_type_id'], 'integer'],
            [['total_price', 'total_distance', 'total_time', 'base_price', 'base_kilo', 'base_minutes', 'lowest_price', 'night_per_kilo_price', 'night_per_minute_price', 'night_price', 'night_distance', 'night_time', 'beyond_start_kilo', 'beyond_per_kilo_price', 'beyond_distance', 'beyond_price', 'per_kilo_price', 'path', 'path_price', 'per_minute_price', 'duration', 'duration_price', 'road_price', 'parking_price', 'other_price', 'cancel_price','supplement_price'], 'number'],
            [['night_start', 'night_end', 'create_time', 'update_time'], 'safe'],
            [['category'], 'string', 'max' => 1],
            [['city_code'], 'string', 'max' => 32],
            [['city_name', 'service_type_name', 'channel_name', 'car_type_name'], 'string', 'max' => 64],
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
            'total_price' => 'Total Price',
            'total_distance' => 'Total Distance',
            'total_time' => 'Total Time',
            'city_code' => 'City Code',
            'city_name' => 'City Name',
            'service_type_id' => 'Service Type ID',
            'service_type_name' => 'Service Type Name',
            'channel_id' => 'Channel ID',
            'channel_name' => 'Channel Name',
            'car_type_id' => 'Car Type ID',
            'car_type_name' => 'Car Type Name',
            'base_price' => 'Base Price',
            'base_kilo' => 'Base Kilo',
            'base_minutes' => 'Base Minutes',
            'lowest_price' => 'Lowest Price',
            'night_start' => 'Night Start',
            'night_end' => 'Night End',
            'night_per_kilo_price' => 'Night Per Kilo Price',
            'night_per_minute_price' => 'Night Per Minute Price',
            'night_price' => 'Night Price',
            'night_distance' => 'Night Distance',
            'night_time' => 'Night Time',
            'beyond_start_kilo' => 'Beyond Start Kilo',
            'beyond_per_kilo_price' => 'Beyond Per Kilo Price',
            'beyond_distance' => 'Beyond Distance',
            'beyond_price' => 'Beyond Price',
            'per_kilo_price' => 'Per Kilo Price',
            'path' => 'Path',
            'path_price' => 'Path Price',
            'per_minute_price' => 'Per Minute Price',
            'duration' => 'Duration',
            'duration_price' => 'Duration Price',
            'road_price' => 'Road Price',
            'parking_price' => 'Parking Price',
            'other_price' => 'Other Price',
            'cancel_price' => 'Cancel Price',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['id' => 'order_id']);
    }
}
