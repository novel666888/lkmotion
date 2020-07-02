<?php
namespace application\modules\crm\controllers;

use application\controllers\BossBaseController;
use common\events\PassengerEvent;
use common\logic\finance\Wallet;
use common\logic\sysuser\UserLogic;
use common\models\Order;
use common\models\PassengerWalletRecord;
use common\services\traits\PublicMethodTrait;
use common\util\Json;
use yii\base\UserException;
use common\logic\LogicTrait;

/**
 * 财务相关
 */
class BalanceController extends BossBaseController
{
    use PublicMethodTrait;
    use LogicTrait;
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }

    /**
     * 后台充值
     */
    public function actionRecharge(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['capital']     = isset($requestData['capital']) ? trim($requestData['capital']) : 0;
        $requestData['giveFee']     = isset($requestData['giveFee']) ? trim($requestData['giveFee']) : 0;
        $requestData['passengerId'] = isset($requestData['passengerId']) ? trim($requestData['passengerId']) : '';
        if(empty($requestData['passengerId'])){
            return Json::message("Parameter passengerId error");
        }
        if(empty($requestData['capital']) && empty($requestData['giveFee'])){
            return Json::message("capital,giveFee 0 error");
        }
        if(!empty($requestData['capital'])){
            if(!is_numeric($requestData['capital'])){
                return Json::message("capital no numeric");
            }
        }
        if(!empty($requestData['giveFee'])){
            if(!is_numeric($requestData['giveFee'])){
                return Json::message("giveFee no numeric");
            }
        }
        if(empty($requestData['giveFee'])){
            $requestData['giveFee']=0;
        }
        if(empty($requestData['capital'])){
            $requestData['capital']=0;
        }
        $sysId=0;
        if(!empty($this->userInfo['id'])){
            $sysId = $this->userInfo['id'];
            //$UserLogic = new UserLogic();
            //$rs = $UserLogic->info(['id'=>$sysId]);
            //if(isset($rs['username'])){
                //$adminName = $rs['username'];
            //}
        }
        try{
            $rs = Wallet::rechargeBoss($requestData['passengerId'], $requestData['capital'], $requestData['giveFee'], $sysId);
            if($rs){
                (new PassengerEvent())->charge($requestData['passengerId']);
                return Json::success();
            }else{
                return Json::message("rechargeBoss error");
            }
        }catch (UserException $exception){
            return $this->renderErrorJson($exception);
        }catch(\yii\httpclient\Exception $exception){
            return $exception->getMessage();
        }
    }

    /**
     * 获取指定用户的充值/消费记录列表
     * @return [type] [description]
     */
    public function actionFlowRecord(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['tradeType']   = isset($requestData['tradeType']) ? trim($requestData['tradeType']) : "";
        $requestData['orderId']     = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $requestData['recordId']    = isset($requestData['recordId']) ? trim($requestData['recordId']) : '';
        $requestData['passengerId'] = isset($requestData['passengerId']) ? trim($requestData['passengerId']) : '';
        if(empty($requestData['passengerId']) || !in_array($requestData['tradeType'],[1,2,3])){
            return Json::message("Parameter error");
        }
        //将order_number转换order_id
        if(!empty($requestData['orderId'])){
            $_on = Order::find()->select(['id'])->where(['order_number'=>$requestData['orderId']])->asArray()->one();
            if(isset($_on['id'])){
                $requestData['orderId'] = $_on['id'];
            }
        }
        if($requestData['tradeType']==1) {
            $requestData['tradeType'] = [1];
        }elseif($requestData['tradeType']==2) {
            $requestData['tradeType'] = [2, 5, 6];
        }elseif($requestData['tradeType']==3) {
            $requestData['tradeType'] = [3];
        }else{
            $requestData['tradeType'] = [1];
        }
        $condition=[];
        $condition=$requestData;
        $condition['payStatus']=1;//已支付
        $field=[
            'id', //充值流水号
            'passenger_info_id AS passengerId',
            'trade_type AS event',
            'pay_capital AS payCapital',//本金
            'pay_give_fee AS payGiveFee',//赠送金额
            'refund_capital AS refundCapital',//退款本金
            'refund_give_fee AS refundGiveFee',//退款本金
            'recharge_discount AS rechargeDiscount',//充值折扣
            'IFNULL(order_id,"") AS orderId', //订单ID
            'pay_time AS payTime',//支付时间
            'transaction_id AS transactionId',//第三方支付ID
            'pay_type AS payType',//充值渠道
            'IFNULL(create_user,"") AS createUser',//操作人
            'create_time AS createTime',//操作时间
        ];
        $data = PassengerWalletRecord::getFlowRecord($condition, $field);
        if(!empty($data)){
            foreach ($data['list'] as $k => &$v){
                if(!empty($v['orderId'])){
                    $rs = Order::fetchFieldBy($v['orderId'], 'order_number');
                    if(!empty($rs)){
                        $v['orderNum'] = $rs;
                    }else{
                        $v['orderNum'] = "";
                    }
                }
                if($v['payType']==3 && $v['event']==1){//后台充值
                    $v['rechargeDiscount']=0;//强制变为0
                }
            }
            LogicTrait::fillUserInfo($data['list'],'createUser');
        }
        return Json::success($data);
    }






}
