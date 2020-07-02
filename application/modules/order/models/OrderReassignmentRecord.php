<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/15
 * Time: 20:30
 */
namespace application\modules\order\models;

use common\models\OrderReassignmentRecord as OrderReassignmentRecordBoss;
use common\services\traits\ModelTrait;
use yii\base\UserException;

class OrderReassignmentRecord extends OrderReassignmentRecordBoss
{
    use ModelTrait;

    /**
     * @var array
     */

    public static $returnCols = [
        'id'=>'id',
        'driverNameBefore'=>'driver_name_before',
        'driverNameNow'=>'driver_name_now',
        'reasonType' => 'reason_type',
        'reasonText' => 'reason_text',
        'operatorId' => 'operator',
        'operatorTime'=>'update_time',
    ];

    /**
     * @param $orderId
     * @return array
     * @throws UserException
     */

    public static function getReassignmentList($orderId)
    {

        if(!$orderId){
            throw new UserException('Params error',1001);
        }
        $returnCols = [
            'id'=>'id',
            'driverNameBefore'=>'driver_name_before',
            'driverNameNow'=>'driver_name_now',
            'driverIdNow'=>'driver_id_now',
            'reasonType' => 'reason_type',
            'reasonText' => 'reason_text',
            'operatorId' => 'operator',
            'operatorTime'=>'update_time',
        ];
        $activeQuery = self::find()
            ->select($returnCols)
            ->where('order_id=:order_id',[':order_id'=>$orderId])
            ->andWhere(['<>','driver_id_now',0]);
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
                $data['data']['list'][$k]['operator'] = $operator[$v['operatorId']]['username'];
            }
        }

        return $data;

    }
}