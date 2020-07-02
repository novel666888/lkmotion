<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/14
 * Time: 12:41
 */

namespace application\modules\order\models;

use application\modules\order\components\PhoneNumber;
use common\models\CarInfo;
use common\models\CarLevel;
use common\models\DriverInfo;
use common\models\EvaluateCarscreen;
use common\models\EvaluateDriverToPassenger;
use common\models\Order;
use common\models\OrderRulePrice;
use common\models\OrderRulePriceTag;
use common\models\PassengerInfo;
use common\models\SecretVoiceRecords;
use common\services\CConstant;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;
use yii\base\UserException;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class OrderBoss extends Order
{
    use ModelTrait;

    /**
     * get order list
     *
     * @param null $where
     * @param null $sort
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function getOrderList($where = null, $sort = null)
    {
        $columns     = [
            'orderId' => 'id',
            'orderNum' => 'order_number',
            'orderType' => 'order_type',
            'orderStatus' => 'status',
            'userFeature' => 'user_feature',//1201版加入用户标签
            'getOnAddress' => 'start_address',//1201版加入用户标签
            'getOffAddress' => 'end_address',
            'passengerInfoId'=>'passenger_info_id',
            'payType' => new Expression('space(-1)'),
            'srvType' => 'service_type',
            'cabRunnerName' => 'passenger_info_id',
            'cabRunnerPhone' => 'passenger_phone',
            'carManName' => 'other_name',
            'carManPhone' => 'other_phone',
            'driverId'=>'driver_id',
            'driver' => 'driver_id',
            'driverPhone' => 'driver_phone',
            'orderTime' => 'start_time',
            'orderBeginTime' => 'order_start_time',
            'orderChannel' => 'order_channel',
            'userSource' => 'source',
            'carId' => 'car_id',
            'receivePassengerTime' => 'receive_passenger_time',
            'passengerGetoffTime' => 'passenger_getoff_time',
            'payTime' => new Expression('space(-1)'),
        ];
        /**1201版去除掉叫车后无效的订单 on 2018/11/7 (status = 1 and is_fake_success = 1) or (status > 1) */
        $activeQuery = self::find()->select($columns)
            ->where(
                ['or',
                    ['>', 'status', Order::ORDER_START],
                    ['and',
                        ['=', 'status', Order::ORDER_START],
                        ['=', 'is_fake_success', Order::IS_FAKE_SUCCESS]
                    ]
                ]
            );
        if ($where) {
            if (isset($where['orderNum']) && (($orderNum = trim($where['orderNum'])) !== '')) {
                $activeQuery->andWhere(['order_number' => $orderNum]);
            }
            if (isset($where['cabRunnerPhone']) && (($cabRunnerPhone = trim($where['cabRunnerPhone'])) !== '')) {
                $phone_encrypt_info = Common::phoneEncrypt($cabRunnerPhone);
                $activeQuery->andWhere(['passenger_phone' => $phone_encrypt_info]);
            }
            if (isset($where['plateNumber']) && (($plateNumber = trim($where['plateNumber'])) !== '')) {
                $activeQuery->andWhere(['plate_number' => $plateNumber]);
            }
            if (isset($where['carManPhone']) && (($carManPhone = trim($where['carManPhone'])) !== '')) {
                $phone_encrypt_info = Common::phoneEncrypt($carManPhone);
                $activeQuery->andWhere(['other_phone' => $phone_encrypt_info]);
            }
            if (isset($where['driverPhone']) && (($driverPhone = trim($where['driverPhone'])) !== '')) {
                $phone_encrypt_info = Common::phoneEncrypt($driverPhone);
                $activeQuery->andWhere(['driver_phone' => $phone_encrypt_info]);
            }
            if (isset($where['orderStatus'])) {
                $activeQuery->andFilterWhere(['status' => $where['orderStatus']]);
            }
            //用户特征
            if (isset($where['userFeature'])){
                $activeQuery->andFilterWhere(['user_feature' => $where['userFeature']]);
            }
            if (isset($where['payType'])) {
                $activeQuery->andFilterWhere(['pay_type' => $where['payType']]);
            }
            if (isset($where['isUseCoupon'])  && $where['isUseCoupon'] !='') {
                $useCouponOrderIds = OrderPayment::find()
                    ->where(['<>','user_coupon_id',0])
                    ->select('order_id')
                    ->column();
                if($where['isUseCoupon'] == 1){
                    $activeQuery->andWhere(['id'=>$useCouponOrderIds]);
                }else{
                    $activeQuery->andWhere(['not in','id',$useCouponOrderIds]);
                }
            }
            if (isset($where['serviceType'])) {
                $activeQuery->andFilterWhere(['service_type' => $where['serviceType']]);
            }
            if (isset($where['isPaid'])) {
                $activeQuery->andFilterWhere(['is_paid' => $where['isPaid']]);
            }
            if(!empty($where['orderBeginTime'])){
                $activeQuery->andWhere(['>=', 'start_time', $where['orderBeginTime']]);
            }
            if(!empty($where['orderEndTime'])){
                $orderEndTime = date('Y-m-d H:i:s', (strtotime($where['orderEndTime'])+86400));
                $activeQuery->andWhere(['<', 'start_time', $orderEndTime]);
            }
            if(!empty($where['useCarBeginTime'])){
                $activeQuery->andWhere(['>=', 'order_start_time', $where['useCarBeginTime']]);
            }
            if(!empty($where['useCarEndTime'])){
                $useCarEndTime = date('Y-m-d H:i:s', (strtotime($where['useCarEndTime'])+86400));
                $activeQuery->andWhere(['<', 'order_start_time', $useCarEndTime]);
            }

            /************************1201版加入新条件*************************/
            if(!empty($where['orderScenario'])){
                if(strlen($where['orderScenario'])<4){
                    $activeQuery->andWhere(new Expression('FIND_IN_SET(:user_feature,user_feature)'))
                        ->addParams([':user_feature'=>$where['orderScenario']]);
                }else{
                    $scenarioCount = (int)substr($where['orderScenario'],-1,1);
                    $activeQuery->andWhere(['=',new Expression("length(`user_feature`)-length(replace(`user_feature`,',',''))+1"),$scenarioCount]);
                }
            }
            if(!empty($where['cityCode'])){
                $orderIdsFromCityCode = OrderRulePrice::find()
                    ->select('order_id')
                    ->where(['city_code'=>$where['cityCode'],'category'=>CConstant::TYPE_FORECAST_ORDER])
                    ->column();
                $activeQuery->andWhere(['id'=>$orderIdsFromCityCode]);

            }
            /************************end 1201******************************/
        }
        $data = self::getPagingData($activeQuery, $sort);
        $lists= $data['data']['list'];

        if(!empty($lists)) {
            $newList = PhoneNumber::mappingCipherToPhoneNumber($lists,[
                'cabRunnerPhone','carManPhone','driverPhone'
            ]);
            $data['data']['list'] = $newList;
        }
        self::_appendOrderPriceData($data);

        return $data;
    }


    /**
     * @param $data
     */

    private static function _appendOrderPriceData(&$data)
    {   //@todo service_duration
        $orderIds                   = ArrayHelper::getColumn($data['data']['list'], 'orderId');
        if(!$orderIds)  return;

        $orderRulePriceForecastData = OrderRulePrice::find()
            ->select(['order_id', 'total_price', 'total_distance','car_level_id','car_level_name'])
            ->where(['order_id' => $orderIds, 'category' => 0])
            ->indexBy('order_id')
            ->asArray()
            ->all();
        //var_dump($orderRulePriceForecastData);exit;
        $orderRulePriceActualData = OrderRulePrice::find()
            ->select(['order_id', 'total_price', 'total_distance'])
            ->where(['order_id' => $orderIds, 'category' => 1])
            ->indexBy('order_id')
            ->asArray()
            ->all();
        $orderUseCopounData = OrderUseCoupon::find()
            ->select(['order_id','after_use_coupon_moeny'])
            ->where(['order_id' => $orderIds])
            ->indexBy('order_id')
            ->all();
        $orderPay           = OrderPayment::find()
            ->select(['order_id', 'pay_type', 'pay_time','total_price','final_price'])
            ->where(['order_id' => $orderIds])
            ->asArray()
            ->indexBy('order_id')
            ->all();
        $orderCancelRecordData = OrderCancelRecord::find()
            ->select(['order_id'])
            ->where(['order_id'=>$orderIds,'is_charge'=>OrderCancelRecord::USER_NO_CHARGE])
            ->asArray()
            ->indexBy('order_id')
            ->all();


        $driver_ids = ArrayHelper::getColumn($data['data']['list'], 'driverId');
        $driver_info = DriverInfo::showBatch($driver_ids);

        $passenger_ids = ArrayHelper::getColumn($data['data']['list'], 'passengerInfoId');
        $passenger_info = PassengerInfo::showBatch($passenger_ids);

        //var_dump($orderPay);exit;
        //var_dump($orderRulePriceActualData);exit;
        if ($data['data']['list']) {
            foreach ($data['data']['list'] as $k => $v) {
                //$orderRulePriceMode = OrderRulePrice::findOne(['order_id' => $v['orderId'], 'category' => 1]);
                if (isset($orderRulePriceActualData[$v['orderId']])) {
                    $data['data']['list'][$k]['orderPrice'] = $orderRulePriceActualData[$v['orderId']]['total_price'];
                    $data['data']['list'][$k]['srvMile']    = $orderRulePriceActualData[$v['orderId']]['total_distance'];
                } else if (isset($orderRulePriceForecastData[$v['orderId']])) {
                    //var_dump($orderRulePriceForecastData[$v['orderId']]);exit;
                    $data['data']['list'][$k]['orderPrice'] = $orderRulePriceForecastData[$v['orderId']]['total_price'];
                    $data['data']['list'][$k]['srvMile']    = $orderRulePriceForecastData[$v['orderId']]['total_distance'];
                    if(isset($orderUseCopounData[$v['orderId']])){
                        $data['data']['list'][$k]['orderPrice'] = $orderUseCopounData[$v['orderId']]['after_use_coupon_moeny'];
                    }
                } else {
                    $data['data']['list'][$k]['orderPrice'] = '0.00';
                    $data['data']['list'][$k]['srvMile']    = '0.0';
                }
                if(isset($orderCancelRecordData[$v['orderId']])){ //若是无责取消订单 费用为 0
                    $data['data']['list'][$k]['orderPrice'] = '0.00';
                }
                if(isset($orderPay[$v['orderId']])){ // 如果已经产生orderPayment
                    $data['data']['list'][$k]['orderPrice'] = $orderPay[$v['orderId']]['final_price'];
                }
                if (isset($orderPay[$v['orderId']])) {
                    $data['data']['list'][$k]['payTime'] = $orderPay[$v['orderId']]['pay_time'];
                    $data['data']['list'][$k]['payType'] = $orderPay[$v['orderId']]['pay_type'];
                }
                $data['data']['list'][$k]['carLevel'] = '';
                if(isset($orderRulePriceForecastData[$v['orderId']]['car_level_name'])){
                    $data['data']['list'][$k]['carLevel'] = $orderRulePriceForecastData[$v['orderId']]['car_level_name'];
                }
                $data['data']['list'][$k]['srvDuration'] = '--';
                if(isset($v['receivePassengerTime']) && isset($v['passengerGetoffTime'])){
                    $srvDuration = floor((strtotime($v['passengerGetoffTime']) - strtotime($v['receivePassengerTime'])) / 60);
                    $data['data']['list'][$k]['srvDuration'] = $srvDuration;
                }
                if(isset($driver_info[$v['driver']])){
                    $data['data']['list'][$k]['driver'] = isset($driver_info[$v['driver']]['driver_name'])?$driver_info[$v['driver']]['driver_name']:"";
                }
                if(isset($passenger_info[$v['cabRunnerName']])){
                    $data['data']['list'][$k]['cabRunnerName'] = $passenger_info[$v['cabRunnerName']]['passenger_name'];
                }

                /*****************************1201 列表加入（多个） 用户标签 **********************/
                if(!empty($data['data']['list'][$k]['userFeature'])){
                    $userFeatureIds = explode(',',$data['data']['list'][$k]['userFeature']);
                    $userFeatures = TagInfo::fetchArray(['id'=>$userFeatureIds],['tag_name']);
                    $userFeatureTextArray = ArrayHelper::getColumn($userFeatures,'tag_name');
                    $data['data']['list'][$k]['userFeatureText'] = $userFeatureTextArray;
                }
                /*************************************end 1201*******************************/
            }
        }
    }

    /**
     * @param $orderId
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    public static function getOrderDetail($orderId)
    {
        if (!is_numeric($orderId)) {
            throw new UserException('Params error!', 1001);
        }
        $orderModel = OrderBoss::findOne(intval($orderId));
        if (empty($orderModel)) {
            throw new UserException('No data!', 1002);
        }
        $orderPreCostModel = OrderRulePrice::getOne([
            'order_id' => $orderId,
            'category' => OrderRulePrice::PRICE_TYPE_FORECAST,
        ]);
        $orderPaymentTable   = OrderPayment::getOne(['order_id' => $orderId],false);
        $orderUserCoupon = OrderUseCoupon::findOne(['order_id'=>$orderId]);
        $columns = [
            'totalPrice' => 'total_price',
            'totalDistance'=>'total_distance',
            'totalTime' => 'total_time',
            'basePrice' => 'base_price', //起步费(包含公里base_kilo公里base_minute分钟)
            'baseKilo' => 'base_kilo',//基础价包含公里数
            'baseMinute' => 'base_minute',//基础价时长
            'lowestPrice' => 'lowest_price',//最低消费
            'nightStart' => 'night_start',
            'nightEnd' => 'night_end',
            'nightPerKiloPrice' => 'night_per_kilo_price',
            'nightPerMinutePrice' => 'night_per_minute_price',
            'nightDistance' => 'night_distance',
            'nightTime' => 'night_time',
            'nightPrice'=>'night_price',
            'beyondStartKilo'=>'beyond_start_kilo',
            'beyondPerKiloPrice'=>'beyond_per_kilo_price',
            'beyondDistance'=>'beyond_distance',
            'beyondPrice' => 'beyond_price',
            'perKiloPrice'=>'per_kilo_price', // 每公里单价,
            'path'=>'path',//无分段时 里程费使用 距离所用字段, 里程费=path*per_kilo_price (有分段计价,取periodPrice字段)
            'pathPrice' => 'path_price', // 里程费(元)
            'perMinutePrice'=>'per_minute_price',
            'duration'=>'duration',//时长
            'durationPrice' => 'duration_price',//时长费用
            'restDuration'=>'rest_duration',
            'restDurationPrice'=>'rest_duration_price',
            'restDistance'=>'rest_distance',  // 其他时距离
            'restDistancePrice' => 'rest_distance_price',
            'roadPrice'=>'road_price',
            'parkingPrice' => 'parking_price',
            'otherPrice' => 'other_price',
            'dynamicDiscountRate'=>'dynamic_discount_rate',//动态调价率
            'dynamicDiscount'=>'dynamic_discount',//动态调价 金额
            'supplementPrice'=>'supplement_price',
            'couponDiscount' => new Expression('0.00'),
            'isAdjust'=>new Expression('0'),
        ];
        $actualCost = OrderRulePrice::fetchOne([
            'order_id' => $orderId,
            'category' => OrderRulePrice::PRICE_TYPE_ACTUAL,
        ], $columns);
        $periodPrice  = OrderRulePriceDetail::fetchArray([
            'order_id'=>$orderId,
            'category'=>CConstant::TYPE_ACTUAL_ORDER,
        ],[
            'startHour'=>'start_hour',
            'endHour'=>'end_hour',
            'perKiloPrice'=>'per_kilo_price',
            'distance'=>'distance',
            'perMinutePrice'=>'per_minute_price',
            'duration'=>'duration',
        ]);
        /************1201版加入用户标签费用**********************/
        $actualTagPrice = OrderRulePriceTag::fetchArray([
            'order_id'=>$orderId,
            'category'=>CConstant::TYPE_ACTUAL_ORDER,
        ],[
            'tagName'=>'tag_name',
            'tagPrice'=>'tag_price',
        ]);
        /*************************end****************************/
        //if($actualCost && $orderModel->is_adjust !=0){
        if($actualCost){
            if($orderModel->is_adjust!=0) {
                $actualCost['isAdjust'] = '1';
            }
            $actualCost['totalPrice'] = empty($orderPaymentTable)?$actualCost['totalPrice']:$orderPaymentTable->final_price;
            $actualCost['couponDiscount'] = empty($orderPaymentTable)?$actualCost['couponDiscount']:$orderPaymentTable->coupon_reduce_price;
            $actualCost['periodPrice'] =$periodPrice;
            $actualCost['tagPrice'] = $actualTagPrice; //1201
        }else{
            $actualCost = array_fill_keys(array_keys($columns), '');
            $actualCost['periodPrice'] = [];
            $actualCost['tagPrice'] = []; //1201版加入标签费用
        }

        if (!$orderPreCostModel) {
            throw new UserException('Params order ID error,cannot find forecast cost!', 1003);
        }
        /**@var \common\models\OrderRulePrice $orderPreCostModel **/
        $forecastCost    = [
            'totalPrice' => (string)$orderPreCostModel->total_price,
            'totalDistance'=>(string)$orderPreCostModel->total_distance,
            'totalTime' => (string)$orderPreCostModel->total_time,
            'basePrice' =>(string) $orderPreCostModel->base_price, //基础价
            'baseKilo' => (string)$orderPreCostModel->base_kilo,//基础价包含公里数
            'baseMinute' => (string)$orderPreCostModel->base_minute,//基础价时长
            'lowestPrice' => (string)$orderPreCostModel->lowest_price,
            'nightStart' => (string)$orderPreCostModel->night_start,
            'nightEnd' =>(string)$orderPreCostModel->night_end,
            'nightPerKiloPrice' => (string)$orderPreCostModel->night_per_kilo_price,
            'nightPerMinutePrice' => (string)$orderPreCostModel->night_per_minute_price,
            'nightDistance' => (string)$orderPreCostModel->night_distance,
            'nightTime' => (string)$orderPreCostModel->night_time,
            'nightPrice'=>(string)$orderPreCostModel->night_price,
            'beyondStartKilo' => (string) $orderPreCostModel->beyond_start_kilo,
            'beyondPerKiloPrice'=>(string)$orderPreCostModel->beyond_per_kilo_price,
            'beyondDistance'=>(string)$orderPreCostModel->beyond_distance,
            'beyondPrice' => (string)$orderPreCostModel->beyond_price,
            'perKiloPrice'=>(string)$orderPreCostModel->per_kilo_price, // 每公里单价,
            'path' => (string)$orderPreCostModel->path,//无分段时 里程费使用 距离所用字段, 里程费=path*per_kilo_price
            'pathPrice' => (string)$orderPreCostModel->path_price, // 里程费
            'perMinutePrice'=>(string)$orderPreCostModel->per_minute_price,
            'duration'=>(string)$orderPreCostModel->duration,
            'durationPrice' => (string)$orderPreCostModel->duration_price,
            'restDuration'=>(string)$orderPreCostModel->rest_duration,
            'restDurationPrice' => (string) $orderPreCostModel->rest_duration_price,
            'restDistance'=>(string)$orderPreCostModel->rest_distance,  // 其他时距离
            'restDistancePrice' => (string)$orderPreCostModel->rest_distance_price,
            'roadPrice'=>(string)$orderPreCostModel->road_price,
            'parkingPrice' => (string)$orderPreCostModel->parking_price,
            'dynamicDiscount'=>(string)$orderPreCostModel->dynamic_discount,//动态调价 金额
            'dynamicDiscountRate'=>(string)$orderPreCostModel->dynamic_discount_rate,//动态调价率
            'supplementPrice' => (string)$orderPreCostModel->supplement_price,
            'otherPrice' => (string)$orderPreCostModel->other_price,
            'couponDiscount' => '0.00',
        ];
        $periodPrice = OrderRulePriceDetail::fetchArray([
            'order_id'=>$orderId,
            'category'=>CConstant::TYPE_FORECAST_ORDER
        ],[
            'startHour'=>'start_hour',
            'endHour'=>'end_hour',
            'perKiloPrice'=>'per_kilo_price',
            'distance'=>'distance',
            'perMinutePrice'=>'per_minute_price',
            'duration'=>'duration',
        ]);
        /************************1201 版加入标签费用 预估标签费用******************/
        $forecastTagPrice = OrderRulePriceTag::fetchArray([
            'order_id'=>$orderId,
            'category'=>CConstant::TYPE_FORECAST_ORDER,
        ],[
            'tagName'=>'tag_name',
            'tagPrice'=>'tag_price',
        ]);
        /***********************************end 1201******************************/
        $forecastCost['periodPrice'] = $periodPrice;
        $forecastCost['tagPrice'] = $forecastTagPrice; // 1201
        if (!empty($orderUserCoupon)) {
            $forecastCost['couponDiscount'] = (string)$orderUserCoupon->coupon_money;
            $forecastCost['totalPrice'] = (string)$orderUserCoupon->after_use_coupon_moeny;
        }
        $car_info = CarInfo::showBatch($orderModel->car_id);
        $car_info = array_shift($car_info);
        /**
         * 订单最终价格
         */
        if($orderPaymentTable){
            $orderFinalPrice = (string)$orderPaymentTable->final_price;
        }elseif($actualCost['totalPrice']) {
            $orderFinalPrice = $actualCost['totalPrice'];
        }else{
            $orderFinalPrice = $forecastCost['totalPrice'];
        }
        $orderCancelOrderTable = OrderCancelRecord::findOne(['order_id'=>$orderId]);
        if($orderCancelOrderTable!==null){
            if($orderCancelOrderTable->is_charge == 0){
                $orderFinalPrice ='0.00'; // 如果是取消的订单且是无责的,则订单费用为0元 on 2018/11/19
                $actualCost['totalPrice'] = $orderFinalPrice; //订单最终收费 on 2018/11/20
            } else{
                $actualCost['totalPrice'] = $orderFinalPrice; //订单最终收费 on 2018/11/20
            }
        }

        //服务时长
        $srvDuration = strtotime($orderModel->passenger_getoff_time) - strtotime($orderModel->receive_passenger_time);
        if($srvDuration < 0){
            $srvDuration = '0';
        }else{
            $srvDuration = strval(round($srvDuration/60));
        }
        $baseInfo   = [
            'orderId' => (string)$orderModel->id,
            'orderNum' => (string)$orderModel->order_number,
            'city' => $orderPreCostModel->city_name,
            'srvType' => (string)$orderModel->service_type,
            'carLevel' => isset($car_info['car_level_id'])? $car_info['car_level_id'] : '',
            'orderStatus' => (string)$orderModel->status,
            'getOnAddress' => $orderModel->start_address,
            'getOffAddress' => $orderModel->end_address,
            //'orderPrice' => !empty($actualCost) ? (empty($orderPaymentTable)?$actualCost['totalCost']:$orderPaymentTable->final_price): $orderPreCostModel->total_price,
            'orderPrice' => $orderFinalPrice,
            'srvDuration' => $srvDuration,
            'srvMile' => !empty($actualCost['totalDistance']) ? $actualCost['totalDistance'] : (string)$orderPreCostModel->total_distance,
            'payType' => (string)$orderModel->pay_type,
            'orderChannel' => (string)$orderModel->order_channel,
            'userFeature' => $orderModel->user_feature,
            'invoiceType' => $orderModel->invoice_type,
        ];
        /*****************1201加入多标签功能******************************************/
        if(!empty($orderModel->user_feature)){
            $userFeatureIds = explode(',',$orderModel->user_feature);
            $userFeatures = TagInfo::fetchArray(['id'=>$userFeatureIds],['tag_name']);
            $userFeatureTextArray = ArrayHelper::getColumn($userFeatures,'tag_name');
            $baseInfo['userFeatureText'] = implode(',',$userFeatureTextArray);
        }
        /******************end 1201**************************************/
        $personInfo = [
            'orderType' => (string)$orderModel->order_type,
            'cabRunnerPhone' => $orderModel->passenger_phone,
            'carMan' => $orderModel->other_name,
            'carManPhone' => $orderModel->other_phone,
            'driver' => (string)$orderModel->driver_id,
            'driverPhone' => $orderModel->driver_phone,
            'driverTransferPhone' => $orderModel->mapping_number,
            'plateNum' => $orderModel->plate_number,
            //'dispatcherName' => '',
        ];
        //司机
        if(!is_null($personInfo['driver'])){
            $personInfo['driver'] = DriverInfo::fetchFieldBy(['id'=>$personInfo['driver']],'driver_name');
        }
        $personInfo['cabRunner'] = PassengerInfo::fetchFieldBy(['id'=>$orderModel->passenger_info_id],'passenger_name'); // 订车人
        $phoneNumbers = [
            $personInfo['cabRunnerPhone'],
            $personInfo['carManPhone'],
            $personInfo['driverPhone']
        ];
        $phoneNumbers = array_values(array_unique(array_filter($phoneNumbers,function($v){
            return !empty($v);
        })));
        $phoneNumbers = Common::decryptCipherText($phoneNumbers);
        $personInfo['cabRunnerPhone'] =  ArrayHelper::getValue($phoneNumbers,$personInfo['cabRunnerPhone']);
        $personInfo['carManPhone'] =  ArrayHelper::getValue($phoneNumbers,$personInfo['carManPhone']);
        $personInfo['driverPhone'] =  ArrayHelper::getValue($phoneNumbers,$personInfo['driverPhone']);
        $timeInfo   = [
            'orderTime' => $orderModel->start_time,
            'useCarTime' => $orderModel->order_start_time,
            'driverStartTime' => $orderModel->driver_start_time,
            'driverArrivedTime' => $orderModel->driver_arrived_time,
            'srvBeginTime' => $orderModel->receive_passenger_time,
            'srvFinishTime' => $orderModel->passenger_getoff_time,
            'payTime' => '',
            'cancelOrderTime' => '',
        ];
        if ($orderModel->is_paid) {
            $timeInfo['payTime'] = isset($orderPaymentTable->pay_time)?$orderPaymentTable->pay_time:"";
        }
        if ($orderModel->is_cancel) {
            //$orderCancelOrderTable       = OrderCancelRecord::findOne(['order_id' => $orderId]);
            $timeInfo['cancelOrderTime'] = isset($orderCancelOrderTable->update_time)?$orderCancelOrderTable->update_time:'';
        }

        $costInfo = compact('forecastCost', 'actualCost');
        $memo     = $orderModel->memo;
        if (!$orderModel->is_evaluate) {
            $evalInfo = array_fill_keys(['evalGrade', 'evalText'], '');
        } else {
            //$evalInfo = ['evalGrade' => '3', 'evalText' => '很好!'];
            $evalDriverTable = EvaluateDriver::getOne(['order_id' => $orderId]);
            $evalInfo        = [
                'evalGrade' => $evalDriverTable->grade,
                'evalText' => $evalDriverTable->content,
            ];
            if($evalDriverTable->label){
                /**PassengerCommentDriver*/
                $labelsTextToDriver = '';
                $arrLabels = explode(',',$evalDriverTable->label);
                foreach ($arrLabels as $v){
                    if(isset(\Yii::$app->params['PassengerCommentDriver'][$v])){
                        $labelsTextToDriver = $labelsTextToDriver .' '.\Yii::$app->params['PassengerCommentDriver'][$v];
                    }
                }
                $labelsTextToDriver = trim($labelsTextToDriver);
                $evalInfo['evalText'] =  $labelsTextToDriver . $evalInfo['evalText'];
            }
        }
        if(empty($orderModel->mapping_id)){
            $callRec  = [];
        }else{
            $callRec = SecretVoiceRecords::fetchArray(['subs_id'=>$orderModel->mapping_id,'flag'=>CConstant::STATUS_ENABLE],['oss_download_url']);
            if(!empty($callRec)){
                $callRec = ArrayHelper::getColumn($callRec,'oss_download_url');
            }

        }
        /*************************1201 加入对大屏的评价信息 司机对乘客的评价*****************************/
        $evalCarScreen = ['grade'=>'', 'text'=>''];
        $evalCarScreenTable = EvaluateCarscreen::findOne(['order_id'=>$orderId]);
        if($evalCarScreenTable){
            $evalCarScreen['grade'] = (string)$evalCarScreenTable->grade;
            $evalCarScreen['text'] = $evalCarScreenTable->content;
        }
        $evalDriverToPassenger = ['grade'=>'','text'=>'']; //司机对乘客的评价
        $evalDriverToPassengerTable = EvaluateDriverToPassenger::findOne(['order_id'=>$orderId]);
        if($evalDriverToPassengerTable){
            $driverCommentPassengerLabels = \Yii::$app->params['driverCommentPassenger'];
            //var_dump($evalDriverToPassenger['grade'],$driverCommentPassengerLabels[3][3]);exit;
            //var_dump($evalDriverToPassengerTable);exit;
            $labelsText = '';
            if($evalDriverToPassengerTable->label !=''){
                $arrLabels = explode(',',$evalDriverToPassengerTable->label);
                foreach ($arrLabels as $v){
                    if(isset($driverCommentPassengerLabels[$evalDriverToPassengerTable->grade][$v])){
                        $labelsText = $labelsText .' '.$driverCommentPassengerLabels[$evalDriverToPassengerTable->grade][$v];
                    }
                }
                $labelsText = trim($labelsText);
            }

            $evalDriverToPassenger['grade'] = (string)$evalDriverToPassengerTable->grade;
            $evalDriverToPassenger['text'] = $labelsText . ' ' . $evalDriverToPassengerTable->content;
        }
        /*************************end 1201***********/

        $allData = compact('baseInfo', 'timeInfo', 'personInfo', 'costInfo', 'memo', 'evalInfo', 'evalCarScreen','evalDriverToPassenger','callRec');
        return $allData;

    }

    /**
     * @param $where
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function getCancelOrders($where)
    {
        $activeQuery = self::find()->select([
            'orderId' => 'id',
            'orderNum' => 'order_number',
            'orderType' => 'order_type',
            'passengerInfoId' => 'passenger_info_id',
            'cabRunner' => new Expression('space(-1)'),
            'cabRunnerPhone' => 'passenger_phone',
            'driver' => 'driver_id',//new Expression('space(-1)'),
            'driverPhone' => 'driver_phone',
            'isCharge' => new Expression('0'),
            'cancelCost' => new Expression('0'),
            'reasonType' => 'cancel_order_type',
            'reasonText' => new Expression('space(-1)'),
            'operatorType' => new Expression('space(-1)'),
            'operator' => new Expression('space(-1)'),
        ])
            ->where(['is_cancel' => 1])
            ->andFilterWhere(['passenger_phone'=>$where['cabRunnerPhone']])
            ->andFilterWhere(['driver_phone'=>$where['driverPhone']])
            ->andFilterWhere(['order_number'=>$where['orderNum']]);
        if(!empty($where['reasonType'])){ //920修复的bug合并到1201版中
            $cancelOrderIds = OrderCancelRecord::fetchArray(['reason_type'=>$where['reasonType']],['order_id']);
            $cancelOrderIds = ArrayHelper::getColumn($cancelOrderIds,'order_id');
            $activeQuery->andWhere(['id'=>$cancelOrderIds]);
        }
        $results     = self::getPagingData($activeQuery, ['create_time' => SORT_DESC]);
        if ($results['data']['list']) {
            $orderIds              = ArrayHelper::getColumn($results['data']['list'], 'orderId');
            $cancelOrderRecordData = OrderCancelRecord::find()->select([
                'orderId' => 'order_id',
                'isCharge' => 'is_charge',
                'cancelCost' => 'cancel_cost',
                'reasonType' => 'reason_type',
                'reasonText' => 'reason_text',
                'operatorType' => 'operator_type',
                'operatorId' => 'operator',
                'operateTime'=>'create_time',
            ])->where(['order_id' => $orderIds])->indexBy('orderId')->asArray()->all();
            $passengerIds          = ArrayHelper::getColumn($results['data']['list'], 'passengerInfoId');
            //get passenger name,
            $passengerIds = array_unique($passengerIds);
            $passengerNames = PassengerInfo::find()
                ->select(['passengerId' => 'id', 'passengerName' => 'passenger_name'])
                ->indexBy('passengerId')
                ->where(['id' => $passengerIds])
                ->asArray()
                ->all();

            $operatorIds = ArrayHelper::getColumn($cancelOrderRecordData, 'operatorId');
            $operatorIds = array_unique(array_values($operatorIds));
            //
            $operatorNames = SysUser::find()->select([
                'operatorId' => 'id',
                'username'
            ])->where(['id' => $operatorIds])->indexBy('operatorId')->asArray()->all();
            $driverIds     = ArrayHelper::getColumn($results['data']['list'], 'driver');
            $driverIds = Common::getUniqueAndNotEmptyValueFromArray($driverIds);
            $driverNames  = DriverInfo::find()
                ->where(['id'=>$driverIds])
                ->select('id,driver_name')
                ->asArray()
                ->all();
            $finalDriverNames = [];
            foreach ($driverNames as $item){
                $finalDriverNames[$item['id']]=$item['driver_name'];
            }
            foreach ($results['data']['list'] as $k => $v) {
                if (in_array($v['orderId'], array_keys($cancelOrderRecordData))) {
                    $results['data']['list'][$k] = ArrayHelper::merge($results['data']['list'][$k], (array)$cancelOrderRecordData[$v['orderId']]);
                }
                $results['data']['list'][$k]['cabRunner'] = $passengerNames[$v['passengerInfoId']]['passengerName'];
                //var_dump(p,$operatorNames,$operatorNames[$results['data']['list'][$k]['operatorId']]);exit;
                if (empty($operatorNames) || !isset($operatorNames[$results['data']['list'][$k]['operatorId']])) {
                    $results['data']['list'][$k]['operator'] = '乘客';
                } else {
                    $results['data']['list'][$k]['operator'] = $operatorNames[$results['data']['list'][$k]['operatorId']]['username'];

                }
                if(!empty($v['driver'])){
                    $results['data']['list'][$k]['driver'] = $finalDriverNames[$v['driver']];
                }
            }
            $newList = PhoneNumber::mappingCipherToPhoneNumber($results['data']['list'],['cabRunnerPhone','driverPhone']);
            $results['data']['list'] = $newList;


        }
        return $results;
    }

    /**
     * @param $where
     * @return array|\yii\db\ActiveRecord[]
     * @throws UserException
     */

    public static function getPendingOrders($where)
    {
        /**
         * compact('orderNum', 'cabRunnerPhone', 'carManPhone');
         */
        $results = self::find()
            ->select([
                'orderId' => 'id',
                'orderNum' => 'order_number',
                'orderType' => 'order_type',
                'passengerInfoId' => 'passenger_info_id',
                'cabRunner' => new Expression('space(-1)'),
                'cabRunnerPhone' => 'passenger_phone',
                'carMan' => 'other_name',
                'carManPhone' => 'other_phone',
                'callOrderTime' => 'start_time',
                'useCarTime' => 'order_start_time',
                'getOnAddress' => 'start_address',
                'getOffAddress' => 'end_address',
                'srvMile' => new Expression('space(-1)'),
                'exceedTimeOrder' => new Expression('space(-1)'),
                'leftTimeUseCar' => new Expression('space(-1)'),
                'operator' => new  Expression('space(-1)'),
                'operateTime' => new Expression('space(-1)')
            ])->where(['and',
                ['is','driver_id',null],
                ['=', 'is_fake_success', Order::IS_FAKE_SUCCESS],
                ['<>','status',Order::STATUS_CANCEL],//920 bug 修复同步
                //['>','order_start_time',date('Y-m-d H:i:s')]
            ])
            ->andFilterWhere(['order_number'=>$where['orderNum']])
            ->andFilterWhere(['passenger_phone'=>$where['cabRunnerPhone']])
            ->andFilterWhere(['other_phone'=>$where['carManPhone']])
            ->asArray()->all();

        if (empty($results)) {
           return [];
        }
        $passengerInfoIds = ArrayHelper::getColumn($results, 'passengerInfoId');
        $passengerInfoIds = array_values(array_unique($passengerInfoIds));
        $passengerNames   = PassengerInfo::find()->where(['id' => $passengerInfoIds])
            ->select(['pid' => 'id', 'passenger_name'])
            ->indexBy('pid')
            ->asArray()
            ->all();
        $orderIds         = ArrayHelper::getColumn($results, 'orderId');
        $srvMiles         = OrderRulePrice::find()->where(['order_id' => $orderIds])->select([
            'oId' => 'order_id',
            'distance' => 'total_distance'
        ])->indexBy('oId')->asArray()->all();
        $results          = self::_arrayMergerExtraData(
            $results, 'orderId', $srvMiles, 'distance', 'srvMile'

        );
        $results          = self::_arrayMergerExtraData(
            $results, 'passengerInfoId', $passengerNames, 'passenger_name', 'cabRunner'
        );

        foreach ($results as $k => $v) {
            $results[$k]['exceedTimeOrder'] = intval((time() - strtotime($v['callOrderTime'])) / 60);
            $left_time = intval((strtotime($v['useCarTime']) - time()) / 60);
            $results[$k]['leftTimeUseCar']  = $left_time >= 0 ? $left_time : 0;
        }
        $results = PhoneNumber::mappingCipherToPhoneNumber($results,['cabRunnerPhone','carManPhone']);
        $results = Common::arraySortByKey($results, 'leftTimeUseCar','asc');

        return $results;

    }

    /**
     * @param array $data
     * @param $columnKey
     * @param $indexByColumnData
     * @param $extraKey
     * @param $extraDataKey
     * @return array
     */

    private static function _arrayMergerExtraData(array $data, $columnKey, $indexByColumnData, $extraKey, $extraDataKey)
    {
        if (empty($data) || empty($indexByColumnData)) {
            return $data;
        }
        foreach ($data as $k => $v) {
            if (in_array($v[$columnKey], array_keys($indexByColumnData))) {
                $data[$k][$extraDataKey] = $indexByColumnData[$v[$columnKey]][$extraKey];
            }
        }
        return $data;
    }

    /**
     * @return \yii\db\ActiveQuery
     */

    public function getDriverInfo()
    {
        return $this->hasOne(DriverInfo::className(), ['id' => 'driver_id']);
    }


}
