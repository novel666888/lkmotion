<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%base_info_company_pay}}".
 *
 * @property int $id
 * @property string $pay_name 银行或非银行支付机构名称
 * @property string $pay_id 非银行支付机构支付业务许可证编号
 * @property string $pay_type 支付业务类型
 * @property string $pay_scope 业务覆盖范围
 * @property string $prepare_bank 备付金存管银行
 * @property int $count_date 结算周期（天）
 * @property int $state 状态0有效，1失效
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class BaseInfoCompanyPay extends \yii\db\ActiveRecord
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%base_info_company_pay}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pay_name', 'pay_id', 'pay_type', 'pay_scope', 'prepare_bank', 'count_date', 'state'], 'required'],
            [['pay_name', 'pay_id', 'pay_type', 'pay_scope', 'prepare_bank', 'count_date', 'state'], 'trim'],
            [['count_date', 'state'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['pay_name'], 'string', 'max' => 256],
            [['pay_id'], 'string', 'max' => 32],
            [['pay_type', 'pay_scope', 'prepare_bank'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_name' => 'Pay Name',
            'pay_id' => 'Pay ID',
            'pay_type' => 'Pay Type',
            'pay_scope' => 'Pay Scope',
            'prepare_bank' => 'Prepare Bank',
            'count_date' => 'Count Date',
            'state' => 'State',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
