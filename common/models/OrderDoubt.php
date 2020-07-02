<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_doubt}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property string $appeal_number 申诉单号
 * @property int $adjust_type 调账类型
 1充值
 2扣款
 3发优惠券
 * @property string $adjust_content 调账内容 充值| 扣款 单号，优惠券
 * @property int $reason_type 疑义原因类型 
 1
 2
 3
 4
 5其他
 * @property string $reason_text 自定义其他疑义原因
 * @property string $old_cost 调账前金额
 * @property string $now_cost 调账后金额
 * @property int $status 1待处理 
 2待审核 
 3已审核 
 4已完成 
 5已撤销 
 * @property int $handle_type 处理类型 
 1.调账
 2.发券
 * @property string $solution 处理方案
 * @property string $operators 操作人 （多人操作全记录)
 * @property string $operate_time 操作时间
 * @property string $create_time 提交时间
 * @property string $update_time 更新时间
 */
class OrderDoubt extends BaseModel
{
    /**
     * 1 待处理
     * 2 待审核
     * 3 已审核
     * 4 已完成
     * 5 已撤销
     */
    const STATUS_HANDLING = 1;
    const STATUS_AUDITING = 2;
    const STATUS_AUDITED = 3;
    const STATUS_FINISH = 4;
    const STATUS_REVOKED = 5;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_doubt}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id'], 'required'],
            [['order_id', 'adjust_type', 'reason_type', 'status', 'handle_type'], 'integer'],
            [['old_cost', 'now_cost'], 'number'],
            [['operate_time', 'create_time', 'update_time'], 'safe'],
            [['appeal_number'], 'string', 'max' => 32],
            [['adjust_content', 'solution'], 'string', 'max' => 200],
            [['reason_text'], 'string', 'max' => 300],
            [['operators'], 'string', 'max' => 50],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'appeal_number' => 'Appeal Number',
            'adjust_type' => 'Adjust Type',
            'adjust_content' => 'Adjust Content',
            'reason_type' => 'Reason Type',
            'reason_text' => 'Reason Text',
            'old_cost' => 'Old Cost',
            'now_cost' => 'Now Cost',
            'status' => 'Status',
            'handle_type' => 'Handle Type',
            'solution' => 'Solution',
            'operators' => 'Operators',
            'operate_time' => 'Operate Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
