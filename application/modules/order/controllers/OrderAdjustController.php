<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/24
 * Time: 11:02
 */

namespace application\modules\order\controllers;

use application\modules\order\components\traits\ChangeOrderRecordTrait;
use application\modules\order\models\OrderAdjustRecord;
use application\modules\order\models\OrderBoss;
use application\modules\order\models\OrderDoubt;
use application\modules\order\models\OrderGiftCouponRecord;
use application\modules\order\models\OrderPayment;
use common\logic\blacklist\BlacklistDashboard;
use common\logic\finance\Wallet;
use common\logic\order\OrderDetailStatusTrait;
use common\models\Coupon;
use common\models\DriverIncomeDetail;
use common\services\CConstant;
use common\services\traits\PublicMethodTrait;
use common\services\YesinCarHttpClient;
use common\util\Common;
use common\util\Json;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
class OrderAdjustController extends BossBaseController
{
    use PublicMethodTrait;
    use ChangeOrderRecordTrait;
    use OrderDetailStatusTrait;


    private $_pushType = 3; //订单通知 类型
    private $_pushDataBase = [
        'sendId' =>'system',
        'sendIdentity' => 1,
        'messageType' => 1
    ]; //推送数据体(base)

    /**
     * index
     *
     * @return array
     */
    public function actionIndex()
    {
        return Json::success();
    }

    /**
     * @return array|mixed
     */

    private function _getOderId()
    {
        $request = $this->getRequest();
        $orderId = $request->post('orderId');
        return $orderId;
    }

    /**
     * @return mixed
     * @throws \yii\db\Exception
     */

