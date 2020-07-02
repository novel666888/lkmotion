<?php
namespace common\logic\finance;

use common\util\Json;
use common\util\Common;
use common\models\OrderPayment;
use common\models\Order;
use common\models\OrderDoubt;
use common\models\OrderAdjustRecord;

use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
use yii\base\UserException;
/**
 *
 * 个人财务管理。支付相关，钱包相关
 *
 */
class Wallet{

    public static $payResultMes = "";

    /**
     * 取消订单，预先扣除账户余额
     * @param $orderId 订单ID
     * @param $yid 用户ID
     * @param $price 取消订单产生的取消费
     */
    public static function orderPayCancel($orderId, $yid, $price){
        if(empty($orderId) || empty($yid) || empty($price)){
            \Yii::info([$orderId,$yid,$price], "orderPayCancel_1");
            return false;
        }
        if(self::addPayment($orderId, $price)==false){
            \Yii::info("addPayment error", "orderPayCancel_2");
            return false;
        }
        $rp = self::orderPayPart($orderId, $yid, $price);
        if($rp===false){
            \Yii::info("orderPayPart error", "orderPayCancel_3");
            return false;
        }

        $pay_time = date("Y-m-d H:i:s", time());
        $OrderPayment_model = new OrderPayment();
        $rrss = $OrderPayment_model::find()->where(["order_id"=>$orderId])->select(["paid_price","remain_price"])->asArray()->one();
        if(empty($rrss)){
            \Yii::info($rrss, "orderPayCancel_4");
            return false;
        }
        $yzf = sprintf("%.2f", $rrss['paid_price']+($rrss['remain_price']-$rp));
        $gengxin = [
            "pay_type"          =>  1,//账户余额
            "paid_price"        =>  $yzf,
            "remain_price"      =>  $rp,
            "pay_time"          =>  $pay_time,
            "tail_price"        =>  $rp,
            "replenish_price"   =>  0,
        ];
        $jg = $OrderPayment_model->updateAll($gengxin, ["order_id"=>$orderId]);
        if($jg===false){
            \Yii::info([$rp,$jg,$orderId,$gengxin], "orderPayCancel_5");
            return false;
        }
        //判断是否需要继续支付，还是支付完成更新order表为完成状态
        if($rp>0){
            \Yii::info([$rp], "orderPayCancel_6");
            return $rp;
        }
        else{
            \Yii::info("success!", "orderPayCancel_7");
            //订单取消费扣费，已在之前修改订单状态
            return true;
        }
    }

    /**
     * 订单扣款，完全扣款
     * 场景：订单支付、进入订单详情默认扣款
     * @param $orderId 订单ID
     * @param $yid 用户ID
     * @param $tailPrice 尾款
     * @param $replenishPrice 补扣、调账金额
     */
    public static function orderPayAll($orderId, $yid, $tailPrice, $replenishPrice){
        if(empty($orderId) || empty($yid)){
            \Yii::info([$orderId,$yid], "orderPayAll_1");
            return false;
        }
        $rp = self::orderPayFull($orderId, $yid, $tailPrice, $replenishPrice);
        if($rp===false){
            \Yii::info("orderPayFull error", "orderPayAll_2");
            return false;
        }

        $pay_time = date("Y-m-d H:i:s", time());
        $OrderPayment_model = new OrderPayment();
        $rrss = $OrderPayment_model::find()->where(["order_id"=>$orderId])->select(["paid_price","remain_price"])->asArray()->one();
        if(empty($rrss)){
            \Yii::info($rrss, "orderPayAll_3");
            return false;
        }
        $yzf = sprintf("%.2f", $rrss['paid_price']+($rrss['remain_price']-$rp));
        $gengxin = [
            "pay_type"          =>  1,//账户余额
            "paid_price"        =>  $yzf,
            "remain_price"      =>  $rp,
            "pay_time"          =>  $pay_time,
            "tail_price"        =>  0,
            "replenish_price"   =>  0,
        ];
        $jg = $OrderPayment_model->updateAll($gengxin, ["order_id"=>$orderId]);
        if($jg===false){
            \Yii::info([$rp,$jg,$orderId,$gengxin], "orderPayAll_4");
            return false;
        }

        if($rp>0){
            //这种情况是不存在的
            \Yii::info([$rp], "orderPayAll_5");
            return $rp;
        }
        else{
            //完全扣款
            if(self::updateOrderStatus($orderId)==false){
                \Yii::info("updateOrderStatus error", "orderPayAll_6");
                return false;
            }
            if(self::updateDoubtStatus($orderId)==false){
                \Yii::info("updateDoubtStatus error", "orderPayAll_7");
                return false;
            }
            \Yii::info("success!", "orderPayAll_8");
            return true;
        }
    }

