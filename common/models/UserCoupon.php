<?php

namespace common\models;

use common\services\traits\ModelTrait;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%user_coupon}}".
 *
 * @property int $id 领取记录ID
 * @property int $passenger_id 乘客ID
 * @property string $phone_number 电话号码
 * @property string $order_id 关联订单号
 * @property int $coupon_id 优惠券ID
 * @property string $coupon_name
 * @property int $coupon_type 优惠券类型 1:现金券, 2:专项券-免费送车券, 3:专项券-免费还车券 4:折扣券
 * @property int $get_method    1,主动发放. 2,用户获取
 * @property string $enable_time 优惠券起效时间
 * @property string $create_time 获取时间
 * @property string $expire_time 过期时间
 * @property string $update_time 使用时间
 * @property string $use_time 使用时间
 * @property int $is_use 状态 0:未使用, 1:已使用
 * @property string $minimum_amount 最低消费金额
 * @property string $reduction_amount 减免金额(现金券：能抵扣的金额,折扣券：最高能抵扣的金额)
 * @property string $discount 折扣8,7.5
 * @property int $function_type 功能类型 1:市场活动, 2:订单赔付
 * @property int $indemnity_record_id 赔付记录ID
 * @property string $activity_tag 活动标记
 * @property int $activity_id 活动ID
 */