    public function actionAdjustAccount()
    {
        $request = $this->getRequest();
        $doubtId = intval($request->post('doubtId'));
        $orderId = intval($request->post('orderId'));
        $oldCost = trim($request->post('oldCost'));
        $newCost = trim($request->post('newCost'));
        $resolution = trim($request->post('solution'));
        $trans      = $this->beginTransaction();
        try {
            if($newCost==$oldCost){
                throw new UserException('调账差额不能为0元,请重新输入',999);
            }
            if(empty($orderId) &&  empty($doubtId)){
                throw new \InvalidArgumentException('Params error!', 1000);
            }
            if (is_null($oldCost) || is_null($newCost) || $oldCost === '' ||  $newCost === '') {
                throw new \InvalidArgumentException('Params error!', 1001);
            }
            if(empty($orderId)){
                $orderIdFromDoubtId = OrderDoubt::findOne($doubtId)->order_id;
                $orderPaymentTable = OrderPayment::findOne(['order_id'=>$orderIdFromDoubtId]);
                $orderModel = OrderBoss::findOne($orderIdFromDoubtId);
            }else{
                $orderPaymentTable = OrderPayment::findOne(['order_id'=>$orderId]);
                $orderModel = OrderBoss::findOne($orderId);
            }
            if (!$orderModel) {
                throw new \RuntimeException('orderId not exist!', 1002);
            }
            if(!in_array($orderModel->status,[7,8]) && $orderModel->status != 9 ){
                throw new UserException('订单正在服务中,暂不能调账!',1003);
            }
            if($orderModel->status == 9 && empty($orderPaymentTable) ){
                throw new UserException('无偿取消订单的无法调账',1004);
            }
            if($oldCost != $orderPaymentTable->final_price){
                throw new UserException('异常操作',1005);
            }
            if(!empty($orderId)){ // 调账来自于订单详情
                $orderDoubtModel = OrderDoubt::find()
                    ->where([
                        'and',
                        ['=', 'order_id', $orderId],
                        ['<=', 'status', 3]
                    ])->one();
                if ($orderDoubtModel) { //如果存在未解决的疑义订单
                    throw new UserException('该笔订单有待处理疑义账单，请先处理后在操作', 1003);
                }
                $orderModel->is_adjust = OrderBoss::IS_ADJUSTING; //adjusting
                if (!$orderModel->save(false)) {
                    throw new UserException($orderModel->getFirstError(),1004);
                }

                $insertDoubtAttributes = [
                    'appeal_number'=> date('YmdHis').mt_rand(1000,9999),
                    'order_id' => $orderId,
                    'old_cost' => floatval($oldCost),
                    'now_cost' => floatval($newCost),
                    'status' => \common\models\OrderDoubt::STATUS_AUDITING,
                    'solution' => $resolution,
                    'operate_time' => date('Y-m-d H:i:s'),
                    'handle_type'=>($newCost-$oldCost)>0?2:1,
                    'operators'=>$this->userInfo['id'],
                ];// 改变疑义定单调账后的金额状态
                $doubtTable = OrderDoubt::insertChunk($insertDoubtAttributes); //插入一条疑义定单
                //($insertDoubtAttributes);
                $orderAdjustAccountRecordModel = new OrderAdjustRecord;
                $orderAdjustAccountRecordModel->setAttributes([
                    'adjust_account_type' => ($newCost-$oldCost)>0?2:1,
                    'doubt_id'=>$doubtTable->id,
                    'order_id' => $orderId,
                    'old_cost' => $oldCost,
                    'new_cost' => $newCost,
                    'solution' => $resolution,
                    'operate_time' => date('Y-m-d H:i:s'),
                    'operator' => $this->userInfo['id'],
                ]);//写入调账记录
                if (!$orderAdjustAccountRecordModel->save()) {
                    throw new UserException($orderAdjustAccountRecordModel->getFirstError(),1005);
                }

            }else {
                $orderDoubtModelAnother  = OrderDoubt::findOne($doubtId);
                if(!$orderDoubtModelAnother){
                    throw new \InvalidArgumentException('Params error!',1006);
                }
                $orderDoubtModelAnother->status = 2;
                $orderDoubtModelAnother->handle_type = ($newCost-$oldCost)>0?2:1;
                $orderDoubtModelAnother->old_cost = $oldCost;
                $orderDoubtModelAnother->now_cost = $newCost;
                $orderDoubtModelAnother->save();
                $orderIdAnother = $orderDoubtModelAnother->order_id;
                $orderAdjustAccountRecordModelAnother = OrderAdjustRecord::findOne(['doubt_id'=>$doubtId]);
                if($orderAdjustAccountRecordModelAnother){
                    throw new UserException('该笔订单有待处理疑义账单，请先处理后在操作',1007);
                }

                OrderAdjustRecord::insertChunk([
                    'order_id' => $orderIdAnother,
                    'doubt_id' => $doubtId,
                    'adjust_account_type' => ($newCost-$oldCost)>0?2:1,
                    'old_cost' => $oldCost,
                    'new_cost' => $newCost,
                    'solution' => $resolution,
                    'operator' => $this->userInfo['id'],
                ]);//写义调账记录
            }
            $trans->commit();

            return Json::success();
        } catch (UserException $exception) {
            $trans->rollBack();
            return $this->renderErrorJson($exception);
        } catch (\Exception $exception) {
            $trans->rollBack();
            throw new $exception;
            \Yii::info($exception->getMessage(),__METHOD__);
        }
    }

