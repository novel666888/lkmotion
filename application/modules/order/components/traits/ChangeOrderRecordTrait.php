<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/15
 * Time: 20:50
 */
namespace application\modules\order\components\traits;

use application\modules\order\models\OrderReassignmentRecord;
use application\modules\order\models\SysUser;
use common\models\BaseModel;
use common\services\traits\ModelTrait;
use yii\base\UserException;

trait ChangeOrderRecordTrait {

    use ModelTrait;

    /**
     * @param $orderId
     * @param BaseModel $modelClass
     * @return array
     * @throws UserException
     */

    public function getChangeOrderRecordList($orderId,$modelClass)
    {
        if(!$orderId){
            throw new UserException('Params error!',1001);
        }
        $returnCols = $modelClass::$returnCols;
        $activeQuery = $modelClass::find()->select($returnCols)->where('order_id=:order_id',[':order_id'=>$orderId]);
        if($modelClass==OrderReassignmentRecord::class){
            $activeQuery->andWhere(['<>','driver_id_now',0]);
        }
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
            if($operator){
                foreach ($data['data']['list'] as $k=>$v){
                    if(isset($operator[$v['operatorId']]['username'])){
                        $data['data']['list'][$k]['operator'] = $operator[$v['operatorId']]['username'];
                    }else {
                        $data['data']['list'][$k]['operator'] = $data['data']['list'][$k]['operatorId'];
                    }

                }
            }

        }

        return $data;

    }

}