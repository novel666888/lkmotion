<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/15
 * Time: 14:50
 */
namespace application\modules\order\models;

use common\models\OrderAdjustRecord as OrderAdjustRecordBoss;
use common\services\traits\ModelTrait;
use yii\base\UserException;

class OrderAdjustRecord extends OrderAdjustRecordBoss
{
    use ModelTrait;

    /**
     * @param $orderId
     * @return array
     * @throws UserException
     */
    public static function getAdjustList($orderId)
    {
        if(empty($orderId)){
            throw new UserException('Params error!',1001);
        }
        $returnCols = [
            'id'=>'id',
            'adjustAccountType'=>'adjust_account_type',
            'chargeNumber' => 'charge_number',
            'oldCost'=>'old_cost',
            'newCost'=>'new_cost',
            'reasonType'=>'reason_type',
            'reasonText'=>'reason_text',
            'solution'=>'solution',
            'operateTime'=>'update_time',
            'operatorId'=>'operator',
        ];
        $activeQuery = self::find()->select($returnCols)
            ->where('order_id=:order_id',[':order_id'=>$orderId])
            ->andWhere(['is not','charge_number',null]);
        $data = self::getPagingData($activeQuery,['create_time'=>SORT_DESC]);
        if($data['data']['list']){
            $operatorIds = array_column($data['data']['list'],'operatorId');
            $operatorIds = array_unique($operatorIds);
            $operator = SysUser::find()
                ->select('id,username')
                ->where(['id'=>$operatorIds])
                ->indexBy('id')
                ->asArray()
                ->all();
            foreach ($data['data']['list'] as $k=>$v){
                $data['data']['list'][$k]['operator'] = empty($operator[$v['operatorId']]['username'])?'':$operator[$v['operatorId']]['username'];
            }
        }

        return $data;
    }
}