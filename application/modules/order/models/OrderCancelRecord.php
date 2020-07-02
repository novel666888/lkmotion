<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/15
 * Time: 16:47
 */
namespace application\modules\order\models;
use common\models\OrderCancelRecord as OrderCancelRecordBoss;
use common\services\traits\ModelTrait;
use yii\base\UserException;

class OrderCancelRecord extends OrderCancelRecordBoss
{
    use ModelTrait;

    /**
     * @param $orderId
     * @return array
     * @throws UserException
     */

    public static function getOrderCancelOrderList($orderId)
    {
        if (!$orderId) {
            throw new UserException('Params error', 1001);
        }
        $returnCols  = [
            'id'           => 'id',
            'orderId'      => 'order_id',
            'reasonType'   => 'reason_type',
            'reasonText'   => 'reason_text',
            'cancelCost'   => 'cancel_cost',
            'operateTime'  => 'update_time',
            'operatorType' => 'operator_type',
            'operatorId'   => 'operator',
        ];
        $activeQuery = self::find()->select($returnCols)->where('order_id=:order_id', [':order_id' => $orderId]);
        $data        = self::getPagingData($activeQuery, ['create_time' => SORT_DESC]);
        if ($data['data']['list']) {
            $operatorIds = array_column($data['data']['list'], 'operatorId');
            $operatorIds = array_merge(array_diff($operatorIds, array(0)));
            $operatorIds = array_unique($operatorIds);
            $operator    = SysUser::find()
                ->select(['id','username'])
                ->where(['id' => $operatorIds])
                ->indexBy('id')
                ->asArray()
                ->all();
            foreach ($data['data']['list'] as $k => $v) {
                if ($v['operatorType'] == 1) {
                    $data['data']['list'][$k]['operator'] = '乘客自己';
                } else {
                    $data['data']['list'][$k]['operator'] =
                        isset($operator[$v['operatorId']]['username'])?$operator[$v['operatorId']]['username']:'';
                }
            }
        }

        return $data;
    }

}