    /**
     * 订单扣款，更新payment数据
     * $orderId 订单ID
     * $yid 乘客ID
     * $price 订单需要支付金额
     * $tailPrice 尾款金额
     * $replenishPrice 扣款金额
     * 类型id 1订单扣款，2取消订单
     * @return [type] [description]
     */
    public static function orderPay($orderId, $yid, $price, $tailPrice=0, $replenishPrice=0, $type=1){
        if(empty($orderId) || empty($yid)){
            \Yii::info([$orderId,$yid], "orderPay_1");
            return false;
        }
        if($type==1){
            $rp = self::orderPayFull($orderId, $yid, $tailPrice, $replenishPrice);
            if($rp===false){
                \Yii::info("orderPayFull error", "orderPay_2");
                return false;
            }
        }
        if($type==2){
            if(self::addPayment($orderId, $price)==false){
                \Yii::info("addPayment error", "orderPay_3");
                return false;
            }
            $rp = self::orderPayPart($orderId, $yid, $price);
            if($rp===false){
                \Yii::info("orderPayPart error", "orderPay_4");
                return false;
            }
        }

        $pay_time = date("Y-m-d H:i:s", time());
        $OrderPayment_model = new OrderPayment();
        $rrss = $OrderPayment_model::find()->where(["order_id"=>$orderId])->select(["paid_price","remain_price"])->asArray()->one();
        if(empty($rrss)){
            \Yii::info($rrss, "orderPay_5");
            return false;
        }
        $yzf = sprintf("%.2f", $rrss['paid_price']+($rrss['remain_price']-$rp));
        //更新payment表
        if($type==1){
            $gengxin = [
                "pay_type"          =>  1,//账户余额
                "paid_price"        =>  $yzf,
                "remain_price"      =>  $rp,
                "pay_time"          =>  $pay_time,
                "tail_price"        =>  0,
                "replenish_price"   =>  0,
            ];
        }
        if($type==2){
            $gengxin = [
                "pay_type"          =>  1,//账户余额
                "paid_price"        =>  $yzf,
                "remain_price"      =>  $rp,
                "pay_time"          =>  $pay_time,
                "tail_price"        =>  $rp,
                "replenish_price"   =>  0,
            ];
        }
        $jg = $OrderPayment_model->updateAll($gengxin, ["order_id"=>$orderId]);
        if($jg===false){
            \Yii::info([$rp,$jg,$orderId,$gengxin], "orderPay_6");
            //remain_price update error
            return false;
        }

        //判断是否需要继续支付，还是支付完成更新order表为完成状态
        if($rp>0){
            \Yii::info([$rp], "orderPay_7");
            return $rp;
        }
        else{
            if($type==1){
                //完全扣款
                $is_cancel = order::find()->where(['id'=>$orderId])->select(['is_cancel'])->asArray()->one();
                if(isset($is_cancel['is_cancel']) && $is_cancel['is_cancel']==1){
                    $zt = 9;//取消订单完全付款
                }else{
                    $zt = 8;//正常完全付款
                }
                if(self::updateOrderStatus($orderId, $zt)==false){
                    \Yii::info("updateOrderStatus error", "orderPay_8");
                    return false;
                }
                if(self::updateDoubtStatus($orderId)==false){
                    \Yii::info("updateDoubtStatus error", "orderPay_9");
                    return false;
                }
                self::updateOrderPayType($orderId);
                \Yii::info("success!", "orderPay_10");
                return true;
            }else{
                \Yii::info("success!", "orderPay_11");
                //取消订单扣费，已在之前修改订单状态
                return true;
            }
        }
    }

    /**
     * @var string 支付后的流水号（完全支付/部分支付,java返回）
     */
    public static $flowNumber="";

