<?php
namespace passenger\modules\user\controllers;

use Yii;
use common\util\Json;
use common\util\Common;
use common\controllers\ClientBaseController;

use common\models\Order;
use common\models\OrderCancelRecord;
use common\models\OrderRulePrice;
use common\models\OrderPayment;
use common\models\OrderUseCoupon;

use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;

use common\services\traits\ModelTrait;
use common\logic\finance\Wallet;
use common\logic\order\OrderBillingRulesTrait;
use common\logic\blacklist\BlacklistDashboard;

use passenger\services\OrderDetailStatusTrait;
use yii\base\UserException;
use common\events\OrderEvent;
use common\events\PassengerEvent;

class OrderController extends ClientBaseController
{
    
	use ModelTrait;
    use OrderBillingRulesTrait;
    use OrderDetailStatusTrait;

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 订单支付
     * @return [type] [description]
     */
    public function actionPay(){
        if(!$this->preventRefresh($this, 1)){
            return Json::message("请稍后访问");
        }
		$request = $this->getRequest();
        $requestData = $request->post();
        $requestData['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        if(empty($requestData['orderId'])){
        	return Json::message("订单号错误");
        }
        if(empty($this->userInfo['id'])){
        	return Json::message("身份权限认证错误");
        }
        $chk = Order::find()->select(["passenger_info_id", "is_adjust"])->where(["id"=>$requestData['orderId']])->asArray()->one();
        if($chk['passenger_info_id']!=$this->userInfo['id']){
            return Json::message("没有权限操作订单");
        }
        if($chk['is_adjust']==1){
            return Json::message("订单正在调帐中，请稍后支付", 2);
        }
        $OrderPayment = OrderPayment::find()->select(['remain_price','tail_price', 'replenish_price'])->andFilterWhere(['order_id'=>$requestData['orderId']])->asArray()->one();
        if(empty($OrderPayment)){
            return Json::message("订单付款金额错误");
        }
        if($OrderPayment['remain_price']==0){
            return Json::message("支付金额错误");
        }
        if($OrderPayment['remain_price']<0){
            return Json::message("支付金额错误");
        }
        //验证两个值
        $total_price = sprintf("%.2f", $OrderPayment['tail_price']+$OrderPayment['replenish_price']);
        if($total_price==0){
            return Json::message("支付总额错误");
        }
        if($total_price<0){
            return Json::message("支付总额错误");
        }

        //订单扣款
        $data = Wallet::orderPay($requestData['orderId'], $this->userInfo['id'], 0, $OrderPayment['tail_price'], $OrderPayment['replenish_price'], 1);
        if($data===false){
            return Json::message("订单支付失败");
        }

        //解除设备黑名单
        BlacklistDashboard::relieveDevice($requestData['orderId']);
        $eventData = [
            'identity' => '',
            'orderId' => $requestData['orderId'],
            'extInfo' => ['messageType'=>207],
        ];
        (new OrderEvent())->paySuccess($eventData);
        (new PassengerEvent())->consumption($this->userInfo['id']);

        //返回前端
        $OrderRulePrice = OrderRulePrice::find()->select(['total_price'])->where(["order_id"=>$requestData['orderId'],"category"=>1])->asArray()->one();
        $totalPrice = isset($OrderRulePrice['total_price']) ? trim($OrderRulePrice['total_price']) : "0";
        if($data===true){
            $fine=[];
            $fine['remainPrice']    =   "0";
            $fine['totalPrice']     =   (string)$totalPrice;
            $fine['paymentPrice']   =   sprintf("%.2f", ($OrderPayment['tail_price']+$OrderPayment['replenish_price']));
            $fine['paymentPrice']   =   (string)$fine['paymentPrice'];
        }else{
            $fine=[];
            $fine['remainPrice']    =   (string)$data;
            $fine['totalPrice']     =   (string)$totalPrice;
            $fine['paymentPrice']   =   sprintf("%.2f", ($OrderPayment['tail_price']+$OrderPayment['replenish_price']-$data));
            $fine['paymentPrice']   =   (string)$fine['paymentPrice'];
        }
        return Json::success($fine);
    }

