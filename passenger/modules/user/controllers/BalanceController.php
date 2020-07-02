<?php
namespace passenger\modules\user\controllers;

use common\controllers\ClientBaseController;
use common\logic\FeeTrait;
use common\logic\finance\Wallet;
use common\models\Order;
use common\models\PassengerWallet;
use common\models\PassengerWalletRecord;
use common\models\RechargePrice;
use common\models\UserCoupon;
use common\services\YesinCarHttpClient;
use common\util\Common;
use common\util\Json;
use yii;
use yii\helpers\ArrayHelper;

//use common\util\Cache;

/**
 * 用户账户相关获取
 */
class BalanceController extends ClientBaseController
{
    use FeeTrait;

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 支付宝/支付
     */
    public function actionAlipayPayment(){
        return $this->payment("alipay");
    }

    /**
     * 微信/支付
     */
    public function actionWeixinPayment(){
        return $this->payment("weixinPay");
    }


    public function payment($payname='alipay'){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['orderId']       = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $requestData['capital']       = isset($requestData['capital']) ? trim($requestData['capital']) : '';
        $requestData['giveFee']       = isset($requestData['giveFee']) ? trim($requestData['giveFee']) : 0;
        $requestData['source']        = isset($requestData['source'])  ? trim($requestData['source'])  : '';
        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }

        if(!empty($requestData['orderId'])){
            $chk = Order::find()->select(["passenger_info_id", "is_adjust"])->where(["id"=>$requestData['orderId']])->asArray()->one();
            if($chk['passenger_info_id'] != $this->userInfo['id']){
                return Json::message("没有权限操作订单");
            }
            if($chk['is_adjust']==1){
                return Json::message("订单正在调帐中，请稍后支付", 2);
            }
        }