    /**
     * @return array|mixed
     * @throws \Exception
     */
    public function actionGiftCoupon()
    {
        $request = $this->getRequest();
        $orderId = (int)$request->post('orderId');
        $doubtId = (int)$request->post('doubtId'); //如果有此doubtId则说明来自于疑义定单处理
        //$giftAccount = $request->get('giftAccount', '13002913577');//cabRunner phone 定车人电话
        if(!empty($orderId)){
            $orderBoss =   OrderBoss::findOne($orderId);
            $giftAccount = $orderBoss->passenger_phone;
            $passenger_id =$orderBoss->passenger_info_id;
        }else{
            $orderIdFromDoubt = OrderDoubt::findOne($doubtId)->order_id;
            $orderBossFromDoubt =OrderBoss::findOne($orderIdFromDoubt);
            $giftAccount = $orderBossFromDoubt->passenger_phone;
            $passenger_id =$orderBossFromDoubt->passenger_info_id;

        }
        $orderId = empty($orderId)?$orderIdFromDoubt:$orderId;  //如果不传orderId 则从疑义定义中找出定单id


        $couponType  = $request->post('couponType');
        $solution    = trim($request->post('solution', 'test'));
        $trans = $this->beginTransaction();
        try {

            if(empty($doubtId)){ //来源订单详情,
                $doubtOrder = OrderDoubt::find()
                    ->where(['and',
                        ['=', 'order_id', $orderId],
                        ['<=', 'status', 3],
                    ])->one();
                if ($doubtOrder) {
                    throw new UserException('该笔订单有待处理疑义账单，请先处理后在操作', 1001);
                }

                $newOrderDoubtTable = OrderDoubt::insertChunk([
                    'order_id' => $orderId,
                    'appeal_number' => date('YmdHis').mt_rand(1000,9999),
                    'handle_type' => 3,//@todo convent to const 3发优惠券
                    'operators' => $this->userInfo['id'],//@todo 管理员id
                    'solution' => $solution,
                    //'adjust_type' => 3,
                    'operate_time' => date('Y-m-d H:i:s'),
                    'status'=>2
                ]); //如果没有疑义定单则插入一个疑义订单
                $doubtId  = $newOrderDoubtTable->id;
            }else{//来源 疑义定单处理
                $orderGiftCouponRecordFindTable = OrderGiftCouponRecord::findOne(['doubt_id'=>$doubtId]);
                if($orderGiftCouponRecordFindTable){
                    throw new UserException('该笔订单有待处理疑义账单，请先处理后在操作', 1002);
                } //已经发了一张优惠券还未处理
                $doubtOrderAnother = OrderDoubt::findOne($doubtId);
                $doubtId  = $doubtOrderAnother->id;
                $doubtOrderAnother->status = 2;
                $doubtOrderAnother->handle_type = 3;
                $doubtOrderAnother->save(false);
            }
            //$orderGiftCouponRecordModel = new OrderGiftCouponRecord();
            //$orderGiftCouponRecordModel->setAttributes(); //写入发券记录
            //var_dump($orderId,$doubtId,$passenger_id,$couponType,$this->userInfo['id'],$solution);exit;

            $orderGiftCouponRecordInsertSets = [
                    'order_id' => $orderId,
                    'doubt_id' => $doubtId,
                    'passenger_info_id'=>$passenger_id,
                    'coupon_id' => (int)$couponType,
                    'user_phone' => $giftAccount,
                    'solution' => $solution,
                    'operator_id' => (int)$this->userInfo['id'],//@todo 管理员id
                    'operator_time' => date('Y-m-d H:i:s')
            ];
            OrderGiftCouponRecord::insertChunk($orderGiftCouponRecordInsertSets);
            $trans->commit();
            return Json::success('');
        } catch (UserException $exception) {
            $trans->rollBack();
            return $this->renderErrorJson($exception);
        } catch (\Exception $exception) {
            $trans->rollBack();
            throw $exception;
            //\Yii::trace($exception->getMessage());
        }
    }

    /**
     * @return array|mixed
     * @throws \Throwable
     */