class UserCoupon extends \common\models\BaseModel
{
    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user_coupon}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_id', 'coupon_id', 'coupon_type', 'get_method', 'is_use', 'function_type', 'indemnity_record_id', 'activity_id'], 'integer'],
            [['enable_time', 'create_time', 'expire_time', 'update_time', 'use_time'], 'safe'],
            [['minimum_amount', 'reduction_amount', 'discount'], 'number'],
            [['phone_number'], 'string', 'max' => 20],
            [['order_id'], 'string', 'max' => 32],
            [['coupon_name', 'activity_tag'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_id' => 'Passenger ID',
            'phone_number' => 'Phone Number',
            'order_id' => 'Order ID',
            'coupon_id' => 'Coupon ID',
            'coupon_name' => 'Coupon Name',
            'coupon_type' => 'Coupon Type',
            'get_method' => 'Get Method',
            'enable_time' => 'Enable Time',
            'create_time' => 'Create Time',
            'expire_time' => 'Expire Time',
            'update_time' => 'Update Time',
            'use_time' => 'Use Time',
            'is_use' => 'Is Use',
            'minimum_amount' => 'Minimum Amount',
            'reduction_amount' => 'Reduction Amount',
            'discount' => 'Discount',
            'function_type' => 'Function Type',
            'indemnity_record_id' => 'Indemnity Record ID',
            'activity_tag' => 'Activity Tag',
            'activity_id' => 'Activity ID',
        ];
    }

    /**
     * 获取我的优惠券列表
     * 1.未使用且过期的也会显示出来
     * @return array
     */
    public static function getCouponList($condition, $field = ['*'])
    {
        if (empty($condition['passengerId'])) {
            return ['code' => 0, 'message' => '参数丢失'];
        }
        $model = self::find()->select($field)->from('tbl_user_coupon AS uc');
        $model = $model->andFilterWhere(['uc.passenger_id' => intval($condition['passengerId'])]);
        $model = $model->andFilterWhere(['uc.is_use' => 0]);
        $date = date("Y-m-d H:i:s", time());
//        $model = $model->andFilterWhere(['uc.order_id'=>'']);
        $model = $model->andWhere(['uc.order_id' => '']);
        $model = $model->andFilterWhere(['>', 'expire_time', $date]);
        $data = self::getPagingData($model, ['uc.expire_time' => SORT_ASC, 'uc.id' => SORT_DESC], true);
        if (isset($data['data']['list']) && !empty($data['data']['list'])) {
            foreach ($data['data']['list'] as &$v) {
                $v['coupon_name'] = "";
                $v['coupon_desc'] = "";
                $v['reduction_amount'] = sprintf("%.1f", $v['reduction_amount']);
                $v['minimum_amount'] = sprintf("%.1f", $v['minimum_amount']);
            }
            $cipher = ArrayHelper::getColumn($data['data']['list'], 'coupon_id');
            if (!empty($cipher)) {
                $jg = Coupon::find()->select(['id', 'coupon_name', 'coupon_desc'])->andFilterWhere(['in', 'id', $cipher])->indexBy("id")->asArray()->all();
                foreach ($data['data']['list'] as $k => &$v) {
                    if (isset($jg[$v['coupon_id']])) {
                        $v['coupon_name'] = $jg[$v['coupon_id']]['coupon_name'];
                        $v['coupon_desc'] = $jg[$v['coupon_id']]['coupon_desc'];
                    }
                }
            }
        }
        return $data['data'];

    }

    /**
     * 获取优惠券用券记录
     * @return array
     */
    public static function getUsedRecordList($condition, $field = ['*'])
    {
        if (empty($condition['passengerId'])) {
            return ['code' => 0, 'message' => '参数丢失'];
        }
        $model = self::find()->select($field)->from('tbl_user_coupon AS uc');
        $model = $model->andFilterWhere(['uc.passenger_id' => intval($condition['passengerId'])]);
        $model = $model->andFilterWhere(['uc.is_use' => 1]);
        $data = self::getPagingData($model, ['uc.use_time' => SORT_DESC, 'uc.id' => SORT_DESC], true);
        if (isset($data['data']['list']) && !empty($data['data']['list'])) {
            foreach ($data['data']['list'] as &$v) {
                $v['coupon_name'] = "";
                $v['coupon_desc'] = "";
                $v['reduction_amount'] = sprintf("%.1f", $v['reduction_amount']);
                $v['minimum_amount'] = sprintf("%.1f", $v['minimum_amount']);
            }
            $cipher = ArrayHelper::getColumn($data['data']['list'], 'coupon_id');
            if (!empty($cipher)) {
                $jg = Coupon::find()->select(['id', 'coupon_name', 'coupon_desc', 'coupon_type'])->andFilterWhere(['in', 'id', $cipher])->indexBy("id")->asArray()->all();
                foreach ($data['data']['list'] as $k => &$v) {
                    if (isset($jg[$v['coupon_id']])) {
                        $v['coupon_name'] = $jg[$v['coupon_id']]['coupon_name'];
                        $v['coupon_desc'] = $jg[$v['coupon_id']]['coupon_desc'];
                    }
                }
            }
        }
        return $data['data'];
    }

    /**
     * @param $userId
     * @param string $type
     * @return array
     */
    public function getCoupons($userId, $type = '')
    {
        $query = $this->getCouponQuery($userId, $type);
        if ($type == 'used') {
            $list = self::getPagingData($query, 'use_time DESC, id ASC');
            $list['data']['total'] = $query->sum('reduction_amount');
        } elseif ($type == 'expired') {
            $list = self::getPagingData($query, 'expire_time DESC, id DESC');
        } else {
            $list = self::getPagingData($query, 'expire_time ASC, id DESC');
        }
        // 附加优惠券使用说明
        $couponIds = array_column($list['data']['list'], 'coupon_id');
        if ($couponIds) {
            $couponMap = array_column(Coupon::find()->where(['in', 'id', $couponIds])->select('id,coupon_desc')->all(), 'coupon_desc', 'id');
        } else {
            $couponMap = [];
        }
        foreach ($list['data']['list'] as &$item) {
            if (isset($item['coupon_id']) && isset($couponMap[$item['coupon_id']])) {
                $item['coupon_desc'] = $couponMap[$item['coupon_id']];
            }
        }
        return $list;
    }

    /**
     * @param $userId
     * @param $type
     * @return \yii\db\ActiveQuery
     */
    public function getCouponQuery($userId, $type)
    {
        $query = self::find()->select('id,coupon_id,coupon_type,coupon_name,reduction_amount,discount,is_use,enable_time,expire_time,use_time');
        $query->where(['passenger_id' => $userId]);
        // 分类
        $now = date('Y-m-d H:i:s');
        if ($type == 'expired') { // 过期
            $query->andWhere(['<', 'expire_time', $now]);
        } elseif ($type == 'normal') { // 正常
            $query->andWhere(['is_use' => 0])->andWhere(['>', 'expire_time', $now])
                ->andWhere(['order_id' => '']); //未冻结
        } elseif ($type == 'unused') { // 未使用
            $query->andWhere(['is_use' => 0]);
        } elseif ($type == 'used') { // 已使用
            $query->andWhere(['is_use' => 1]);
        }
        return $query;
    }
}