    /**
     * 获取行程列表记录数
     */
    public function actionTripCount(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $fine['completes']      = $this->getCompleteTripsCount($this->userInfo['id']);
        $fine['uncompletes']    = $this->getUncompleteTripsCount($this->userInfo['id']);
        return Json::success($fine);
    }

    /**
     * 获取行程列表
     * @return [type] [description]
     */
    public function actionGetTripList(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['complete'] = isset($requestData['complete']) ? trim($requestData['complete']) : 0;
        if(!in_array((string)$requestData['complete'],[(string)0,(string)1])){
            return Json::message("complete error");
        }
    	if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        if($requestData['complete']==0){
            $rs = $this->getUncompleteTrips($this->userInfo['id']);
            if($rs===false){
                return Json::success();
            }else{
                return $rs;
            }
        }
        if($requestData['complete']==1){
            $rs = $this->getCompleteTrips($this->userInfo['id']);
            if($rs===false){
                return Json::success();
            }else{
                return $rs;
            }
        }
    }


    /**
     * 获取费用详情
     * @return [type] [description]
     */
    public function actionGetTripDetail(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        if(empty($requestData['orderId'])){
            return Json::message("Parameter error");
        }

        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }

        $data = $this->details($requestData['orderId'], $this->userInfo['id']);
        if($data['code']==0){
            if(!empty($data['data'])){
                $data['data']['order_status'] = $this->_getOrderState($data['data']['order_id']);
            }
            return Json::success(Common::key2lowerCamel($data['data']));
        }else{
            return Json::message($data['message']);
        }
    }


    /**
     * 获取可开发票行程列表
     */
    public function actionGetTripListDrawabill(){
        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }
        $request = $this->getRequest();
        $requestData = $request->post();
        $search_orderId = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $_page = isset($requestData['page']) ? trim($requestData['page']) : 1;
        $model = Order::find()->select(['id AS order_id', 'start_time', 'start_address', 'end_address']);
        $model = $model->andFilterWhere(['passenger_info_id'=>intval($this->userInfo['id'])]);
        $model = $model->andFilterWhere(['in', 'invoice_type', [1, 5]]);//1未开票,5退回
        $model = $model->andFilterWhere(['is_paid'=>1]);//已支付
        $model = $model->andFilterWhere(['status'=>8]);//完成支付
        $data = self::getPagingData($model, ['type'=>'DESC','field'=>'start_time'], true);
        if(!empty($data['data']['list'])){
            $ids = ArrayHelper::getColumn($data['data']['list'], 'order_id');
            //获取订单价格
            $record = OrderPayment::find()->select(['order_id', 'total_price', 'coupon_reduce_price', 'paid_price'])
                ->FilterWhere(['in', 'order_id', $ids])
                ->indexBy('order_id')->asArray()->all();
            //放进data里
            foreach($data['data']['list'] as $key => $value){
                //过滤金额为0的订单
                if(!isset($record[$value['order_id']]['total_price'])){
                    unset($data['data']['list'][$key]);
                    continue;
                }elseif($record[$value['order_id']]['total_price']<=0){
                    unset($data['data']['list'][$key]);
                    continue;
                }elseif($record[$value['order_id']]['paid_price']<=0){
                    unset($data['data']['list'][$key]);
                    continue;
                }else{
                    $data['data']['list'][$key]['total_price']      = (string)$record[$value['order_id']]['total_price'];
                    $data['data']['list'][$key]['coupon_price']     = (string)$record[$value['order_id']]['coupon_reduce_price'];
                    $data['data']['list'][$key]['paid_price']       = (string)$record[$value['order_id']]['paid_price'];
                    $data['data']['list'][$key]['price']            = (string)Order::getPayCapital($value['order_id']);
                }

                if(empty($value['start_time'])){
                    $data['data']['list'][$key]['start_time'] = "0";
                }else{
                    $data['data']['list'][$key]['start_time'] = strtotime($value['start_time'])."000";
                }
            }
            $data['data']['list'] = array_values($data['data']['list']);

            //判断置顶
            if(!empty($search_orderId)){
                $_model = Order::find()->select(['id AS order_id', 'start_time', 'start_address', 'end_address'])
                    ->andFilterWhere(['passenger_info_id'=>intval($this->userInfo['id'])])
                    ->andFilterWhere(['in', 'invoice_type', [1, 5]])//1未开票,5退回
                    ->andFilterWhere(['is_paid'=>1])//已支付
                    ->andFilterWhere(['status'=>8])//完成支付
                    ->andFilterWhere(['id'=>$search_orderId])
                    ->asArray()->one();
                if(!empty($_model)){
                    $_record = OrderPayment::find()->select(['order_id', 'total_price', 'coupon_reduce_price', 'paid_price'])
                        ->FilterWhere(['order_id'=>$_model['order_id']])
                        ->asArray()->one();
                    //过滤金额为0的订单
                    if(!isset($_record['total_price'])){
                        unset($_model);
                    }elseif($_record['total_price']<=0){
                        unset($_model);
                    }elseif($_record['paid_price']<=0){
                        unset($_model);
                    }else{
                        $_model['total_price']      = (string)$_record['total_price'];
                        $_model['coupon_price']     = (string)$_record['coupon_reduce_price'];
                        $_model['paid_price']       = (string)$_record['paid_price'];
                        $_model['price']            = (string)Order::getPayCapital($_model['order_id']);
                    }
                    if(isset($_model['start_time'])){
                        if(empty($_model['start_time'])){
                            $_model['start_time'] = "0";
                        }else{
                            $_model['start_time'] = strtotime($_model['start_time'])."000";
                        }
                    }
                }
                //置顶
                if(!empty($_model)){
                    $_list=[];
                    if($_page==1){
                        $_list[] = $_model;
                    }
                    foreach($data['data']['list'] as $_kk => $_vv){
                        if($_vv['order_id'] != $search_orderId){
                            $_list[] = $_vv;
                        }
                    }
                    $data['data']['list'] = $_list;
                }
            }
            $data['data']['list'] = Common::key2lowerCamel($data['data']['list']);
        }
        return $this->asJson($data);
    }





    /**
     *
     * 下面
     *
     */

    /**
     * 获取已完成的行程
     */
    public function getCompleteTrips($userId){
        $rs = $this->countTrips($userId);
        return $this->countPageTrips($rs['complete']);
    }

    /**
     * 获取未完成的行程
     */
    public function getUncompleteTrips($userId){
        $rs = $this->countTrips($userId);
        return $this->countPageTrips($rs['uncomplete']);
    }

    /**
     * 获取已完成的行程记录数
     */
    public function getCompleteTripsCount($userId){
        $rs = $this->countTrips($userId);
        return count($rs['complete']);
    }

    /**
     * 获取未完成的行程记录数
     */
    public function getUncompleteTripsCount($userId){
        $rs = $this->countTrips($userId);
        return count($rs['uncomplete']);
    }


    /**
     * 计算分页
     * @param $orderIds array 订单ID数组
     */
    public function countPageTrips($orderIds){
        if(empty($orderIds)){
            return false;
        }
        $model = Order::find()->select(['id AS order_id', 'status', 'is_evaluate', 'is_cancel', 'service_type', 'order_start_time', 'start_address', 'end_address', "driver_id", 'is_fake_success']);
        $model = $model->FilterWhere(['in', 'id', $orderIds]);
        $data = self::getPagingData($model, ['type'=>'DESC','field'=>'order_start_time'], true);
        if(!empty($data['data']['list'])){
            $ids = [];
            foreach($data['data']['list'] as $value){
                $ids[] = $value['order_id'];
            }
            //获取对应的取消订单
            $record = OrderCancelRecord::find()->select(['*'])->andFilterWhere(['in', 'order_id', $ids])->indexBy('order_id')->asArray()->all();
            //获取对应订单的状态
            try{
                $Ccodes = $this->_getBatchOrderState($ids);
            }catch(UserException $exception){
                //出现脏数据异常情况
                $Ccodes = "";
            }

            //把取消订单表放进data里
            foreach($data['data']['list'] as $key => $value){
                if(isset($record[$value['order_id']])){
                    $data['data']['list'][$key]['cancel_cost'] = $record[$value['order_id']]['cancel_cost'];
                }
                else{
                    $data['data']['list'][$key]['cancel_cost'] = "0";
                }

                if(isset($Ccodes[$value['order_id']])){
                    $data['data']['list'][$key]['order_status'] = $Ccodes[$value['order_id']];
                }else{
                    unset($data['data']['list'][$key]);
                }
            }
            //变为自然排序
            $data['data']['list'] = array_values($data['data']['list']);
            $data['data']['list'] = Common::key2lowerCamel($data['data']['list']);
        }
        return $this->asJson($data);
    }

    /**
     * 获取已完成/未完成的行程
     * @param $userId int 用户ID
     * @return array 返回已完成/未完成订单ID数组
     */
    public function countTrips($userId){

        $model = Order::find()->select(['id AS order_id', 'status', 'is_evaluate', 'is_cancel',  "driver_id", 'is_fake_success']);
        $model->andFilterWhere(['passenger_info_id'=>intval($userId)]);
        $model->andFilterWhere(['>','status', 0]);//去掉0状态
        //$data = self::getPagingData($model, ['type'=>'DESC','field'=>'order_start_time'], true);
        $data = $model->asArray()->all();

        if(!empty($data)){
            foreach ($data as $k => $v){
                if($v['status']==1 && $v['is_fake_success']==0){
                    unset($data[$k]);
                }
                if($v['status']==9 && ($v['driver_id']=="" or $v['driver_id']==null or $v['driver_id']=="null")){
                    unset($data[$k]);
                }
            }
        }

        $complete   = [];//结束的订单ID集合
        $uncomplete = [];//未结束的订单ID集合
        if(!empty($data)){
            $ids = [];
            foreach($data as $k => $v){
                $ids[] = $v['order_id'];
            }
            //获取对应的取消订单
            $payments = OrderPayment::find()->select(['order_id', 'remain_price'])->andFilterWhere(['in', 'order_id', $ids])->indexBy('order_id')->asArray()->all();
            foreach($data as $k => $v){
                if(isset($payments[$v['order_id']])){
                    $data[$k]['remain_price'] = $payments[$v['order_id']]['remain_price'];
                }else{
                    $data[$k]['remain_price'] = 0;
                }
            }

            foreach($data as $k => $v){
                //非取消订单时，未评价的，无论是否支付，是未结束状态
                if($v['is_cancel']==0 && $v['is_evaluate']==0){
                    $uncomplete[] = $v['order_id'];
                }
                //非取消订单时，已评价的，有剩余未支付的，是未结束状态
                elseif ($v['is_cancel']==0 && $v['is_evaluate']==1 && $v['remain_price']>0){
                    $uncomplete[] = $v['order_id'];
                }
                //取消订单时，不可评价，判断有剩余未支付的，是未结束状态
                elseif ($v['is_cancel']==1 && $v['remain_price']>0){
                    $uncomplete[] = $v['order_id'];
                }
                //结束的订单状态
                else{
                    $complete[] = $v['order_id'];
                }
            }
        }

        return [
            'complete' => $complete,
            'uncomplete' => $uncomplete,
        ];

    }

}