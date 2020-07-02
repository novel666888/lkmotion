<?php
/**
 * 乘客类
 *
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/26
 * Time: 14:33
 */
namespace common\logic;

use common\models\Order;
use common\models\OrderRulePrice;
use common\models\OrderPayment;
use common\models\PassengerInfo;
use common\util\Common;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use common\models\PassengerWallet;
use common\models\PassengerWalletRecord;
use common\services\YesinCarHttpClient;
use common\services\XEncode;

/**
 * Class Passenger
 * @package common\logic
 * @property string $name
 * @property string $phone
 */

class Passenger extends Component
{
    public $id;
    private $_passengerAR; // passenger Active Record;

    /**
     * Passenger constructor.
     * @param $id
     * @param array $config
     * @throws InvalidConfigException
     * @throws \yii\base\UserException
     */

    public function __construct($id,array $config = [])
    {
        if(empty($id) || !is_numeric($id)){
            throw new  InvalidConfigException('Params error!');
        }
        $this->id = $id;
        $this->_passengerAR = PassengerInfo::getOne($id);
        parent::__construct($config);
    }

    /**
     * @return mixed
     */

    public function getName()
    {
        return $this->_passengerAR->passenger_name;
    }

    /**
     * @return mixed
     */

    public function getPhone()
    {
        return $this->_passengerAR->phone;
    }

    /**
     * check passenger has unpaid order
     *
     * @return array|bool
     */

    public function hasUnpaidOrder()
    {
        $order = Order::find()
            ->where([
                'passenger_info_id' => $this->id,
                'is_paid' => Order::IS_PAID_NO,
                'status' => [Order::STATUS_ARRIVED, Order::STATUS_GATHERING]
            ])
            ->select(['order_id'])
            ->asArray()
            ->all();
        if(empty($order)){
            return false;
        }

        return  ArrayHelper::getColumn($order,'order_id');
    }


    /**
     * 行程可开发票金额=行程可开发票总额（支付金额）-行程已开发票金额（支付金额）
     */
    public function getTripInvoiceAmount(){
        $order_all = Order::find()->select(['id'])
            ->andFilterWhere(['passenger_info_id' => $this->id,'is_paid' => Order::IS_PAID_YES,'status' => Order::STATUS_COMPLETE])->select(['id'])->asArray()->all();
        $price_all = 0;
        if(!empty($order_all)){
            $cipherKeys = ArrayHelper::getColumn($order_all, 'id');
            $RulePrice = OrderPayment::find()->select(["sum(paid_price) AS total_price"])->andFilterWhere(['in', "order_id", $cipherKeys])->asArray()->one();
            if(!empty($RulePrice)){
                $price_all = $RulePrice['total_price'];
            }
        }

        $order_inv = Order::find()->select(['id'])
            ->andFilterWhere(['passenger_info_id' => $this->id,'is_paid' => Order::IS_PAID_YES,'status' => Order::STATUS_COMPLETE])
            ->andFilterWhere(['in', 'invoice_type', [2,3,4]])->select(['id'])->asArray()->all();
        $price_inv = 0;
        if(!empty($order_inv)){
            $cipherKeys = ArrayHelper::getColumn($order_inv, 'id');
            $RulePrice = OrderPayment::find()->select(["sum(paid_price) AS total_price"])->andFilterWhere(['in', "order_id", $cipherKeys])->asArray()->one();
            if(!empty($RulePrice)){
                $price_inv = $RulePrice['total_price'];
            }
        }
        return sprintf("%.2f", ($price_all-$price_inv));
    }


    /**
     * 统计、乘客总充值金额
     */
    public function getTotalRechargeAmount(){
        $model = PassengerWalletRecord::find()->select(["sum(pay_capital) AS pay_capital"])
            ->andFilterWhere(['passenger_info_id' => $this->id,'trade_type' => 1, 'pay_status' => 1])->asArray()->one();
        if(!empty($model)){
            return sprintf("%.2f", $model['pay_capital']);
        }else{
            return 0;
        }
    }

    /**
     * 统计、乘客总退款金额
     */
    public function getTotalRefundAmount(){
        $model = PassengerWalletRecord::find()->select(["sum(pay_capital) AS pay_capital"])
            ->andFilterWhere(['passenger_info_id' => $this->id,'trade_type' => 3, 'pay_status' => 1])->asArray()->one();
        if(!empty($model)){
            return sprintf("%.2f", $model['pay_capital']);
        }else{
            return 0;
        }
    }

