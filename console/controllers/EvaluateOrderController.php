<?php
/**
 * 乘客自动评价司机、大屏
 * 
 * Created by Zend Studio
 * User: sunliang
 * Date: 2018年10月19日
 * Time: 上午
 */
namespace console\controllers;

use common\models\Order;
use common\util\Common;
use common\util\Json;
use yii\console\Controller;
use common\models\OrderPayment;
use passenger\models\EvaluateDriver;
use common\models\EvaluateCarscreen;
use yii\base\UserException;
use common\services\traits\PublicMethodTrait;

class EvaluateOrderController extends Controller
{
    use PublicMethodTrait;

    //php yii evaluate-order/index
    public function actionIndex(){
        $orders = $this->getOrder();
        //echo count($orders);exit;
        //\Yii::info($orders,'EvaluateOrder_0');
        if(!empty($orders)){
            foreach ($orders as $k => $v){
                $this->pj($v);
            }
        }
    }

    /**
     * 返回符合要求的订单
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getOrder(){
        $time = date("Y-m-d H:i:s", time()-86400*2);
        $all = OrderPayment::find()->select(['order_id'])->where(['<', 'pay_time', $time])
            ->andWhere(['>', 'pay_time', '0000-00-00 00:00:00'])
            ->asArray()->all();
        if(!empty($all)){
            foreach ($all as $k => $v){
                $order_ids[] = $v['order_id'];
            }
            $orders = Order::find()->select(['id','passenger_info_id','driver_id','car_id'])->andFilterWhere(['in', 'id', $order_ids])
                ->andFilterWhere(['status'=>8])
                ->andFilterWhere(['is_evaluate'=>0])
                ->asArray()->all();
            return $orders;
        }
        return [];
    }

    /**
     * 评价
     * @param $order
     */
    public function pj($order){
        if(empty($order['id'])){
            return;
        }
        $trans = \Yii::$app->db->beginTransaction();
        try {
            //乘客评价司机
            $evalDriverModel = new EvaluateDriver;
            $insertSets = [
                'order_id' => $order['id'],
                'passenger_id' => $order['passenger_info_id'],
                'grade' => 4,//默认4星级
                'label' => '',
                'content' => '',
                'driver_id' => $order['driver_id'],
            ];
            $insertSets = Common::filterNull($insertSets);
            $evalDriverModel->setAttributes($insertSets);
            if (!$evalDriverModel->save()) {
                throw new UserException($evalDriverModel->getFirstError(), 1002);
            }

            //乘客评价大屏
            $EvaluateCarscreen = new EvaluateCarscreen;
            $_insertSets = [
                'order_id' => $order['id'],
                'passenger_id' => $order['passenger_info_id'],
                'grade' => 4,//默认4星级
                'content' => '',
                'car_id' => $order['car_id'],
            ];
            $_insertSets = Common::filterNull($_insertSets);
            $EvaluateCarscreen->setAttributes($_insertSets);
            if (!$EvaluateCarscreen->save()) {
                throw new UserException($EvaluateCarscreen->getFirstError(), 1002);
            }

            /************将订单改为已经评价状态****************/
            $model_order = order::find()->where(['id' => $order['id']])->one();
            if (!empty($model_order)) {
                $model_order->is_evaluate = 1;
                if (!$model_order->save(false)) {
                    throw new UserException($model_order->getFirstError(), 1003);
                }
            }
            $trans->commit();
        }catch (UserException $exception){
            $trans->rollBack();
            \Yii::info($exception->getMessage(), 'EvaluateOrderController');
        }
    }

}