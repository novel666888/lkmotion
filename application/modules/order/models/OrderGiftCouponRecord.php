<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/15
 * Time: 14:50
 */
namespace application\modules\order\models;

use application\modules\order\components\PhoneNumber;
use common\models\Coupon;
use common\services\traits\ModelTrait;
use yii\base\UserException;
use yii\db\Expression;

class OrderGiftCouponRecord extends \common\models\OrderGiftCouponRecord
{
    use ModelTrait;

    /**
     * @param $orderId
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function getGiftCouponList($orderId)
    {
        if(empty($orderId)){
            throw new UserException('Params error!',1001);
        }
        $returnCols = [
            'id'=>'id',
            'couponId'=>'coupon_id',
            'userPhone'=>'user_phone',
            'operateTime'=>'operator_time',
            'operatorId'=>'operator_id',
            'couponType'=>new Expression('space(-1)'),
            'couponMoney'=>'coupon_amount',
            'expireDate'=>'coupon_expired_date',
        ];
        $activeQuery = self::find()->select($returnCols)
            ->where('order_id=:order_id',[':order_id'=>$orderId])
            ->andWhere(['is not','user_coupon_id',null]);
        //var_dump($activeQuery->createCommand()->getRawSql());exit;
        $data = self::getPagingData($activeQuery,['create_time'=>SORT_DESC]);
        if($data['data']['list']){
            $operatorIds = array_column($data['data']['list'],'operatorId');
            $operatorIds = array_values(array_unique($operatorIds));
            $operator = SysUser::find()
                ->select('id,username')
                ->where(['id'=>$operatorIds])
                ->indexBy('id')
                ->asArray()
                ->all();
            $couponTypeIds = array_column($data['data']['list'],'couponId');
            $couponTypeIds = array_values(array_unique($couponTypeIds));
            $couponTypeNames = Coupon::find()
                ->select('id,coupon_name')
                ->where(['id'=>$couponTypeIds])
                ->indexBy('id')
                ->asArray()
                ->all();
            foreach ($data['data']['list'] as $k=>$v){
                $data['data']['list'][$k]['operator'] = isset($operator[$v['operatorId']]['username'])?$operator[$v['operatorId']]['username']:"";
                $data['data']['list'][$k]['couponType'] = isset($couponTypeNames[$v['couponId']]['coupon_name'])?$couponTypeNames[$v['couponId']]['coupon_name']:"";

            }
            $data['data']['list'] = PhoneNumber::mappingCipherToPhoneNumber($data['data']['list'],['userPhone']);

        }

        return $data;
    }
}