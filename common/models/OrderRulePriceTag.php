<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%order_rule_price_tag}}".
 *
 * @property int $id
 * @property int $order_id 订单id
 * @property string $category 价格类型：0预约，1结算
 * @property string $tag_name 标签名称
 * @property string $tag_price 标签费用
 * @property string $create_time create_time
 * @property string $update_time update_time
 */
class OrderRulePriceTag extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%order_rule_price_tag}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['order_id', 'category', 'tag_name'], 'required'],
            [['order_id'], 'integer'],
            [['tag_price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['category'], 'string', 'max' => 1],
            [['tag_name'], 'string', 'max' => 70],
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
            'tag_name' => 'Tag Name',
            'tag_price' => 'Tag Price',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
