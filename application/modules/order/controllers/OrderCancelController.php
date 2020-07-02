<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/24
 * Time: 11:02
 */

namespace application\modules\order\controllers;

use application\modules\order\models\OrderBoss;
use application\modules\order\models\OrderCancelRecord;
use common\controllers\BaseController;
use common\logic\CouponTrait;
use common\logic\finance\Wallet;
use common\logic\order\UnfreezeBalanceTrait;
use common\models\Order;
use common\models\OrderRulePrice;
use common\models\PassengerInfo;
use common\services\traits\PublicMethodTrait;
use common\services\YesinCarHttpClient;
use common\util\Common;
use common\util\Json;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
class OrderCancelController extends BossBaseController
{
    use PublicMethodTrait,CouponTrait,UnfreezeBalanceTrait;
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
     * @throws \Throwable
     */
    public function actionCancel()
    {
        $request = $this->getRequest();
        $orderId = intval($request->post('orderId'));
        $isCharge = $request->post('isCharge');//乘客是否有责任
        $reasonType = intval($request->post('reasonType'));
        $reasonText = trim($request->post('reasonText',''));
        $trans  = $this->beginTransaction();
        try{
            if(empty($orderId) || is_null($isCharge) || empty($reasonType)){
                throw new UserException('Params error!',1001);
            }
            $orderModel = OrderBoss::findOne($orderId);
            if(!$orderModel) {
                throw new UserException('no Order!',1002);
            }
            if($orderModel->status > 4 && $orderModel->status<8){
                throw new UserException('服务中的订单能无法取消!',1003);
            }
            if($orderModel->status == 8){
                throw new UserException('订单已经完成,无法取消!',1004);
            }
            if($orderModel->is_cancel == 1 || $orderModel->status == 9){
                 throw new UserException('定单已经取消过了',1005);
            }
            $cancelCost = 0;
            if($isCharge){ //调用钱包扣费
                $orderRulePrice = OrderRulePrice::findOne(['order_id' => $orderId, 'category' => OrderRulePrice::PRICE_TYPE_FORECAST]);
                if(!$orderRulePrice){
                    throw new UserException('Cannot find order Price,exist dirty data!',1006);
                }
                if($orderRulePrice->service_type_id == 1)
                {
                    $cancelCost = (int)$orderRulePrice->base_price;
                }else{
                    $cancelCost = (int)$orderRulePrice->lowest_price;
                }

                $payResponse =   Wallet::orderPay($orderId, $orderModel->passenger_info_id, $cancelCost,0,0,2);
                \Yii::info($payResponse);
                if($payResponse ===true){ // 如果支付完成费用,则表示支付完成
                    $orderModel->is_paid = 1;
                }
            }else{
                $this->unfreezeBalance($orderModel->passenger_info_id,$orderId);
            }

            $orderModel->status = Order::STATUS_CANCEL;
            $orderModel->is_cancel = Order::IS_CANCEL;
            if(!$orderModel->save(false)){
                throw new UserException($orderModel->getFirstError(),1007);
            }
            $orderCancelOrderModel = new OrderCancelRecord;
            $orderCancelOrderModel->setAttributes([
                'order_id'=>$orderId,
                'is_charge'=>$isCharge,
                'reason_type'=>$reasonType,
                'reason_text'=>$reasonText,
                'cancel_cost'=>$cancelCost,
                'operator'=>$this->userInfo['id'],
                'operator_type'=>OrderCancelRecord::OPERATOR_TYPE_SERVICE,
            ]);
            if(!$orderCancelOrderModel->save()){
                throw new UserException($orderCancelOrderModel->getFirstError(),1008);
            }
            $this->unlockOrderCoupon($orderId); //解绑优惠券  on 2018-11-1

            $this->_sendCancelOrderMsg($orderModel,301,$isCharge,$cancelCost);
            $trans->commit();

            return Json::success();

        }catch (UserException $exception){
            $trans->rollBack();
            return $this->renderErrorJson($exception);
        }catch (\Throwable $exception){
            $trans->rollBack();
            \Yii::trace($exception->getMessage());
            throw $exception;
        }


    }