    public function actionAuditing()
    {
        $id          = $this->getRequest()->post('doubtId');  //疑义订单表id
        $operateType = (int)$this->getRequest()->post('operateType'); //操作类型1. 通过 2. 驳回
        $trans  = $this->beginTransaction();
        try {
            if(!in_array($operateType,[1,2]) || empty($id)){
                throw new UserException('Params error!',1000);
            }
            $orderDoubtModel = OrderDoubt::findOne($id);
            if (!$orderDoubtModel) {
                throw new UserException('Order not exist!',1001);
            }
            $orderId = $orderDoubtModel->order_id; // 订单id,
            $orderCancelStatus =  $this->_getOrderState($orderId);//取消订单的状态(取消费支付完否) 302完 303否
            $orderPayment = OrderPayment::findOne(['order_id'=>$orderId]);
            $orderTable = OrderBoss::findOne($orderId);  //疑义定单模型,
            if(!$orderTable){
                throw new UserException('Cannot find and Order ActiveRecord,please check params!',999);
            }
            $driverId = $orderTable->driver_id;
            $orderNumber = $orderTable->order_number;
            $passengerId = $orderTable->passenger_info_id;
            if(!in_array($orderTable->status,[7,8]) && $orderTable->status!=9 ){
                throw new UserException('行程未结束,不能调账!',1002);
            }
            if($orderTable->status == 9 && empty($orderPayment) ){
                throw new UserException('无偿取消的订单无法调账',1003);
            }
            $handleType = $orderDoubtModel->handle_type;
            if ($operateType == 1) { //通过
                $orderDoubtModel->status = 3;
                if($handleType == 3 || $handleType == 1){
                    $orderDoubtModel->status = 4;// 如果是发优惠券直接到完成状态 或者是退款  或者是状态未支付情况,审核后直接到完成状态
                }
                //$orderDoubtModel->adjust_content='200343';
                $difference = abs($orderDoubtModel->now_cost-$orderDoubtModel->old_cost); //差额

                if ($handleType == 1 || $handleType == 2) {
                    if($difference == 0){
                        throw new UserException('差额为0,无需调账',1004);
                    }
                    if($handleType ==1){ //价格调低,退款或者调低定单价格
                        if($orderTable->status == 6 ){
                            $orderPayment->final_price = $orderPayment->final_price - $difference;
                            $orderPayment->remain_price = $orderPayment->remain_price - $difference;
                            $orderPayment->tail_price = $orderPayment->tail_price - $difference;

                            $orderPayment->save();//在orderPayment已经生在价格记录 未支付情况下 只更新原价格
                            $orderTable->is_adjust = 2;
                            $orderTable->save(false);
                        }else if($orderTable->status == 7 || $orderCancelStatus == 303) { // 支付未完成状态
                            if($orderPayment->remain_price - $difference < 0){ //需要 退款
                                $orderPayment->final_price = $orderPayment->final_price -$difference;
                                //$orderPayment->remain_price -= $difference;
                                $refund = $difference- $orderPayment->remain_price;
                                $orderPayment->paid_price = $orderPayment->paid_price - $refund;
                                $orderPayment->remain_price = 0;
                                $orderPayment->tail_price = 0;
                                $orderPayment->save(false);
                                $refundResult = $this->_refund($passengerId,$orderId,$refund,$this->userInfo['id']);
                                if(!$refundResult){
                                    throw new UserException('调账退款失败,请联系管理员!',1005);
                                }
                                //写入退款单号
                                $orderDoubtModel->adjust_content = $refundResult;
                                //$orderDoubtModel->save(false);
                                //司机相应流水减少
                                /*if($driverId && $orderNumber){
                                    $this->_reduceDriverIncome($driverId,$orderNumber,$refund);
                                }*/
                                if($orderTable->status != 9){
                                    $orderTable->status = 8;
                                }

                                $orderTable->is_paid = 1;
                                $orderTable->is_adjust =2;
                                $orderTable->save(false);
                                BlacklistDashboard::relieveDevice($orderId);// 解禁未支付黑名单
                            }elseif($orderPayment->remain_price - $difference >0){ //不需要退款
                                $orderPayment->final_price -=$difference;
                                $orderPayment->remain_price-=$difference;
                                $orderPayment->tail_price-=$difference;
                                $orderPayment->save(false);
                                $orderTable->is_adjust = 2;
                                $orderTable->save(false);
                                $orderDoubtModel->status = 3; //调低后还仍需要支付
                                //$refund = abs($orderPayment->remain_price - $difference);
                            }elseif($orderPayment->remain_price - $difference == 0){ // 刚好调到已经支付的金额
                                $diff = $orderPayment->paid_price - $difference;
                                \Yii::info($diff,'diff');
                                $orderPayment->final_price = $orderPayment->final_price-$difference;
                                $orderPayment->remain_price=0;
                                $orderPayment->tail_price=0;
                                $orderPayment->save(false);
                                if($orderTable->status != 9){
                                    $orderTable->status = 8;
                                }
                                $orderTable->is_paid = 1;
                                $orderTable->is_adjust =2;
                                $orderTable->save(false);
                                BlacklistDashboard::relieveDevice($orderId); // 解禁未支付黑名单

                            }

                        }else if($orderTable->status == 8 || $orderCancelStatus == 302){  //如果已经支付完成,则走退款流程
                            $orderPayment->final_price = $orderPayment->final_price - $difference;
                            $orderPayment->paid_price = $orderPayment->paid_price - $difference; //退款时已经支付的减去差额
                            $orderPayment->save(false);
                            $orderTable->is_paid = 1;
                            $orderTable->is_adjust = 2;
                            $orderTable->save(false);
                            $refundResult = $this->_refund($passengerId,$orderId,$difference,$this->userInfo['id']);
                            if(!$refundResult){
                                throw new UserException('调账退款失败,请联系管理员!',1005);
                            }
                            //写入退款单号
                            $orderDoubtModel->adjust_content = $refundResult;
                            //$orderDoubtModel->save(false);
                            //司机相应流水减少
                            /*if($driverId && $orderNumber){
                                $this->_reduceDriverIncome($driverId,$orderNumber,$difference);
                            }*/
                        }
                    }else if($handleType ==2){//价格调高,补扣
                        $orderPayment1 = OrderPayment::findOne(['order_id'=>$orderId]);
                        $orderPayment1->final_price = $orderPayment1->final_price + $difference;
                        $orderPayment1->remain_price = $orderPayment1->remain_price + $difference;
                        $orderPayment1->replenish_price = $orderPayment1->replenish_price + $difference;
                        $payMust = $orderPayment1->tail_price + $difference;
                        $orderPayment1->save(); // 如果未付则只保存价格 7发起收款的状态
                        if($orderTable->status == 8){ //如果已经支付完成,则走支付流程
                            //$payResponse =   Wallet::orderPay($orderId, $passengerId, 0,$payMust,0,1);
                            //\Yii::info($payResponse);
                            //if(!$payResponse){  //支付失败时 订单回到未支付状态
                                $orderTable->is_adjust = 2; //调账完成乘客需要支付
                                $orderTable->status = 7;
                                $orderTable->is_paid = 0;
                                $orderTable->save(false);  //订单状态改变
                            //}
                        }else{
                            $orderTable->is_adjust = 2;
                            $orderTable->save(false);
                        }
                    }
                    $orderDoubtModel->save(false); //改变疑义定单状态
                    $orderAdjustTable = OrderAdjustRecord::findOne(['doubt_id'=>$id]);
                    if(isset($refundResult)){
                        $orderAdjustTable->charge_number = $refundResult;//todo 支付流水号
                        $orderAdjustTable->save(false);
                    }

                    $this->_sendOrderAdjustSuccessMsg($orderTable,$orderDoubtModel->old_cost,$orderDoubtModel->now_cost);
                    //正式调账操作
                    $trans->commit();

                    return Json::message('调账成功',CConstant::SUCCESS_CODE);
                } else {
                    //调用发券模块进行发券
                    $giftCouponRecord = OrderGiftCouponRecord::findOne(['doubt_id'=>$id]);
                    $couponType = $giftCouponRecord->coupon_id;
                    $receiveCouponPassengerId = $giftCouponRecord->passenger_info_id;
                    $couponData = Coupon::pushOneCoupon($receiveCouponPassengerId,$couponType);
                    if($couponData){
                        $orderDoubtModel->adjust_content = $couponData['coupon_name'];
                        $orderDoubtModel->save(false);
                        $giftCouponRecord->updateAttributes([
                            'coupon_name'=>$couponData['coupon_name'],
                            'user_coupon_id'=>$couponData['id'],
                            'coupon_amount'=>$couponData['reduction_amount'],
                            'coupon_expired_date'=>$couponData['available_text']
                        ]);
                    }else{
                        throw new UserException('优惠券发送失败!',1002);
                    }
                    $this->_sendGiftCouponSuccessMsg($orderTable,$couponData['reduction_amount']);//发优惠券推送沙消息
                    $trans->commit();
                    return Json::message('发券成功',CConstant::SUCCESS_CODE);
                }

            } else {//驳回时原先的处理作的预处理作废
                $orderDoubtModel->status = 1;
                $orderDoubtModel->save(false);
                if ($handleType == 1 || $handleType == 2) {
                    $orderAdjustModel = OrderAdjustRecord::find()->where(['doubt_id'=>$id])->one();
                    $orderAdjustModel->delete();
                } else {
                    $orderGiftCouponRecord = OrderGiftCouponRecord::find()->where(['doubt_id'=>$id])->one();
                    $orderGiftCouponRecord->delete();
                }
                $trans->commit();
                return Json::success('驳回成功');
            }

        } catch (UserException $exception) {
            $trans->rollBack();
            return $this->renderErrorJson($exception);
        } catch (\Throwable $exception) {
            $trans->rollBack();
            \Yii::error($exception->getMessage());
            throw $exception;
        }
    }