        if(empty($requestData['source'])){
            return Json::message("Source 错误");
        }
        if(empty($requestData['capital'])){
            return Json::message("Capital 错误");
        }
        $capital = $requestData['capital'];
        $_giveFee = $this->getGiveFee($this->userInfo['id'], $capital);
        try{
            $server     = ArrayHelper::getValue(\Yii::$app->params,'api.pay.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.pay.method.'.$payname);
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $_data = [
                "yid"       =>  $this->userInfo['id'],
                "capital"   =>  $capital,
                "giveFee"   =>  $_giveFee,
                "source"    =>  $requestData['source'],
                "rechargeType"=>    1,
            ];
            $data = $httpClient->post($methodPath, $_data);
            \Yii::info([$_data,$data], "payment");
            if($data['code']!=0){
                throw new yii\base\Exception($data['message'],100010);
            }
            if($_giveFee>0){
                $outTradeNo = "";
                if($payname=='weixinPay'){
                    $outTradeNo = $data['data']['outTradeNo'];
                }
                if($payname=='alipay'){
                    $urldecode = urldecode($data['data']);
                    preg_match("/\"out_trade_no\":\"(.*?)\"/", $urldecode, $h);
                    if(isset($h[1])){
                        $outTradeNo = $h[1];
                    }
                }
                $this->markGiveFee($this->userInfo['id'], $outTradeNo);
            }
            return $this->asJson($data);
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }

    }

    /**
     *
     * 微信，充值查询回掉
     *
     */
    public function actionWeixinPayResult(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['tradeNo'] = isset($requestData['tradeNo']) ? trim($requestData['tradeNo']) : '';
        $requestData['orderId'] = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        if(empty($requestData['tradeNo'])){
            return Json::message("tradeNo error");
        }
        if(empty($requestData['orderId'])){
            return Json::message("orderId error");
        }

        try{
            $server     = ArrayHelper::getValue(\Yii::$app->params,'api.pay.serverName');
            $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.pay.method.weixinPayResult');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
            $_data = [
                "outTradeNo"       =>  $requestData['tradeNo'],
                "orderId"          =>  $requestData['orderId']
            ];
            $data = $httpClient->get($methodPath, $_data, null);
            \yii::info([$_data,$data], "WeixinPayResult");
            if(isset($data['code']) && $data['code']==0){
                if(isset($data['data']['status'])){
                    if($data['data']['status']==0){
                        return Json::success();
                    }
                }
            }
            return Json::message("pay error");
        }catch (yii\base\Exception $e){
            $this->renderJson($e);
        }
    }


    
    /**
     * 获取用户钱包信息
     * 余额/赠额（冻结），优惠券个数
     * @return [type] [description]
     */
    public function actionGetWalletInfo(){
		//$request = $this->getRequest();
        //$requestData = $request->post();
        if (empty($this->userInfo['id'])) {
            return Json::message("Identity error");
        }
        $query = PassengerWallet::find()->select(['passenger_info_id', 'capital', 'give_fee', 'freeze_capital', 'freeze_give_fee']);
        $info = $query->where(['passenger_info_id' => $this->userInfo['id']])->limit(1)->asArray()->one();
        $coupon_num = (new UserCoupon())->getCouponQuery($this->userInfo['id'],'normal')->count();
        if(!empty($info)){
            $info['coupons'] = $coupon_num;
            return Json::success(Common::key2lowerCamel($info));
        }else{
            return Json::message("No data available.");
        }
    }

    /**
     * 获取用户余额明细
     * @return array
     */
    public function actionGetRecord(){
        //$request = $this->getRequest();
        //$requestData = $request->post();
        $requestData=[];
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passenger_info_id'] = $this->userInfo['id'];
        }
        $condition = [];
        $condition['passenger_info_id'] = $requestData['passenger_info_id'];
        $condition['pay_status'] = 1;//已支付
        $condition['trade_type'] = [1,2,3,4,5,6];//流水类型
        $select=[
            'id',
            'pay_capital', 
            'pay_give_fee', 
            'refund_capital',
            'refund_give_fee',
            'pay_type',
            'trade_type',
            'pay_time',
            'passenger_info_id',
            'order_id',
            'description AS frontRecordType',
        ];
        $pwr = PassengerWalletRecord::find()->select($select)->where($condition)->asArray()->all();
        if(empty($pwr)){
            return Json::success(['list' => [],'pageInfo' => ['page' => 1,'pageCount' => 0,'pageSize' => 0,'total' => 0]]);
        }
        foreach ($pwr as $k => $v){
            if($v['trade_type']==4){
                if($v['pay_capital']==0 && $v['pay_give_fee']==0){
                    unset($pwr[$k]);
                    continue;
                }
                $checkThaw = PassengerWalletRecord::checkThaw($v['passenger_info_id'], $v['order_id']);
                if($checkThaw){
                    unset($pwr[$k]);
                    continue;
                }
            }
        }
        $pwr = array_values($pwr);
        if(empty($pwr)){
            return Json::success(['list' => [],'pageInfo' => ['page' => 1,'pageCount' => 0,'pageSize' => 0,'total' => 0]]);
        }
        $pwr = ArrayHelper::getColumn($pwr, 'id');
        $data = PassengerWalletRecord::getFlowRecord(['recordId'=>$pwr], $select);
        if(!empty($data['list'])){
            foreach ($data['list'] as $k => $v){
                $_temp = Wallet::getRecordPrice($v);//前端流水价格
                unset($data['list'][$k]['pay_capital'],
                    $data['list'][$k]['pay_give_fee'],
                    $data['list'][$k]['refund_capital'],
                    $data['list'][$k]['refund_give_fee'],
                    $data['list'][$k]['passenger_info_id'],
                    $data['list'][$k]['order_id']);
                $data['list'][$k]['pay_capital']  = (string)$_temp['pay_capital'];
                $data['list'][$k]['pay_give_fee'] = (string)$_temp['pay_give_fee'];
            }
            $data['list'] = array_values($data['list']);
        }
        return Json::success($data);
    }


    /**
     * 获取充值和赠费列表
     * @return array
     */
    public function actionGetPaymentList()
    {
        $resData = RechargePrice::find()
            ->where(['is_deleted' => 0])
            ->select(['id AS paymentId', 'amount', 'reward', 'desc'])
            ->orderBy('amount DESC, id DESC')
            ->asArray()
            ->all();
        foreach ($resData as &$item) {
            $giveFee = $this->getGiveFee($this->userInfo['id'], $item['amount']);
            $item['reward'] = (string)floatval(strval(number_format($giveFee, 2, '.', '')));
            $item['amount'] = (string)floatval($item['amount']);
        }
        // 返回数据
        return Json::success($resData);
    }










}