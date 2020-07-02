<?php

namespace driver\modules\order\controllers;

use common\controllers\ClientBaseController;
use common\events\OrderEvent;
use common\events\PassengerEvent;
use common\logic\BaseInfoLogic;
use common\logic\CouponTrait;
use common\logic\HttpTrait;
use common\models\DriverInfo;
use common\models\DriverWorkTime;
use common\models\Order;
use common\models\OrderPayment;
use common\models\OrderRulePrice;
use common\models\PushLoopMessage;
use common\util\Common;
use common\util\Json;

/**
 * Site controller
 */
class WorkController extends ClientBaseController
{
    use CouponTrait, HttpTrait;

    public $allowType = [2, 3];
    public $driverId = null;
    public $order = null; // 订单信息
    private $servicePhone = '';
    private $driverInfo = null;
    const MAX_ORDER = 1;

    public function init()
    {
        parent::init();
        $this->driverId = $this->userInfo['id'] ?? 0;
        $servicePhone = \Yii::$app->params['driverServicePhone'] ?? '0571-8690-809';
        $this->servicePhone = $servicePhone;
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $step = trim($request->post('step'));

        $statusMap = ['goPickup' => 3, 'arrived' => 4, 'startOrder' => 5, 'endOrder' => 6];

        if (!isset($statusMap[$step])) {
            return Json::message('不支持的状态');
        }

        $orderId = $request->post('orderId');
        $status = $statusMap[$step];

        $order = $this->getOrderInfo();
        if (!$order) {
            return Json::message('参数异常');
        }

        // 检测服务中的订单 step1
        if ($status == 3) {
            $number = $this->checkOnServiceOrders($orderId);
            if ($number > 0) {
                return Json::message('当前有服务中的订单，无法开始新的服务！');
            }
        }

        // 检测订单状态 step2
        if (intval($order->status) + 1 !== $status) {
            return Json::error(['orderStatus' => $order->status], 1, '订单当前状态不允许执行此操作');
        }

        // 订单结束相关事宜(如果是) step3
        if ($step == 'endOrder') {
            $orderPrice = $this->endOrder();
            if (is_string($orderPrice)) {
                return Json::message($orderPrice);
            }
        }

        // 更改订单状态 step4
        $reqParams = $this->getOrderParams($status);
        $responseData = self::httpPost('order.updateOrder', $reqParams);
        if ($responseData['code'] != 0) {
            return $this->asJson($responseData);
        }

        // 发送事件 step5
        $eventData = $this->patchEventParams($responseData, $statusMap[$step]);
        (new OrderEvent())->{$step}($eventData);
        // 返回数据
        return $this->asJson($responseData);
    }

