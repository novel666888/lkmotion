<?php

namespace common\models;

/**
 * This is the model class for table "tbl_order".
 *
 * @property int $id
 * @property string $order_number 订单号
 * @property int $passenger_info_id 乘客id
 * @property string $passenger_phone 乘客电话
 * @property string $device_code 乘客设备号
 * @property int $driver_id 司机id
 * @property int $driver_status 司机状态
 * 0：没有司机接单
 * 1：司机接单
 * 2.  去接乘客
 * 3：司机到达上车点
 * 4：开始行程
 * 5：结束行程
 * 6：发起收款
 * 7：取消
 * @property string $driver_phone 司机电话
 * @property int $car_id 车辆id
 * @property string $plate_number 车牌号
 * @property string $user_longitude 用户位置经度
 * @property string $user_latitude 用户位置纬度
 * @property string $start_longitude 乘客下单起点经度
 * @property string $start_latitude 乘客下单起点纬度
 * @property string $start_address 起点名称
 * @property string $end_address 终点地址名称
 * @property string $start_time 乘客下单时间
 * @property string $order_start_time 订单开始时间
 * @property string $end_longitude 乘客下单终点经度
 * @property string $end_latitude 乘客下单终点纬度
 * @property string $driver_grab_time 司机抢单时间
 * @property string $driver_start_time 司机去接乘客出发时间
 * @property string $driver_arrived_time 司机到达时间
 * @property string $pick_up_passenger_time 去接乘客时间
 * @property string $pick_up_passenger_longitude 去接乘客经度
 * @property string $pick_up_passenger_latitude 去接乘客纬度
 * @property string $pick_up_passenger_address 去接乘客地点
 * @property string $receive_passenger_time 接到乘客时间
 * @property string $receive_passenger_longitude 接到乘客经度
 * @property string $receive_passenger_latitude 接到乘客纬度
 * @property string $receive_passenger_address 接到乘客地点
 * @property string $passenger_getoff_time 乘客下车时间
 * @property string $passenger_getoff_longitude 乘客下车经度
 * @property string $passenger_getoff_latitude 乘客下车纬度
 * @property string $passenger_getoff_address 乘客下车地点
 * @property string $other_name 他人姓名 （乘车人）
 * @property string $other_phone 他人电话　(乘车人）
 * @property int $order_type 订单类型：1：自己叫车，2：他人叫车
 * @property int $service_type 叫车订单类型，
 * 1：实时订单，
 * 2：预约订单，
 * 3：接机单，
 * 4：送机单，
 * 5：包车半日租
 * 6:  包车全日租
 * @property int $order_channel 订单渠道
 * 1.自有订单
 * 2.高德
 * 3.飞猪
 * @property int $status 订单状态 0: 订单预估 1：订单开始 2：司机接单 3：去接乘客 4：司机到达乘客起点 5：乘客上车，司机开始行程 6：到达目的地，行程结束，未支付 7：发起收款 8: 支付完成 9.乘客取消订单
 * @property string $user_feature 1：儿童用车
 * 2：女性用车
 * @property string $transaction_id 商户交易id
 * @property string $mapping_id 乘客绑定号id
 * @property string $mapping_number 乘客关联号码
 * @property string $other_mapping_id 它人绑定id
 * @property string $other_mapping_number 它人绑定号码
 * @property string $merchant_id 商户id
 * @property int $is_evaluate_driver 司机是否评价乘客，0：未评价，1：已评价
 * @property int $is_evaluate 乘客是否评价司机，0：未评价，1：已评价
 * @property int $invoice_type 发票状态：
 * 1：未开票，
 * 2：申请开票，
 * 3：开票中，
 * 4：已开票,
 * 5：退回,
 * @property int $is_annotate 通知客服状态
 * 0，未通知
 * 1,  已通知
 * @property string $source 设备来源
 * 1: ios
 * 2:android
 * 3.other
 * @property int $use_coupon 是否使用优惠券
 * 0:未使用  1:使用
 * @property int $cancel_order_type 取消原因类型id
 * @property int $pay_type 1:余额
 * 2.微信
 * 3.支付宝
 * @property int $is_paid 是否已支付 0：未支付  1：已支付
 * @property int $is_cancel 是否取消  0：未取消   1：已取消
 * @property int $is_adjust 调帐状态  0：未调帐  1:调账中 2.调账完毕
 * @property int $is_dissent 是否疑义订单 0：否  1：是
 * @property int $is_manual 是否人工派单0 否 1 原来无司机, 人工派 2原来有司机，改派
 * @property int $is_following 是否是顺风单0否 1是
 * @property int $is_fake_success 是否是假成功单0 否 1是
 * @property string $memo 备忘录
 * @property int $is_use_risk 是否使用最风险限额 0 否 1是
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class Order extends BaseModel
{
    const IS_ADJUSTED = 2;
    const IS_ADJUSTING = 1;
    const IS_NOT_ADJUST = 0;

    const STATUS_CANCEL = 9;
    const STATUS_GET_ON = 5;
    const STATUS_ARRIVED = 6; //到达目的地
    const STATUS_GATHERING = 7; //收款状态
    const STATUS_COMPLETE = 8; //支付完成
    const STATUS_DRIVER_ARRIVE = 4;//司机到达
    const STATUS_RECEIVE_PASSENGER = 3;//去接乘客
    const STATUS_GRAB = 2;//司机抢单
    const ORDER_START = 1;

    const IS_CANCEL = 1;
    const IS_NOT_CANCEL = 0;
    const IS_PAID_YES = 1;
    const IS_PAID_NO = 0;

    const ORDER_FOR_SELF = 1;
    const ORDER_FOR_OTHER = 2;

    const SERVICE_TYPE_REAL_TIME = 1;
    const SERVICE_TYPE_RESERVE = 2;

    const PAID_FINISH = 8;

    const IS_FAKE_SUCCESS = 1; //假成功
    const IS_NOT_FAKE_SUCCESS = 0;
    const IS_USE_RISK = 1; //是否是使用了下单限额
    const IS_NOT_USE_RISK = 0;

    //订单对应推送messageType
    public static $orderStatus = [
        '201'=> '去接乘客',
        '202'=> '司机到达上车点',
        '203'=> '已上车',
        '204'=> '到达目的地',
        '205'=> '司机发起收款',
        '206'=> '支付成功！',
        '207'=> '支付成功！',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'driver_id', 'driver_status', 'car_id', 'order_type', 'service_type', 'order_channel', 'status', 'is_evaluate_driver', 'is_evaluate', 'invoice_type', 'is_annotate', 'use_coupon', 'cancel_order_type', 'pay_type', 'is_paid', 'is_cancel', 'is_adjust', 'is_dissent', 'is_manual', 'is_following', 'is_fake_success', 'is_use_risk'], 'integer'],
            [['passenger_phone', 'user_longitude', 'user_latitude', 'start_longitude', 'start_latitude', 'start_address', 'end_address', 'end_longitude', 'end_latitude', 'service_type', 'status', 'source'], 'required'],
            [['start_time', 'order_start_time', 'driver_grab_time', 'driver_start_time', 'driver_arrived_time', 'pick_up_passenger_time', 'receive_passenger_time', 'passenger_getoff_time', 'create_time', 'update_time'], 'safe'],
            [['order_number', 'user_longitude', 'user_latitude', 'start_longitude', 'start_latitude', 'end_longitude', 'end_latitude', 'pick_up_passenger_longitude', 'pick_up_passenger_latitude', 'receive_passenger_longitude', 'receive_passenger_latitude', 'passenger_getoff_longitude', 'passenger_getoff_latitude', 'transaction_id', 'mapping_number', 'other_mapping_id', 'merchant_id'], 'string', 'max' => 32],
            [['passenger_phone', 'device_code', 'driver_phone', 'other_phone', 'user_feature', 'mapping_id', 'other_mapping_number', 'source'], 'string', 'max' => 64],
            [['plate_number', 'other_name'], 'string', 'max' => 16],
            [['start_address', 'end_address'], 'string', 'max' => 128],
            [['pick_up_passenger_address'], 'string', 'max' => 300],
            [['receive_passenger_address', 'passenger_getoff_address'], 'string', 'max' => 255],
            [['memo'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_number' => 'Order Number',
            'passenger_info_id' => 'Passenger Info ID',
            'passenger_phone' => 'Passenger Phone',
            'device_code' => 'Device Code',
            'driver_id' => 'Driver ID',
            'driver_status' => 'Driver Status',
            'driver_phone' => 'Driver Phone',
            'car_id' => 'Car ID',
            'plate_number' => 'Plate Number',
            'user_longitude' => 'User Longitude',
            'user_latitude' => 'User Latitude',
            'start_longitude' => 'Start Longitude',
            'start_latitude' => 'Start Latitude',
            'start_address' => 'Start Address',
            'end_address' => 'End Address',
            'start_time' => 'Start Time',
            'order_start_time' => 'Order Start Time',
            'end_longitude' => 'End Longitude',
            'end_latitude' => 'End Latitude',
            'driver_grab_time' => 'Driver Grab Time',
            'driver_start_time' => 'Driver Start Time',
            'driver_arrived_time' => 'Driver Arrived Time',
            'pick_up_passenger_time' => 'Pick Up Passenger Time',
            'pick_up_passenger_longitude' => 'Pick Up Passenger Longitude',
            'pick_up_passenger_latitude' => 'Pick Up Passenger Latitude',
            'pick_up_passenger_address' => 'Pick Up Passenger Address',
            'receive_passenger_time' => 'Receive Passenger Time',
            'receive_passenger_longitude' => 'Receive Passenger Longitude',
            'receive_passenger_latitude' => 'Receive Passenger Latitude',
            'receive_passenger_address' => 'Receive Passenger Address',
            'passenger_getoff_time' => 'Passenger Getoff Time',
            'passenger_getoff_longitude' => 'Passenger Getoff Longitude',
            'passenger_getoff_latitude' => 'Passenger Getoff Latitude',
            'passenger_getoff_address' => 'Passenger Getoff Address',
            'other_name' => 'Other Name',
            'other_phone' => 'Other Phone',
            'order_type' => 'Order Type',
            'service_type' => 'Service Type',
            'order_channel' => 'Order Channel',
            'status' => 'Status',
            'user_feature' => 'User Feature',
            'transaction_id' => 'Transaction ID',
            'mapping_id' => 'Mapping ID',
            'mapping_number' => 'Mapping Number',
            'other_mapping_id' => 'Other Mapping ID',
            'other_mapping_number' => 'Other Mapping Number',
            'merchant_id' => 'Merchant ID',
            'is_evaluate_driver' => 'Is Evaluate Driver',
            'is_evaluate' => 'Is Evaluate',
            'invoice_type' => 'Invoice Type',
            'is_annotate' => 'Is Annotate',
            'source' => 'Source',
            'use_coupon' => 'Use Coupon',
            'cancel_order_type' => 'Cancel Order Type',
            'pay_type' => 'Pay Type',
            'is_paid' => 'Is Paid',
            'is_cancel' => 'Is Cancel',
            'is_adjust' => 'Is Adjust',
            'is_dissent' => 'Is Dissent',
            'is_manual' => 'Is Manual',
            'is_following' => 'Is Following',
            'is_fake_success' => 'Is Fake Success',
            'memo' => 'Memo',
            'is_use_risk' => 'Is Use Risk',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 返回订单实际支付本金金额
     * @param number $orderId 订单ID
     * @return number
     */
    public static function getPayCapital($orderId)
    {
        if (empty($orderId)) {
            return 0;
        }
        $wr = PassengerWalletRecord::find()->select(['order_id', 'pay_capital', 'pay_give_fee', 'refund_capital', 'refund_give_fee', 'trade_type'])
            ->where(['order_id' => $orderId, 'trade_type' => [2, 3, 5, 6], 'pay_status' => 1])
            ->asArray()->all();
        $capital = 0;
        if (!empty($wr)) {
            foreach ($wr as $k => $v) {
                if ($v['trade_type'] == 2 || $v['trade_type'] == 5 || $v['trade_type'] == 6) {
                    if ($v['pay_capital'] > 0) {
                        $capital = sprintf("%.2f", ($capital + $v['pay_capital']));
                    } elseif ($v['refund_capital'] > 0) {
                        $capital = sprintf("%.2f", ($capital + $v['refund_capital']));
                    }
                }
                if ($v['trade_type'] == 3) {//退款
                    if ($v['refund_capital'] > 0) {
                        $capital = sprintf("%.2f", ($capital - $v['refund_capital']));
                    } elseif ($v['pay_capital'] > 0) {
                        $capital = sprintf("%.2f", ($capital - $v['pay_capital']));
                    }
                }
            }
        }
        return $capital;
    }
}
