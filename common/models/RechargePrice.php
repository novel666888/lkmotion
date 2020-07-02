<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_recharge_price".
 *
 * @property string $id paymentID
 * @property double $amount 充值金额
 * @property double $reward 赠送金额
 * @property string $desc 金额描述信息
 * @property bool $is_deleted 是否被删除 1 ：已删除 0 ：未删除
 */
class RechargePrice extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_recharge_price';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['amount', 'reward'], 'number'],
            [['is_deleted'], 'boolean'],
            [['desc'], 'string', 'max' => 200],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'amount' => 'Amount',
            'reward' => 'Reward',
            'desc' => 'Desc',
            'is_deleted' => 'Is Deleted',
        ];
    }


}