    /**
     * 订单完全支付
     * @return false/int
     */
    public static function orderPayFull($orderId, $yid, $tailPrice, $replenishPrice){
        if(empty($tailPrice) && empty($replenishPrice)){
            \Yii::info([$tailPrice,$replenishPrice], "orderPayFull_1");
            return false;
        }
        if(!is_numeric($tailPrice) || !is_numeric($replenishPrice)){
            \Yii::info([$tailPrice,$replenishPrice], "orderPayFull_2");
            return false;
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.pay.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.pay.method.pay');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $_data = [
            "orderId"	        =>	$orderId,
            "yid"		        =>	$yid,
            //"price"           =>  0,
            "tailPrice"		    =>	$tailPrice,
            "replenishPrice"    =>  $replenishPrice,
        ];
        $data = $httpClient->post($methodPath, $_data);
        \Yii::info([$_data,$data], "orderPayFull_3");
        if(isset($data['code']) && $data['code']==0){
            if(isset($data['data']['remainPrice'])){
                self::$flowNumber = (string)$data['data']['id'];
                return $data['data']['remainPrice'];
            }
        }
        \Yii::info([$_data,$data], "orderPayFull_4");
        return false;
    }

    /**
     * 订单部分支付
     * @return false/int
     */
    public static function orderPayPart($orderId, $yid, $price){
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.pay.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.pay.method.pay');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $_data = [
            "orderId"	        =>	$orderId,
            "yid"		        =>	$yid,
            "price"             =>  $price,
            //"tailPrice"		=>	0,
            //"replenishPrice"  =>  0,
        ];
        $data = $httpClient->post($methodPath, $_data);
        \Yii::info([$_data,$data], "orderPayPart_1");
        if(isset($data['code']) && $data['code']==0){
            if(isset($data['data']['remainPrice'])){
                self::$flowNumber = (string)$data['data']['id'];
                return $data['data']['remainPrice'];
            }
        }
        \Yii::info([$_data,$data], "orderPayPart_2");
        return false;
    }

    /**
     * 插入一条payment记录（存在则不插入）
     */
    public static function addPayment($orderId, $price){
        if(empty($price) || !is_numeric($price)){
            \Yii::info($price, "addPayment_0");
            return false;
        }
        $chk_payment = OrderPayment::find()->where(['order_id'=>$orderId])->asArray()->one();
        if(empty($chk_payment)){
            //tbl_order_payment
            $OrderPayment = new OrderPayment();
            $OrderPayment->pay_type = 1;//账户余额
            $OrderPayment->order_id = $orderId;
            $OrderPayment->total_price = $price;
            $OrderPayment->final_price = $price;
            $OrderPayment->paid_price = 0;//已支付金额
            $OrderPayment->remain_price = $price;//剩余支付金额
            $OrderPayment->pay_time = date("Y-m-d H:i:s", time());
            if (!$OrderPayment->validate()){
                \Yii::info($OrderPayment->getFirstError(), "addPayment_1");
                return false;
            }else{
                if($OrderPayment->save()){
                    \Yii::info("add success!", "addPayment_4");
                    return true;
                }else{
                    \Yii::info($OrderPayment->getErrors(), "addPayment_2");
                    return false;
                }
            }
        }
        \Yii::info("success!", "addPayment_3");
        return true;
    }

    /**
     * 更新订单pay_type
     */
    public static function updateOrderPayType($orderId, $payType=1){
        $Order = Order::find()->where(["id"=>$orderId])->one();
        if(!empty($Order)){
            $Order->pay_type = $payType;
            if($Order->save(false)){
                \Yii::info(['add success!'], "updateOrderPayType");
            }else{
                \Yii::info($Order->getErrors(), "updateOrderPayType");
            }
        }
    }

    /**
     * 更改订单状态
     * 前提：订单完全支付后
     */
    public static function updateOrderStatus($orderId, $zt=8){
        //正常订单扣款修改订单状态
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.order.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.order.method.updateOrder');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $_data = ["orderId"=>$orderId,"status"=>$zt,"isPaid"=>1];
        $data = $httpClient->post($methodPath, $_data);
        \Yii::info([$_data,$data], "updateOrderStatus_info");
        if(isset($data['code']) && $data['code']==0){
            \Yii::info('success!', "updateOrderStatus_1");
            return true;
        }else{
            \Yii::info('error!', "updateOrderStatus_2");
            return false;
        }
    }

    /**
     * 将tbl_order_doubt status改为已完成状态
     * 前提：订单完全支付后
     */
    public static function updateDoubtStatus($orderId){
        $OrderDoubt = OrderDoubt::find()->where(["order_id"=>$orderId, "status"=>3])->one();
        if(!empty($OrderDoubt)){
            $OrderDoubt->status = 4;//4已完成
            if(!empty(self::$flowNumber)){
                $OrderDoubt->adjust_content = self::$flowNumber;
            }
            if($OrderDoubt->save(false)){
                \Yii::info('success', "updateDoubtStatus_1");
            }else{
                \Yii::info($OrderDoubt->getErrors(), "updateDoubtStatus_2");
            }

            if(!empty(self::$flowNumber)){
                $OrderAdjustRecord = OrderAdjustRecord::find()->where(['doubt_id'=>$OrderDoubt->id])->one();
                if(!empty($OrderAdjustRecord)){
                    $OrderAdjustRecord->charge_number = self::$flowNumber;
                    if($OrderAdjustRecord->save(false)){
                        \Yii::info('success', "updateOrderAdjustRecord_1");
                    }else{
                        \Yii::info($OrderAdjustRecord->getErrors(), "updateOrderAdjustRecord_2");
                    }
                }
            }
        }else{
            \Yii::info('no OrderDoubt', "updateDoubtStatus_3");
        }
        return true;
    }

    /**
     * 获取每条流水应该返回的本金和赠金金额
     * @return array （+/-）金额
     */
    public static function getRecordPrice($v){
        switch ($v['trade_type']){
            case 1 ://充值
                $v['pay_capital'] = '+'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '+'.$v['pay_give_fee'];
                }
                break;
            case 2 ://消费
                $v['pay_capital'] = '-'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '-'.$v['pay_give_fee'];
                }
                break;
            case 3 ://退款
                $v['refund_capital'] = '+'.$v['refund_capital'];
                if($v['refund_give_fee']>0){
                    $v['refund_give_fee'] = '+'.$v['refund_give_fee'];
                }
                break;
            case 4 ://订单冻结
                $v['pay_capital'] = '-'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '-'.$v['pay_give_fee'];
                }
                break;
            case 5 ://订单补扣
                $v['pay_capital'] = '-'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '-'.$v['pay_give_fee'];
                }
                break;
            case 6 ://尾款支付
                $v['pay_capital'] = '-'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '-'.$v['pay_give_fee'];
                }
                break;
            case 7 ://订单解冻
                $v['pay_capital'] = '+'.$v['pay_capital'];
                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '+'.$v['pay_give_fee'];
                }
                break;
            default:

                if($v['pay_capital']>0){
                    $v['pay_capital'] = '+'.$v['pay_capital'];
                }elseif($v['pay_capital']<0){
                    $v['pay_capital'] = '-'.$v['pay_capital'];
                }

                if($v['pay_give_fee']>0){
                    $v['pay_give_fee'] = '+'.$v['pay_give_fee'];
                }elseif($v['pay_give_fee']<0){
                    $v['pay_give_fee'] = '-'.$v['pay_give_fee'];
                }

                break;
        }
        return [
            'pay_capital' => $v['pay_capital'],
            'pay_give_fee' => $v['pay_give_fee'],
        ];
    }

    /***********************************
     * BOSS后台给乘客直接充值
     */
    public static function rechargeBoss($passengerId=null, $capital=0, $giveFee=0, $adminName=''){
        if(empty($passengerId)){
            return false;
        }
        if(empty($capital) && empty($giveFee)){
            return false;
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.pay.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.pay.method.recharge');
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $data['yid'] = $passengerId;
        $data['capital'] = $capital;
        $data['giveFee'] = $giveFee;
        $data['createUser'] = $adminName;
        \Yii::info($data, "rechargeBoss_1");
        $rs = $httpClient->post($methodPath, $data);
        \Yii::info($rs, "rechargeBoss_2");
        if(isset($rs['code']) && $rs['code']==0){
            return true;
        }else{
            throw new UserException("java rechargeBoss error!", 1);
        }
    }

}