    /**
     * @param int $userId
     * @param int $orderId
     * @param int $price
     * @param int $createUser
     * @return bool
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    private function _refund($userId,$orderId,$price,$createUser)
    {
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.pay');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server['serverName']]);
        $postData = ['yid'=>$userId, 'orderId'=>$orderId, 'refundPrice'=>$price,'createUser'=>$createUser];
        $response = $httpClient->post($server['method']['refund'],$postData);
        \Yii::info($response);
        if(isset($response['code']) && $response['code'] !=0){
            return false;
        }
        return $response['data']['id'];
    }

    /**
     * @param $driverId
     * @param $orderNumber
     * @param $refund
     * @return bool
     */

    private function _reduceDriverIncome($driverId,$orderNumber,$refund)
    {
        $driverIncomeDetailTable = DriverIncomeDetail::findOne([
                'driver_info_id' => $driverId,
                'order_no' => $orderNumber]
        );
        if($driverIncomeDetailTable){
            $driverIncomeDetailTable->order_money = $driverIncomeDetailTable->order_money-$refund;
            return $driverIncomeDetailTable->save(false);
        }
        return false;
    }

    /**
     * 撤销
     *
     * @return mixed
     * @throws \Throwable
     */

    public function actionRevoke()
    {
        $id          = $this->getRequest()->post('doubtId');
        try{
            $doubtOrder = OrderDoubt::findOne($id);
            if(empty($doubtOrder)){
                throw new UserException('not exist record!');
            }
            if($doubtOrder->status == 5){
                throw new UserException('已经撤销过了');
            }
            $doubtOrder->status = 5;
            $doubtOrder->save(false);
            return Json::message('撤销成功',CConstant::SUCCESS_CODE);
        }catch (UserException $exception){
            return $this->renderErrorJson($exception);
        }catch (\Throwable $ex){
            \Yii::error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * @param \common\models\Order $order
     * @param $oldCost
     * @param $newCost
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    private function _sendOrderAdjustSuccessMsg(\common\models\Order $order,$oldCost,$newCost)
    {
        $passengerId = $order->passenger_info_id;
        $passengerPhone = Common::decryptCipherText($order->passenger_phone,true);
        $orderTime = empty($order->order_start_time)?$order->start_time:$order->order_start_time;
        $orderTimeToSecond = strtotime($orderTime);
        $couponMoney = OrderPayment::findOne(['order_id'=>$order->id])->coupon_reduce_price;
        $driveIncome = $newCost + $couponMoney;
        $driverId = $order->driver_id;
        $pushType = 3; //订单通知 类型
        $pushDataBase = ['sendId' =>'system', 'sendIdentity' => 1, 'messageType' => 1];
        if(!empty($passengerId)){ //如果乘客信息不为空,则发推送给乘客
            $messageBodyToPassenger    = [
                'content'=>date('m',$orderTimeToSecond).'月'.date('d',$orderTimeToSecond)
                    .'从'.$order->start_address.'到'.$order->end_address.'的订单金额已由'
                    .$oldCost.'元调整为'.$newCost.'元',
                'messageType'=>601,
                'title'=>'系统调账成功',
                'orderId'=>$order->id
            ];
            $pushDataToPassenger = ArrayHelper::merge([
                'acceptId'=>$passengerId,
                'messageBody'=>$messageBodyToPassenger,
                'title'=>'系统调账成功',
                'acceptIdentity'=>1,
            ],$pushDataBase);
            \Yii::info('发送乘客调账信息');
            \Yii::info($pushDataToPassenger);
            self::jpush($pushType,$pushDataToPassenger,1);
        }
        if(!empty($driverId)){
            $messageBodyToDriver    = [
                'content'=>sprintf('%s,乘客尾号%s从%s前往%s的订单,系统已调账处理,调整后的金额为%s,请在费用详情中查看。',
                    Common::convertTimeToNaturalLanguage($orderTime),
                    Common::getHidePhone($passengerPhone),
                    $order->start_address,
                    $order->end_address,
                    $driveIncome
                ),
                'messageType'=>601,
                'title'=>'系统调账成功',
                'orderId'=>$order->id
            ];
            $pushDataToDriver= ArrayHelper::merge([
                'acceptId'=>$driverId,
                'messageBody'=>$messageBodyToDriver,
                'title'=>'系统调账成功',
                'acceptIdentity'=>2,
            ],$pushDataBase);
            \Yii::info('发送司机调账');
            \Yii::info($pushDataToDriver);
            self::jpush($pushType,$pushDataToDriver,1);
        }
    }

    /**
     * @param \common\models\Order $order
     * @param $couponMoney
     */


    private function _sendGiftCouponSuccessMsg(\common\models\Order $order,$couponMoney)
    {
        $orderTime = empty($order->order_start_time)?$order->start_time:$order->order_start_time;
        $orderTimeToSecond = strtotime($orderTime);
        $passengerId = $order->passenger_info_id;
        $messageBodyToPassenger    = [
            'content'=>date('m',$orderTimeToSecond).'月'.date('d',$orderTimeToSecond)
                .'从'.$order->start_address.'到'.$order->end_address.'的订单由客服赠送您一张'.$couponMoney.
                '元的'.'优惠券,祝您旅途愉快!',
            'messageType'=>801,// 活动消息
            'title'=>'发送优惠券成功',
            'orderId'=>$order->id,
        ];
        $pushDataToPassenger = ArrayHelper::merge([
            'acceptId'=>$passengerId,
            'messageBody'=>$messageBodyToPassenger,
            'title'=>'发送优惠券成功',
            'acceptIdentity'=>1,
        ],$this->_pushDataBase);
        \Yii::info('发送优惠券信息');
        \Yii::info($pushDataToPassenger);

        self::jpush($this->_pushType,$pushDataToPassenger,1);
    }

    public function actionTest()
    {

    }
}