    /**
     * @param $responseData
     * @param int $orderStatus
     * @return array
     */
    private function patchEventParams($responseData, $orderStatus = 0)
    {
        $request = \Yii::$app->request;
        $responseData['messageType'] = $orderStatus + 198; //添加消息类型
        $responseData['driverId'] = $this->driverId; // 司机ID
        $responseData['latitude'] = $request->post('latitude', '');
        $responseData['longitude'] = $request->post('longitude', '');

        $eventData = [
            'identity' => $this->tokenInfo,
            'orderId' => $request->post('orderId'),
            'extInfo' => $responseData,
        ];
        return $eventData;
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionStartPay()
    {
        $status = 7; // 发起收款
        // 检测订单状态
        $order = $this->getOrderInfo();
        if (!$order) {
            return Json::message('参数异常');
        }
        $orderId = $order->id;
        if (intval($order->status) + 1 !== $status) {
            return Json::error(['orderStatus' => $order->status], 1, '订单当前状态不允许执行此操作');
        }

        // 其他费用添加
        $reqParams = $this->getOrderParams($status);
        $responseData = self::httpPost('order.otherPay', $reqParams);
        if (is_string($responseData)) {
            return Json::message($responseData);
        }
        // 其他费用添加失败, 直接返回
        if ($responseData['code'] !== 0) {
            return $this->asJson($responseData);
        }
        $orderPayment = OrderPayment::findOne(['order_id' => $orderId]);
        if (!$orderPayment) {
            return Json::message('计价信息不正确');
        }
        $totalPrice = $responseData['data']['totalPrice'] ?? 0; // 总价
        $shouldPaidPrice = $totalPrice; // 应付金额
        // 检查是否使用优惠券 并修正支付金额
        if ($orderPayment->user_coupon_id > 0) {
            \Yii::info(['orderId' => $orderId, 'userCouponId' => $orderPayment->user_coupon_id], 'order_use_coupon');
            // 使用优惠券
            $reducePrice = $this->useUserCoupon($orderPayment->user_coupon_id, $totalPrice, $order->id);
            if (!$reducePrice) {
                \Yii::warning('用户优惠券' . $orderPayment->user_coupon_id . '使用失败!', 'order_use_coupon');
            } else {
                $shouldPaidPrice -= $reducePrice;
                $orderPayment->coupon_reduce_price = $reducePrice;
            }
            $responseData['data']['totalPrice'] = $shouldPaidPrice;
        }
        // 订单支付
        $remainPrice = $this->orderPay($responseData['data']);
        // 更新支付信息
        $orderPayment->total_price = $totalPrice;
        $orderPayment->paid_price = $shouldPaidPrice - $remainPrice; // 已支付金额
        $orderPayment->final_price = $shouldPaidPrice; // 实际需要支付总额
        $orderPayment->remain_price = $remainPrice; // 还需支付金额
        $orderPayment->tail_price = $remainPrice; // 尾款

        if (is_string($remainPrice)) {
            return Json::message($remainPrice);
        } elseif ($remainPrice == 0) {
            $orderPayment->pay_type = 1;
            $status = 8; // 支付完成
        }
        // 有支付金额则更新支付时间
        if ($totalPrice > $remainPrice) {
            $orderPayment->pay_time = date('Y-m-d H:i:s');
            // 发送消费事件
            (new PassengerEvent())->consumption($order->passenger_info_id);
        }
        $orderPayment->save();

        // 有尾款则发送事件
        $eventData = $this->patchEventParams($responseData, $status);
        if ($status == 8) { // 支付完成
            $this->finishOrder($orderId);
            (new OrderEvent())->paySuccess($eventData);
        } else { // 有尾款
            (new OrderEvent())->driverStartPay($eventData);
        }
        return Json::success();
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionGrabbing()
    {
        $reqParams = $this->getOrderParams(2); // 订单状态

        $result = self::httpPost('order.grabbing', $reqParams);
        if (is_string($result)) {
            return Json::message($result);
        }
        // 发送事件
        $eventData = $this->patchEventParams(array_merge($result, $reqParams));
        (new OrderEvent())->grabResult($eventData);
        // 返回结果
        return $this->asJson($result);
    }

    /**
     * 司机获取工作状态
     * @return array
     */
    public function actionGetStat()
    {
        $driver = $this->getDriverInfo();
        $stat = $this->trans2ClientStatus($driver);
        // 检测司机
        $result = $this->checkDriverStatus(3, true); // 3 顺风单查询模式
        if ($result === true) { // 正常返回
            return Json::success(['workStatus' => $stat, 'canFollowing' => true]);
        } elseif ($result[0] == 400) { // 400 满顺风单
            return Json::success(['workStatus' => $stat, 'canFollowing' => false]);
        } elseif (in_array($result[0], [405, 403])) {
            return Json::error(['phone' => $this->servicePhone], $result[0], $result[1]);
        }
        // 异常返回
        return Json::message($result[1], $result[0]);
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionChangeStat()
    {
        $request = \Yii::$app->request;
        // 城市检测
        $cityCode = trim($request->post('cityCode'));
        !$cityCode && $cityCode = trim($request->post('city'));
        if (!$cityCode) {
            $cityCode = $this->getDriverCityCode();
            $newCityCode = $cityCode ?? '0578';
        }
        // 获取输入内容
        $reqParams = $this->getBasicParams();
        $workStatus = intval($request->post('workStatus'));
        $result = $this->checkDriverStatus($workStatus);
        if (in_array($result[0], [405, 403])) {
            return Json::error(['phone' => $this->servicePhone], $result[0], $result[1]);
        }
        if ($result !== true) {
            return Json::message($result[1], $result[0]);
        }
        // 检查订单状态
        if ($workStatus == 0) {
            $number = $this->checkOnServiceOrders();
            if ($number > 0) {
                return Json::message('目前有服务中订单，无法退出');
            }
        }
        // 请求接口
        $this->patchStatus($reqParams);
        if (isset($newCityCode)) {
            $reqParams['city'] = $newCityCode;
        }
        $responseData = self::httpPost('account.driverWorkStatus', $reqParams);
        // 结果处理
        if (is_string($responseData)) {
            return Json::message($responseData);
        }
        if (isset($newCityCode) && $responseData['code'] == 0) {
            return Json::error(['city' => $newCityCode], 499, '定位司机城市');
        }
        // 获取轨迹id
        (!$responseData['data'] || !is_object($responseData['data'])) && $responseData['data'] = new \stdClass();
        $responseData['data']->sid = $this->getMapSid();
        // 单次工时计算
        $this->processWorkTime($workStatus);

        return $this->asJson($responseData);
    }

    /**
     * 获取费用详情
     * @return array
     */
    public function actionFeeDetails()
    {
        $request = \Yii::$app->request;
        $orderId = intval($request->post('orderId'));
        if (!$orderId) {
            $orderId = intval($request->get('orderId'));
        }
        $orderPrice = OrderRulePrice::find()
            ->select('total_price,base_price,path,path_price,duration,duration_price,night_distance,night_price,
            night_time,beyond_distance,beyond_price,road_price,parking_price,other_price,supplement_price,night_time')
            ->where(['order_id' => $orderId])
            ->andWhere(['category' => 1])
            ->limit(1)->asArray()->one();
        if (!$orderPrice) {
            return Json::message('订单数据异常');
        }
        // 金额格式化
        foreach ($orderPrice as $key => &$item) {
            if (substr($key, -5) == 'price') {
                $item = number_format($item, 2, '.', '');
            }
        }
        return Json::success(Common::key2lowerCamel($orderPrice));
    }

    /**
     * 司机轨迹上传
     * @return array|\yii\web\Response
     */
    public function actionAddPoints()
    {
        $request = \Yii::$app->request;
        $points = $request->post('points');
        if (!is_array($points) || !count($points)) {
            return Json::message('参数格式异常');
        }
        // 打包参数
        $carId = BaseInfoLogic::getDriverCarId($this->driverId);
        $extInfo = [
            'vehicleId' => $carId, // 车辆ID
            'state' => 1, // 车辆状态,必须 !!!!!!!!!!!!!
            'vehicleType' => 1, // 车辆级别,必填 !!!!!!!!!
            //'seats' => 4,
            //'battery' => 70,
            //'mileage' => 45.34,
        ];
        $responseData = ['code' => 1, 'message' => 'success', 'data' => new \stdClass()];
        // 循环发送点
        $group = count($points) > 5 ? true : false;
        $sendPoints = [];
        foreach ($points as $point) {
            // 数据判断
            if (empty($point['longitude'])) {
                return Json::message('经度数据异常');
            }
            if (empty($point['latitude'])) {
                return Json::message('纬度数据异常');
            }
            //输入数据格式化
            $point['timestamp'] = intval(microtime(1) * 1000); // 使用服务器时间
            (!isset($point['city']) || !$point['city']) && $point['city'] = '0578'; // 加入默认参数
            !isset($point['orderId']) && $point['orderId'] = '';
            // 合并数据
            $reqParams = array_merge($point, $extInfo);
            if ($group) {
                $sendPoints[] = $reqParams;
                continue;
            }
            $responseData = self::httpPost('map.addPoints', $reqParams);
            if ($responseData['code'] != 0) {
                break;
            }
        }
        if ($group) {
            $responseData = self::httpPost('map.batchPoints', ['points' => $sendPoints]);
        }
        return $this->asJson($responseData);
    }


    /**
     * 订单消息列表
     * @return array
     */
    public function actionNotify()
    {
//        $request = \Yii::$app->request;
//        $lastMsgId = intval($request->post('lastMsgId'));
//        $pushId = trim($request->post('pushId'));

        $list = PushLoopMessage::find()
            ->select('id,message_type,message_body,create_time')
            ->where(['accept_id' => strval($this->driverId)])// 接收人
            //->andWhere(['>', 'id', $lastMsgId])// 最后接受ID
            ->andWhere(['read_flag' => 0])// 未读
            ->andWhere(['>', 'expire_time', date('Y-m-d H:i')])// 过期时间
            ->limit(200)
            ->asArray()
            ->all();
        if (count($list)) {
            $ids = array_column($list, 'id');
            $result = PushLoopMessage::updateAll(['read_flag' => 1], ['id' => $ids]);
            if (!$result) {
                \Yii::debug('轮询消息已读状态更新失败');
            }
        }

        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    /**
     * 获取订单实时价格
     */
    public function actionCurrentPrice()
    {
        // 检查订单和计价
        $order = $this->getOrderInfo();
        if (!$order) {
            return Json::message('参数异常');
        }
        $orderRulePrice = OrderRulePrice::findOne(['order_id' => $order->id]);
        if (!$orderRulePrice) {
            return Json::message('订单计价异常');
        }
        // 请求实时价格信息
        $now = time();
        $startTime = strtotime($order->receive_passenger_time);
        $reqParams = [
            'orderId' => $order->id,
            'carId' => $order->car_id,
            'startTime' => $startTime * 1000,
            'endTime' => $now * 1000,
        ];
        $result = self::httpGet('valuation.currentPrice', $reqParams);
        // 请求结果检查
        if (is_string($result)) {
            return Json::message($result);
        }
        if ($result['code']) {
            return $this->asJson($result);
        }
        // 打包和返回数据
        $data = [
            'distance' => $result['data']['distance'] ?? 0.0,
            'price' => $result['data']['price'] ?? 0.0,
        ];
        $remainDistance = intval($orderRulePrice->base_kilo * 1000) - intval($data['distance']);
        if ($remainDistance < 0) {
            $remainDistance = 0;
        }
        $time = intval(($now - $startTime) / 60); // 单位(分钟)
        $remainTime = $orderRulePrice->base_minute - $time;
        if ($remainTime < 0) {
            $remainTime = 0;
        }
        $calcData = compact('remainDistance', 'time', 'remainTime');

        return Json::success(array_merge($data, $calcData));
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionGetPoints()
    {
        $request = \Yii::$app->request;

        $order = $this->getOrderInfo();
        if (!$order) {
            return Json::message('参数异常');
        }
        $driver = DriverInfo::findOne(['id' => $this->driverId]);
        if (!$driver->car_id) {
            return Json::message('车辆绑定信息异常');
        }
        // 参数整形
        $vehicleId = $order->car_id;
        $city = $driver->city_code;
        $startTime = strtotime($order->receive_passenger_time) * 1000;
        $endTime = strtotime($order->passenger_getoff_time) * 1000;
        $requestStart = intval($request->post('startTime'));
        if ($startTime < $requestStart && $requestStart < $endTime) {
            $startTime = $requestStart;
        }
        $requestEnd = intval($request->post('endTime'));
        if ($order->passenger_getoff_time < '2' || ($startTime < $requestEnd && $requestEnd < $endTime)) {
            $endTime = $requestEnd;
        }
        // 打包
        $reqPrams = compact('vehicleId', 'city', 'startTime', 'endTime');
        // 请求
        $result = self::httpGet('map.getPoints', $reqPrams);
        // 返回
        return $this->asJson($result);
    }

    /**
     * 获取订单信息并记录在成员属性中
     * @return bool|Order|null
     */
    private function getOrderInfo()
    {
        if ($this->order) {
            return $this->order;
        }
        $orderId = intval(\Yii::$app->request->post('orderId'));
        if (!$orderId) {
            return false;
        }
        $order = Order::findOne(['id' => $orderId, 'driver_id' => $this->driverId]);
        $this->order = $order;
        return $order;
    }

    /**
     * @return array|float|mixed|string
     */
    private function endOrder()
    {
        $request = \Yii::$app->request;
        $orderId = $request->post('orderId');
        // 检测/修改顺风单状态 step3-1
        $this->checkFollowingStatus();

        // 结束行程 step3-2
        $reqParams = $this->getOrderParams(6);
        unset($reqParams['status']); // 暂时不修改订单状态, 写入下车时间经纬度数据给计价使用
        $result = self::httpPost('order.updateOrder', $reqParams);
        if (isset($result['code']) && $result['code']) {
            return $result['message'];
        }

        // 订单计价 step3-3
        $price = $this->calcOrder($orderId);
        if (is_string($price)) {
            return $price;
        }
        // 写入司机流水 step3-4
//        $this->patchDriverIncome($price);
        // 计算优惠券 step3-5
        $this->patchPayment($orderId, $price);
        return $price;
    }

    /**
     * @param $orderId
     * @return array|float|mixed|string
     */
    private function calcOrder($orderId)
    {
        $responseData = self::httpGet('valuation.settlement', [], '/' . $orderId);
        if ($responseData['code']) {
            return $responseData['message'];
        }
        $price = $responseData['data']['price'] ?? 0;
        $result = $price > 0 ? floatval($price) : '计价结果不正确';
        return $result;
    }

    /**
     * 客户端状态转数据库状态
     * @param $reqParams
     */
    private function patchStatus(&$reqParams)
    {
        $workStatus = isset($reqParams['workStatus']) ? intval($reqParams['workStatus']) : 0;
        if ($workStatus == 1 || $workStatus == 0) { // 正常
            $reqParams['isFollowing'] = 0;
        } elseif ($workStatus == 3) { // 顺风
            $reqParams['isFollowing'] = 1;
            // 检车出车状态
            $driverInfo = $this->getDriverInfo();
            if ($driverInfo && $driverInfo->work_status < 1) {
                $reqParams['workStatus'] = 1;
            } else {
                unset($reqParams['workStatus']);
            }
        } elseif ($workStatus == 4) { // 退出车机
            $reqParams['csWorkStatus'] = 0;
        }
    }

    /**
     * @param $driverInfo
     * @return int
     */
    private function trans2ClientStatus($driverInfo)
    {
        if ($driverInfo->is_following) {
            return 3;
        }
        return $driverInfo->work_status ? 1 : 0;
    }

    /**
     * @param $orderId
     * @param $price
     */
    private function patchPayment($orderId, $price)
    {
        // 获取优惠券
        $orderCoupon = $this->useOrderCoupon($this->getOrderInfo(), $price);
        $couponPrice = $orderCoupon->maxAmount;
        $userCouponId = $orderCoupon->userCouponId;
        // 写入payment
        $orderPayment = new OrderPayment();
        $orderPayment->order_id = $orderId;
        $orderPayment->driver_id = intval($this->driverId);
        $orderPayment->final_price = $price;
        $orderPayment->total_price = $price;
        $orderPayment->remain_price = $price - $couponPrice;
        $orderPayment->tail_price = $price - $couponPrice;
        if ($userCouponId) {
            $orderPayment->user_coupon_id = $userCouponId;
            $orderPayment->coupon_reduce_price = $couponPrice;
        }
        $orderPayment->save();
    }

    /**
     * @param $orderId
     * @return bool
     */
    private function finishOrder($orderId)
    {
        $reqParams = [
            'status' => 8, // 支付完成
            'isPaid' => 1, // 已支付
            'orderId' => $orderId,
        ];
        $result = self::httpPost('order.updateOrder', $reqParams);
        if (is_string($result) || $result['code'] !== 0) {
            return false;
        }
        return true;
    }

    /**
     * @param $status
     * @return array
     */
    private function getOrderParams($status)
    {
        $request = \Yii::$app->request;
        $latitude = $request->post('latitude', '');
        $longitude = $request->post('longitude', '');
        $orderId = $request->post('orderId', 0);
        $reqParams = [
            'orderId' => $orderId, //订单ID
            'driverId' => $this->driverId, //司机ID
            'status' => $status, // 订单状态
            'driverStatus' => $status - 1, // 司机状态
            'driverLatitude' => $latitude,
            'driverLongitude' => $longitude,
        ];
        $order = $this->getOrderInfo();
        $ts = time() * 1000;
        if ($status == 3) {
            $reqParams['pickUpPassengerLatitude'] = $latitude;
            $reqParams['pickUpPassengerLongitude'] = $longitude;
            $reqParams['pickUpPassengerAddress'] = $this->getAddressByPoint($latitude, $longitude);
            $reqParams['pickUpPassengerTime'] = $ts;
        } elseif ($status == 4) { // 到达上车点
            $reqParams['driverArrivedTime'] = $ts;
        } elseif ($status == 5) { // 接到乘客
            $reqParams['receivePassengerTime'] = $ts;
            $reqParams['receivePassengerLatitude'] = $latitude;
            $reqParams['receivePassengerLongitude'] = $longitude;
            $reqParams['receivePassengerAddress'] = $this->getAddressByPoint($latitude, $longitude);
        } elseif ($status == 6) { // 到达目的地
            // 如果是包车单,获取目的地地址step3-2-1
            $endAddress = $this->getAddressByPoint($latitude, $longitude);
            if ($order && in_array($order->service_type, [5, 6])) {
                $reqParams['endAddress'] = $endAddress;
                $reqParams['endLatitude'] = $latitude;
                $reqParams['endLongitude'] = $longitude;
            }
            $reqParams['passengerGetoffAddress'] = $endAddress; // 下车地点
            $reqParams['passengerGetoffTime'] = $ts;
            $reqParams['passengerGetoffLatitude'] = $latitude;
            $reqParams['passengerGetoffLongitude'] = $longitude;
        } elseif ($status == 7) { // 添加其他费用
            $reqParams['roadPrice'] = floatval($request->post('roadPrice'));
            $reqParams['parkingPrice'] = floatval($request->post('parkPrice')); // 注意字段不同!!!
            $reqParams['otherPrice'] = floatval($request->post('otherPrice'));
        }
        return $reqParams;
    }

    /**
     * @param $responseData
     * @return array|float|mixed|string
     */
    private function orderPay($responseData)
    {
        $reqParams = [
            'orderId' => $responseData['orderId'],
            'yid' => $responseData['passengerId'],
            'price' => $responseData['totalPrice'],
        ];
        $result = self::httpPost('pay.orderPay', $reqParams);
        if (is_string($result)) {
            return $result; // 返回错误消息
        }
        if ($result['code'] != 0) {
            return '支付失败';
        }
        if (!isset($result['data']['remainPrice'])) { // 支付金额为0的情况
            return 0.0;
        }
        return floatval($result['data']['remainPrice']);
    }

    /**
     * @return bool
     */
    private function checkFollowingStatus()
    {
        $orderInfo = $this->getOrderInfo();
        // 订单数据异常|非顺丰单|订单行程未能结束, 不检测顺风单
        if (!$orderInfo || $orderInfo->is_following != 1 || $orderInfo->status < 5) {
            return false;
        }
        $reqParams = $this->getBasicParams();
        if (isset($reqParams['workStatus'])) {
            unset($reqParams['workStatus']); // 不更新司机工作状态
        }
        $reqParams['isFollowing'] = 0; // 关闭顺风单模式
        $result = self::httpPost('account.driverWorkStatus', $reqParams);
        if (isset($result['code']) && $result['code']) {
            return false;
        }
        return true;
    }

    private function checkDriverStatus($workStatus, $silent = false)
    {
        $driver = $this->getDriverInfo();
        if (!$driver) {
            return [401, '请登录后操作'];
        }
        if (!$driver->sign_status) {
            return [405, '您的账号已被解约,请联系客服' . $this->servicePhone];
        }
        if (!$silent && !$driver->use_status) {
            return [403, '您的账号已被冻结,请联系客服' . $this->servicePhone];
        }
        if (!trim($driver->car_id)) {
            return [404, '您还未绑定车辆'];
        }
        // 检测今日顺风单!!!!!
        // 此处需要优化, 不应该查询订单表, 应该更新司机相关的某个值
        if ($workStatus == 3) {
            $todayCondition = ['between', 'order_start_time', date('Y-m-d'), date('Y-m-d', time() + 86400)];
            $orders = Order::find()
                ->where(['driver_id' => $this->driverId])
                ->andWhere($todayCondition)
                ->andWhere(['and', ['is_following' => 1], ['is_cancel' => 0], ['>', 'status', 5]])
                ->count();
            if ($orders >= self::MAX_ORDER) {
                return [400, '今天已用完顺风单机会'];
            }
        }
        return true;
    }

    private function checkOnServiceOrders($orderId = false)
    {
        $query = Order::find()
            ->where(['driver_id' => $this->driverId])
            ->andWhere(['between', 'status', 3, 5]);
        if ($orderId) {
            $query->andWhere(['<>', 'id', $orderId]);
        }
        $serviceOrders = $query->count();
        return $serviceOrders;
    }

    // 根据经纬度获取城市code
    private function getDriverCityCode()
    {
        $reqParams = \Yii::$app->request->post();

        $responseData = self::httpGet('map.getCity', $reqParams);
        if (!is_array($responseData)) {
            return false;
        }
        return $responseData['data']['cityCode'] ?? false;
    }

    /**
     * 工作时间记录
     * @param $status
     * @return bool
     */
    private function processWorkTime($status)
    {
        if ($status > 0) { // 出车
            $workTime = new DriverWorkTime();
            $workTime->driver_id = $this->driverId; // 司机ID
            $workTime->work_start = date('Y-m-d H:i:s');
//            $workTime->work_day = date('Y-m-d');
            return $workTime->save();
        } else { // 收车
            $last = DriverWorkTime::find()
                ->where(['driver_id' => $this->driverId])
                ->orderBy('work_start DESC')
                ->limit(1)->one();
            if (!$last || strtotime($last->work_start) >= time()) {
                return false;
            }
            /*
            $now = time(); // 减少误差导致的算术异常
            $dayEnd = date('Y-m-d', $now);
            if ($dayEnd == $last->work_day) {
                $last->work_end = date('Y-m-d H:i:s', $now);
                $last->work_duration = $this->getWorkLength($last->work_start, $last->work_end);
                return $last->save();
            }
            // 头一天
            $last->work_end = $last->work_day . ' 23:59:59';
            $last->work_duration = $this->getWorkLength($last->work_start, $last->work_end);
            $last->save();
            // 后一天
            $next = new DriverWorkTime();
            $next->driver_id = $this->driverId;
            $next->work_start = $dayEnd . ' 00:00:00';
            $next->work_day = date('Y-m-d', $now);
            $next->work_end = date('Y-m-d H:i:s', $now);
            $next->update_time = $next->work_end;
            $next->work_duration = $this->getWorkLength($next->work_start, $next->work_end);
            return $next->save();
            */
            $last->work_end = date('Y-m-d H:i:s');
            $last->work_duration = $this->getWorkLength($last->work_start, $last->work_end);
            return $last->save();
        }
    }

    /**
     * 根据两个时刻字符串返回带两位小数的分钟时长
     * @param $start
     * @param $end
     * @return float
     */
    private function getWorkLength($start, $end)
    {
        $minute = (strtotime($end) - strtotime($start)) / 60;
        return round($minute, 2);
    }

    /**
     * 根据经纬度获取地址文本
     * @param $lat
     * @param $long
     * @return string
     */
    private function getAddressByPoint($lat, $long)
    {
        $reqParams = [
            'latitude' => $lat,
            'longitude' => $long,
        ];
        $responseData = self::httpGet('map.getCity', $reqParams);
        if ($responseData['code'] != 0) {
            return '';
        }
        return $responseData['data']['formateedAddress'] ?? '';
    }

    /**
     * 获取基础参数
     * @return array
     */
    private function getBasicParams()
    {
        $request = \Yii::$app->request;
        // 注意key不一致
        $cityCode = $request->post('city', '');
        !$cityCode && $cityCode = $request->post('cityCode', '');
        $reqParams = [
            'id' => $this->driverId,
            'city' => $cityCode,
            'speed' => floatval($request->post('speed')),
            'latitude' => floatval($request->post('latitude')),
            'longitude' => floatval($request->post('longitude')),
            'workStatus' => intval($request->post('workStatus')), // 工作状态
        ];
        return $reqParams;
    }

    /**
     * @param bool $forceUpdate
     * @return string
     */
    private function getMapSid($forceUpdate = false)
    {
        $redis = \Yii::$app->redis;
        $sidKey = 'mapConfigSid:';
        $sid = $redis->get($sidKey);
        if (!$sid || $forceUpdate) {
            $response = self::httpGet('map.getSid');
            if ($response['code'] == 0) {
                $sid = $response['data']['sid'] ?? '0';
                $redis->setex($sidKey, 600, $sid);
            } else {
                \Yii::debug($response, 'get_map_config_error');
            }
        }
        return $sid;
    }

    /**
     * @return DriverInfo|null
     */
    private function getDriverInfo()
    {
        if ($this->driverInfo !== null) {
            return $this->driverInfo;
        }
        $driverInfo = DriverInfo::findOne(['id' => $this->driverId]);
        $this->driverInfo = $driverInfo;
        return $driverInfo;
    }
}