    /**
     * 统计，乘客总订单支付金额
     */
    public function getTotalOrderPaymentAmount(){
        $order_all = Order::find()->select(['id'])
            ->andFilterWhere(['passenger_info_id' => $this->id,'is_paid' => Order::IS_PAID_YES,'status' => Order::STATUS_COMPLETE])->select(['id'])->asArray()->all();
        $price_all = 0;
        if(!empty($order_all)){
            $cipherKeys = ArrayHelper::getColumn($order_all, 'id');
            $RulePrice = OrderRulePrice::find()->select(["sum(total_price) AS total_price"])->andFilterWhere(['in', "order_id", $cipherKeys])->andFilterWhere(['category'=>1])->asArray()->one();
            if(!empty($RulePrice)){
                $price_all = $RulePrice['total_price'];
            }
        }
        return sprintf("%.2f", $price_all);
    }

    /**
     * 统计，乘客本月订单支付金额
     */
    public function getMonthOrderPaymentAmount(){
        $BeginDate  = date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
        $EndDate    = date('Y-m-d 23:59:59', strtotime("$BeginDate +1 month -1 day"));
        $order = Order::find()
            ->andFilterWhere([
                'passenger_info_id' => $this->id,
                'is_paid' => Order::IS_PAID_YES,
                'status' => Order::STATUS_COMPLETE,
            ])
            ->andFilterWhere([">", "order_start_time", $BeginDate])
            ->andFilterWhere(["<", "order_start_time", $EndDate])
            ->select(['id'])->asArray()->all();

        if(!empty($order) && is_array($order)){
            $mm = OrderRulePrice::find()->select(["sum(total_price) AS total_price"])->andFilterWhere(['in', 'order_id', array_column($order, 'id')])->andFilterWhere(['category'=>1])->asArray()->one();
            if(isset($mm['total_price'])){
                return sprintf("%.2f", $mm['total_price']);
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }


    /**
     * 统计、乘客总出行里程（公里）
     */
    public function getTotalDistance(){
        $order = Order::find()
            ->andFilterWhere([
                'passenger_info_id' => $this->id,
                'is_paid' => Order::IS_PAID_YES,
                'status' => Order::STATUS_COMPLETE,
            ])
            ->select(['id'])->asArray()->all();
        if(!empty($order) && is_array($order)){
            $mm = OrderRulePrice::find()->select(["sum(total_distance) AS total_distance"])->andFilterWhere(['in', 'order_id', array_column($order, 'id')])->andFilterWhere(['category'=>1])->asArray()->one();
            if(isset($mm['total_distance'])){
                return sprintf("%.2f", $mm['total_distance']);
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }
    /**
     * 统计、乘客当月出行里程（公里）
     */
    public function getMonthDistance(){
        $BeginDate  = date('Y-m-01 00:00:00', strtotime(date("Y-m-d")));
        $EndDate    = date('Y-m-d 23:59:59', strtotime("$BeginDate +1 month -1 day"));
        $order = Order::find()
            ->andFilterWhere([
                'passenger_info_id' => $this->id,
                'is_paid' => Order::IS_PAID_YES,
                'status' => Order::STATUS_COMPLETE,
            ])
            ->andFilterWhere([">", "order_start_time", $BeginDate])
            ->andFilterWhere(["<", "order_start_time", $EndDate])
            ->select(['id'])->asArray()->all();

        if(!empty($order) && is_array($order)){
            $mm = OrderRulePrice::find()->select(["sum(total_distance) AS total_distance"])->andFilterWhere(['in', 'order_id', array_column($order, 'id')])->andFilterWhere(['category'=>1])->asArray()->one();
            if(isset($mm['total_distance'])){
                return sprintf("%.2f", $mm['total_distance']);
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }


    /**
     * 返回行程中的订单
     * @return array 订单号，一维数组
     */
    public function checkOrderStatus(){
        $passengerId = $this->id;
        $order = order::find()->select(['id'])->where(['passenger_info_id'=>$passengerId, 'status'=>5])
            ->orderBy("start_time DESC")
            ->asArray()->all();
        if(count($order)<=0){
            return [];
        }
        $ids = ArrayHelper::getColumn($order, 'id');
        return $ids;
    }

    /**
     * 加密单号
     * @param $ids array 订单号，一维数组
     * @return array 订单号/加密号，二维数组
     */
    public function encodeOrderId($ids){
        if(count($ids)<=0){
            return [];
        }
        $XEncode = new XEncode();
        $r=[];
        foreach ($ids as $k => $v){
            $Encrypted = $XEncode->encode($v);
            //$url = \Yii::$app->params['shareTripH5Url']."?orderId=".$Encrypted;
            $url = $Encrypted;
            $r[]=[
                'orderId'=>$v,
                'encode' =>$url
            ];
        }
        return $r;
    }

    /**
     * 检测可发送行程的订单号是否存在redis中
     * @param $ids array 订单号，一维数组
     * @return array 订单号，一维数组
     */
    public function checkTripOrderIdRedis($ids){
        if(count($ids)<=0){
            return [];
        }
        $redis = \Yii::$app->redis;
        foreach($ids as $k => $orderId){
            $key = "smstrip_".$orderId;
            $rs = $redis->keys($key);
            if(!empty($rs)){
                unset($ids[$k]);
            }
        }
        return $ids;
    }

    /**
     * 判断用户是否开启紧急联系人、自动分享行程和返回第一紧急联系人
     * @return bool
     */
    public function emergencyContact(){
        $passengerId = $this->id;
        $info = PassengerInfo::getUserInfo(["id"=>$passengerId]);
        if(!empty($info)){
            if($info['is_contact']==1 && $info['is_share']==1){
                $info['sharing_time'] = explode("-", $info['sharing_time']);
                //开始时间戳
                $today = date("Y-m-d");
                $today = $today." ".$info['sharing_time'][0].":00";
                $today = strtotime($today);
                //结束时间戳
                $tomorrow = date("Y-m-d",strtotime("+1 day"));
                $tomorrow = $tomorrow." ".$info['sharing_time'][1].":00";
                $tomorrow = strtotime($tomorrow);
                //当前时间戳
                $currentTime = time();
                if($today<=$currentTime && $tomorrow>=$currentTime){
                    $first = PassengerInfo::getFirstContact($passengerId);
                    if($first!==false){
                        $ids = $this->checkOrderStatus();
                        if(empty($ids)){
                            \Yii::info("无正在行驶中行程，未发送紧急短信", "sendSmsTrip");
                            return false;
                        }
                        $_ids = $this->checkTripOrderIdRedis($ids);
                        if(empty($_ids)){
                            \Yii::info([$ids,"有正在行驶中行程，但都已发送短信"], "sendSmsTrip");
                            return false;
                        }
                        $__ids = $this->encodeOrderId($_ids);
                        if(empty($__ids)){
                            \Yii::info([$_ids,"订单ID，加密错误"], "sendSmsTrip");
                            return false;
                        }
                        $redis = \Yii::$app->redis;
                        $huanj = \Yii::$app->params['env'];
                        switch ($huanj){
                            case 'test' : $twoLevel = "test-h5"; break;
                            case 'pre'  : $twoLevel = "pre-h5"; break;
                            case 'prod' : $twoLevel = "h5"; break;
                            default: $twoLevel = "test-h5"; break;
                        }
                        foreach ($__ids as $__k => $__v){
                            //$first['phone'] = '18600611653';
                            $msgdata = [
                                $info['passenger_name'],
                                Common::decryptCipherText($info['phone'], true),
                                $twoLevel,
                                $__v['encode'],
                            ];
                            Common::sendMessageNew($first['phone'], "HX_0040", $msgdata);
                            $redis->set("smstrip_".$__v['orderId'], "1");
                            $redis->expire("smstrip_".$__v['orderId'], 86400);
                        }
                        \Yii::info([$__ids,"已发送紧急行程短信"], "sendSmsTrip");
                        return true;
                    }else{
                        \Yii::info(["未找到紧急联系人"], "sendSmsTrip");
                    }
                }else{
                    \Yii::info(["不在发送短信时间段范围内"], "sendSmsTrip");
                }
            }else{
                \Yii::info(["未开启分享行程或紧急联系人"], "sendSmsTrip");
            }
        }
        return false;
    }


}