    /**
     * @param Order $orderTable
     * @param $signal
     * @param int $hasCharge
     * @param int $cancelCost
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    private function _sendCancelOrderMsg(\common\models\Order $orderTable,$signal,$hasCharge = 0,$cancelCost = 0)
    {
        $pushType = 3;
        $passengerPhone = Common::decryptCipherText($orderTable->passenger_phone,true);
        if(empty($passengerPhone)){
            throw new UserException('Cannot find passenger phone',2001);
        }
        $orderCancelTitle = '订单取消';
        $pushDataBase = ['sendId'=>'system', 'messageType'=>1, 'sendIdentity'=>1];
        /**时间XX时XX分，从XX到XX的订单已取消*/
        /**已到达上车地点，用户取消订单，已产生行程违约金*/
        $orderTime = $orderTable->order_start_time?$orderTable->order_start_time:$orderTable->start_time;
        $startAddress  = $orderTable->start_address;
        $endAddress = $orderTable->end_address;
        if($hasCharge == 0 ){
            //用户无责任，
            $messageContentToPassenger =[
                'content'=>sprintf("%s从%s到%s的订单已由客服人员协助取消。",
                    Common::convertTimeToNaturalLanguage($orderTime),
                    $startAddress,
                    $endAddress
                ),
                'messageType'=>602,
                'title'=>$orderCancelTitle,
                'orderId'=>$orderTable->id
            ];
            $pushDataToPassenger = ArrayHelper::merge([
                'acceptId'=>$orderTable->passenger_info_id,
                'acceptIdentity'=>1,
                'messageBody'=>$messageContentToPassenger,
                'title'=>'订单取消'
            ],$pushDataBase);
            self::jpush($pushType,$pushDataToPassenger,1);   ////////以上级乘客发推送
            if(!empty($orderTable->driver_id)){//如果有司机则给司机发推送
                $messageContentToDriver = [
                    'content'=>sprintf("时间%s,从%s到%s的订单已经取消",
                        Common::convertTimeToNaturalLanguage($orderTime),
                        $startAddress,
                        $endAddress
                    ),
                    'messageType'=>602,   //@todo 此状态是临时使用,为让司机能接到通知,需要与手机司机端进行对接
                    'title'=>$orderCancelTitle,
                    'orderId'=>$orderTable->id,
                ];
                $pushDataToDriver = ArrayHelper::merge([
                    'acceptId'=>$orderTable->driver_id,
                    'acceptIdentity'=>2,
                    'messageBody'=>$messageContentToDriver,
                    'title'=>$orderCancelTitle,
                ],$pushDataBase);
                self::jpush($pushType,$pushDataToDriver,1);   ////////以上向司机发推送
            }

            /*********************************给订车人和用车人发短信**********************************/
            //$smsTemplateToPassengerSelf = '尊敬逸品出行用户,你用车时间为****年**月**日的订单已被取消,如有疑问,可致电
            //客服热线0571-86908097';//换成模板id   SMS_145295103 无责取消通知订车人
            //$smsTemplateToPassengerSelf = 'SMS_145295103';
            $smsTemplateToPassengerSelf = 'HX_0009';

            $smsToPassengerSelfData = [
                'time'=>Common::convertTimeToNaturalLanguage($orderTable->start_time),
                'order_time'=>Common::convertTimeToNaturalLanguage($orderTable->order_start_time),
            ];
            Common::sendMessageNew($passengerPhone,$smsTemplateToPassengerSelf,$smsToPassengerSelfData);  //给订车人发短信
            if (!empty($orderTable->order_type) && $orderTable->order_type == 2) { //给他人叫车 给他人发短信
                $carManPhone = Common::decryptCipherText($orderTable->other_phone, true);
                if (!empty($carManPhone)) { //给用车人发送短信
                    $passengerNickName = PassengerInfo::fetchFieldBy(['id' => $orderTable->passenger_info_id], 'passenger_name');
                    //$smsTemplateToCarMan = '[逸品出行]尊敬的逸品出行用户，用户{昵称}为您安排用车时间为****年**月**日的订单已被取消,如有
                    //疑问可致电客服热线0571-86908097';//换成模板id  SMS_145295100 //无责通知乘车人短信模板
                    //$smsTemplateToCarMan = 'SMS_145295100';
                    $smsTemplateToCarMan = 'HX_0008';
                    $smsToCarManData     = [
                        'passenger_name' => $passengerNickName,
                        'time' => Common::convertTimeToNaturalLanguage($orderTime),
                        'service_man_fixed_phone'=>\Yii::$app->params['serviceManFixedPhone'],
                    ];
                    Common::sendMessageNew($carManPhone, $smsTemplateToCarMan, $smsToCarManData);
                }
            }

        }else{
            //向乘客端推送
            if(!empty($orderTable->driver_arrived_time)){
                $beyondDriverArrivedMinutes = time()-strtotime($orderTable->driver_arrived_time);
                $beyondDriverArrivedMinutes = round($beyondDriverArrivedMinutes/60);
            }else{
                $beyondDriverArrivedMinutes = 0;
            }
            $messageContentToPassengerHasCharge =[
                'content'=>sprintf("由于司机到达上车地后%s分钟未联系到您,订单自动取消且产生取费,点击查看详情",$beyondDriverArrivedMinutes),
                'messageType'=>602,
                'title'=>$orderCancelTitle,
                'orderId'=>$orderTable->id
            ];
            $pushDataToPassengerHasCharge = ArrayHelper::merge([
                'acceptId'=>$orderTable->passenger_info_id,
                'acceptIdentity'=>1,
                'messageBody'=>$messageContentToPassengerHasCharge,
                'title'=>$orderCancelTitle,
            ],$pushDataBase);
            self::jpush($pushType,$pushDataToPassengerHasCharge,1);   ///以上级乘客发推送有责

            if(!empty($orderTable->driver_id)){//如果有司机则给司机发推送
                $messageContentToDriverHasCharge = [
                    'content'=>'已到达上车地点，用户取消订单，已产生行程违约金。',
                    'messageType'=>602,
                    'title'=>$orderCancelTitle,
                    'orderId'=>$orderTable->id,
                ];
                $pushDataToDriverHasCharge = ArrayHelper::merge([
                    'acceptId'=>$orderTable->driver_id,
                    'acceptIdentity'=>2,
                    'messageBody'=>$messageContentToDriverHasCharge,
                    'title'=>$orderCancelTitle,
                ],$pushDataBase);
                self::jpush($pushType,$pushDataToDriverHasCharge,1);   ////////以上向司机发推送 有责
            }

            /**********************************以下给乘客发有责短信 *****************************/

            //$smsTemplateToPassengerSelfHasCharge = '[逸品出行]尊敬逸品出行用户,****年**月**日由于您取消**时**分的订单时,
            //专业司机已经到达出发地,根据服务协议扣取**元取消费,详情请联系客服.';//换成模板id SMS_145290150 //有责取消给订车人发短信模板
            //$smsTemplateToPassengerSelfHasCharge = 'SMS_145290150';
            $smsTemplateToPassengerSelfHasCharge = 'HX_0039';
            $smsToPassengerSelfDataHasCharge = [
                'time'=>Common::convertTimeToNaturalLanguage($orderTable->start_time),  //订车时间
                'order_time'=>Common::convertTimeToNaturalLanguage($orderTable->order_start_time), //用车时间
                'money'=>$cancelCost //取消费用
            ];
            Common::sendMessageNew($passengerPhone,$smsTemplateToPassengerSelfHasCharge,$smsToPassengerSelfDataHasCharge);
        }
    }

}