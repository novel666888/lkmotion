<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_passenger_statics".
 *
 * @property string $id
 * @property string $passenger_info_id 用户ID
 * @property string $total_distance 总里程
 * @property string $month_distance 当月里程
 * @property string $total_charge 充值总金额
 * @property string $total_refund 退款总额
 * @property string $total_order_pay 订单支付总额
 * @property string $month_order_pay 月订单支付总额
 * @property string $total_invoice 已开票总金额
 * @property string $can_invoice 未开票总金额
 */
class PassengerStatics extends \common\models\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_passenger_statics';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['passenger_info_id'], 'integer'],
            [['total_distance', 'month_distance', 'total_charge', 'total_refund', 'total_order_pay', 'month_order_pay', 'total_invoice', 'can_invoice'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'total_distance' => 'Total Distance',
            'month_distance' => 'Month Distance',
            'total_charge' => 'Total Charge',
            'total_refund' => 'Total Refund',
            'total_order_pay' => 'Total Order Pay',
            'month_order_pay' => 'Month Order Pay',
            'total_invoice' => 'Total Invoice',
            'can_invoice' => 'Can Invoice',
        ];
    }
}
