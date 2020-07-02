<?php
/**
 * Created by Zend Studio
 * User: lijin
 * Date: 2018年8月19日
 * Time: 下午8:24:16
 */
namespace driver\modules\ucenter\controllers;

use common\controllers\BaseController;
use common\logic\TagLogic;
use common\models\DriverInfo;
use common\models\FlightNumber;
use common\util\Json;
use common\models\CarInfo;
use common\models\CarBaseInfo;
use common\models\DriverBaseInfo;
use common\models\Order;
use common\services\traits\ModelTrait;
use common\models\PassengerInfo;
use common\models\OrderRulePrice;
use common\models\OrderAdjustRecord;
use common\models\DriverAdvice;
use common\models\EvaluateDriver;
use common\logic\MessageLogic;
use Codeception\Lib\Console\Message;
use common\util\Common;
use common\models\City;
use common\models\DriverAddress;
use common\models\OrderCancelRecord;
use common\models\Feedback;
use common\logic\FileUrlTrait;
use common\models\EvaluateDriverToPassenger;
use yii\helpers\ArrayHelper;

class UserCenterController extends BaseController
{
    use ModelTrait;
    use FileUrlTrait;
    
    public $driverId = null;
    
    public function init()
    {
        parent::init();
        // 检测token
        if (!$this->tokenInfo) {
            return;
        }
        $tokenSub = explode('_', $this->tokenInfo->sub);
        if ($tokenSub[0] != 2) {
            return;
        }
        $this->driverId = intval($tokenSub[2]);
    }
    //个人资料
    public function actionUserInfo(){
        $driverId = $this->driverId;
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        $driverInfo = DriverInfo::find()
        ->select(['id','driver_name','phone_number','city_code','car_id','head_img','use_status'])
        ->where(['id'=>$driverId])->asArray()->one();
        $address = DriverBaseInfo::find()->select(['address'])->where(['id'=>$driverId])->scalar();
        $driverAddress = Common::addressDecryption($address);
        if (!CarInfo::checkCar($driverInfo['car_id'])){
            return Json::message('车辆信息不存在');
        }
        $cityName = City::find()->select(['city_name'])->where(['city_code'=>$driverInfo['city_code']])->scalar();
        //电话号码解密
        $phone= Common::getPhoneNumber([['id'=>$driverInfo['id']]], 2);
        $driverInfo['phone_number'] = $phone[0]['phone'];
        $plate_number = CarInfo::find()->select(['plate_number'])->where(['id'=>$driverInfo['car_id']])->scalar();
        $vin_number = CarBaseInfo::find()->select(['vin_number'])->where(['id'=>$driverInfo['car_id']])->scalar();
        
        //司机星级
        $todayTime = date("Y-m-d 00:00:00", time());
        $grade = EvaluateDriver::find()->select('AVG(grade)')->where(['driver_id'=>$driverId])->andWhere(['<', 'create_time', $todayTime])->scalar();
        $driverInfo['grade'] = sprintf("%.1f", $grade);
        $driverInfo['address'] = $driverAddress ? $driverAddress : '';
        $driverInfo['plate_number'] = $plate_number ? $plate_number : '';
        $driverInfo['vin_number'] = $vin_number ? $vin_number : '';
        $driverInfo['city_name'] = $cityName ? $cityName : '';
        $this->patchUrl($driverInfo, ['head_img']);
        $driverInfo = $this->keyMod($driverInfo);
        return Json::success($driverInfo);
    }
    
    //修改个人资料
    public function actionUpdateUserInfo(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['id'] = $this->driverId;
        $requestData['address'] = !empty($requestData['address']) ? trim($requestData['address']) : '';
        $requestData['addressLongitude'] = !empty($requestData['longitude']) ? trim($requestData['longitude']) : '';
        $requestData['addressLatitude'] = !empty($requestData['latitude']) ? trim($requestData['latitude']) : '';
        $requestData['phoneNumber'] = !empty($requestData['phoneNumber']) ? trim($requestData['phoneNumber']) : '';
        
        if (empty($requestData['id']) || empty($requestData['phoneNumber']) || empty($requestData['address']) || empty($requestData['addressLongitude']) || empty($requestData['addressLatitude'])){
            return Json::message('请传递完整的数据');
        }
        $res = Common::updateDriverInfo($requestData);
        \Yii::info(json_encode($requestData),'php_request_data');
        \Yii::info(json_encode($res),'java_api_result');
        if ($res['code'] == 0){
            //保存司机地址信息到deiver_address
            $driverAddress = new DriverAddress();
            $driverAddress->setAttribute('driver_id', $requestData['id']);
            $driverAddress->setAttribute('address', $requestData['address']);
            $driverAddress->setAttribute('address_longitude', $requestData['addressLongitude']);
            $driverAddress->setAttribute('address_latitude', $requestData['addressLatitude']);
            $driverAddress->save();
            return Json::message('保存成功', 0);
        }else{
            return Json::message('保存失败');
        }
    }
    
    //行程记录
    public function actionDriveRecord(){
        $request = $this->getRequest();
        $orderType = $request->post('orderType',1);
        $driverId = $this->driverId ?? 256;
        \Yii::info(json_encode(['orderType'=>$orderType,'driverId'=>$driverId]));
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        if (empty($orderType)){
            return Json::message('请传递类型参数');
        }
        $driveRecord = $this->getOrderList($driverId, $orderType);

        $passengerIds = array_unique(array_column($driveRecord['list'], 'passenger_info_id'));
        $passengerPhoneArr = PassengerInfo::find()->select(['id'])->where(['id'=>$passengerIds])->asArray()->all();
        $phones = Common::getPhoneNumber($passengerPhoneArr, 1);
        $passengerPhones = array_unique(array_column($phones, 'phone', 'id'));
        //帮他人叫车，他人手机号码
        $otherPhone = array_filter(array_unique(array_column($driveRecord['list'], 'other_phone')));
        $otherPhones = [];
        foreach ($otherPhone as $k=>$v){
            $otherPhones[$k]['encrypt'] = $v;
        }
        if (!empty($otherPhones)){
            $result = Common::getPhoneNumberByEncrypt(array_values($otherPhones));
            $otherPhonesArr = array_column($result, 'phone', 'encrypt');
        }
        if (!empty($driveRecord['list'])){
            $nowTime = time();
            $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
            $passengerImg = PassengerInfo::find()->select(['head_img','id'])->asArray()->all();
            $passengerIdsStr = implode(",", $passengerIds);
            $sql = "SELECT flight_number,passenger_info_id FROM (SELECT create_time,passenger_info_id,flight_number FROM tbl_flight_number ORDER BY create_time DESC) AS t WHERE passenger_info_id IN (".$passengerIdsStr.") GROUP BY t.passenger_info_id";
            $flightInfo = \Yii::$app->db->createCommand($sql)->queryAll();
            $flightNumber = array_column($flightInfo,'flight_number','passenger_info_id');
            $passengerImgList = array_unique(array_column($passengerImg, 'head_img', 'id'));
            foreach ($driveRecord['list'] as $key=>$value){
                $driveRecord['list'][$key]['head_img'] = !empty($passengerImgList[$value['passenger_info_id']]) ? $passengerImgList[$value['passenger_info_id']] : '';
                $driveRecord['list'][$key]['passenger_phone'] = !empty($passengerPhones[$value['passenger_info_id']]) ? $passengerPhones[$value['passenger_info_id']] : '';
                $driveRecord['list'][$key]['other_phone'] = !empty($otherPhonesArr[$value['other_phone']]) ? $otherPhonesArr[$value['other_phone']] : '';
                if ($value['status'] == 7 || $value['status'] == 8){
                    $totalPrice= OrderRulePrice::find()->select(['total_price'])->where(['order_id'=>$value['id'],'category'=>1])->scalar();//订单价格
                    $driveRecord['list'][$key]['total_price'] = $totalPrice ? $totalPrice : '0.00';
                }
                if ($value['service_type'] == 3){//接机单添加航班号
                    $driveRecord['list'][$key]['flight_number'] = !empty($flightNumber[$value['passenger_info_id']]) ? $flightNumber[$value['passenger_info_id']] : '';
                }
                $orderStartTime = strtotime($value['order_start_time']);
                if ($value['status'] == 2 && ($orderStartTime - $nowTime) <= 3600){//即将开始
                    $driveRecord['list'][$key]['on_one_hour'] = true;
                }
                //取场景用车标签
                $tag = TagLogic::showBatch(explode(",", $value['user_feature']));
//                $tagArr = array_column($tag, 'tag_img');
                $tagArr = [];
                foreach ($tag as $k => $v) {
                    $tagArr[$k]['tag_img'] = isset($v['tag_img']) ?  $ossFileUrl.$v['tag_img'] : '';
                    $tagArr[$k]['id'] = isset($v['id']) ? $v['id'] : '';
                }
                $driveRecord['list'][$key]['tag_list'] = array_values($tagArr);
            }
        }
        $driveRecord = $this->keyMod($driveRecord);
        return Json::success($driveRecord);
    }
    
    //行程记录详情
    public function actionRecordDetail(){
        $request = $this->getRequest();
        $driverId = $this->driverId ?? 256;
        $orderId = $request->post('orderId',3);
        if (!Order::checkOrder($orderId)){
            return Json::message('订单不存在');
        }
        $orderInfo = Order::find()
        ->select(['passenger_info_id','order_number','start_address','start_longitude','start_latitude','end_address','end_longitude','end_latitude',
        'order_start_time','passenger_phone','mapping_number','status','service_type','is_cancel','is_adjust','is_paid','other_phone','order_type','passenger_getoff_address'])
        ->where(['id'=>$orderId,'driver_id'=>$driverId])->asArray()->one();
        //订单详细价格
        if ($orderInfo['status'] == 7 || $orderInfo['status'] == 8){
            $category = 1;
        }else{
            $category = 0;
        }
        $orderPriceDetail = OrderRulePrice::find()->select(['total_distance','total_price','path','path_price','duration','duration_price','beyond_distance',
            'beyond_price','night_price','night_time','road_price','parking_price','other_price','dynamic_discount','base_price','lowest_price','supplement_price'])
            ->where(['order_id'=>$orderId])->andWhere(['category'=>$category])->asArray()->one();
        $orderDetail = !empty($orderPriceDetail) ? array_merge($orderInfo, $orderPriceDetail) : $orderInfo;
        $orderDetail['cancel_price'] = $orderDetail['lowest_price'];
        //调账订单取调账前价格、调账原因
        if ($orderDetail['is_adjust'] == 1){
            $adjustInfo = OrderAdjustRecord::find()->select(['old_cost','reason_type','reason_text'])->where(['order_id'=>$orderId])->asArray()->one();
            $orderDetail['old_cost'] = $adjustInfo['old_cost'];
            $orderDetail['adjust_reason'] = !empty($adjustInfo['reason_text']) ? $adjustInfo['reason_text'] : ''; //调账原因类型  *************待定
        }
        //是否有取消费
        if ($orderDetail['is_cancel'] == 1){
            $cancelPrice = OrderCancelRecord::find()->select(['cancel_cost'])->where(['order_id'=>$orderId])->scalar();
            $orderDetail['cancel_price'] = $cancelPrice ?? '';
        }

        if ($orderInfo['order_type'] == 2){//为他人叫车取他人手机号
            $result = Common::getPhoneNumberByEncrypt([['encrypt'=>$orderInfo['other_phone']]]);
            $orderDetail['passenger_phone'] = $result[0]['phone'];
        }else{//自己叫车
            $phone = Common::getPhoneNumber([['id'=>$orderDetail['passenger_info_id']]], 1);
            $orderDetail['passenger_phone'] = $phone[0]['phone'];
        }
        
        $nowTime = time();
        $orderStartTime = strtotime($orderDetail['order_start_time']);
        if ($orderDetail['status'] == 2 && ($orderStartTime - $nowTime) > 0 && ($orderStartTime - $nowTime) <= 3600){//即将开始
            $orderDetail['on_one_hour'] = true;
        }
        $orderDetail = $this->keyMod($orderDetail);
        return Json::success($orderDetail);
    }
    
    //阶段统计-按月
    /* public function actionMonthSummary(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('请传递司机id');
        }
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        $year = $request->post('startTime');
        $currentYear = date("Y",time());
        //用户没查询年份，从当前月份开始计算当前年份每月的数据，否则从查询年份12月份开始差所有月份数据
        if (!empty($year)){
            $month = '12';
        }else{
            $month = date("n", time());
            $year = $currentYear;
        }
        //最早能显示的日期
        $lastMonth = date(($currentYear-2)."-m");
        //循环月份取数据
        $driverMonthOrder = array();
        for ($month; $month > 0; $month--){
            $monthStartTime = $year."-".$month."-01"; 
            $monthEndTime = $month == 12 ? ($year+1)."-01-01" : $year."-".($month+1)."-01";
            if ($monthStartTime > $lastMonth){
                $driverOrder = Order::find()->select(['id'])->where(['driver_id'=>$driverId,'status'=>8, 'is_paid'=>1])
                ->andWhere(['>','driver_grab_time',$monthStartTime])->andWhere(['<','driver_grab_time',$monthEndTime])->asArray()->all();
                if (!empty($driverOrder)){
                    $orderCount = count($driverOrder);
                    $orderIdStr = array();
                    foreach ($driverOrder as $item){
                        $orderIdStr[] = $item['id'];
                    }
                    $monthOrderData = OrderRulePrice::find()->select(['SUM(total_distance) AS allDistance','SUM(total_time) AS allTimes','SUM(total_price) AS money'])
                    ->where(['category'=>1])->andWhere(['IN','order_id',$orderIdStr])->asArray()->one();
                    $month = strlen($month) == 2 ? $month : "0".$month;
                    $monthOrderData['tradeMonth'] = $year."-".$month;
                    $monthOrderData['orderCount'] = $orderCount;
                    $monthOrderData['allTimes'] = sprintf('%.2f',$monthOrderData['allTimes']/60);
                    $driverMonthOrder['list'][] = $monthOrderData;
                }
            }
        }
        return Json::success($driverMonthOrder);
    } */
    
    //阶段统计-按月(new)
    public function actionMonthSummary(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('请传递司机id');
        }
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        $year = $request->post('startTime');
        $currentYear = date("Y",time());
        //用户没查询年份，从当前月份开始计算当前年份每月的数据，否则从查询年份12月份开始差所有月份数据
        if (!empty($year)){
            $month = '12';
        }else{
            $month = date("n", time());
            $year = $currentYear;
        }
        //最早能显示的日期
        $lastMonth = date(($currentYear-2)."-m");
        $order = Order::find()->select(['id','driver_grab_time'])->where(['driver_id'=>$driverId,'status'=>8, 'is_paid'=>1])
        ->andWhere(['>','driver_grab_time', $year."-00-00 00:00:00"])->andWhere(['<','driver_grab_time',($year+1)."-00-00 00:00:00"])
        ->asArray()->all();
        $orderIdStr = array_column($order, 'id');
        //取订单详情数据
        $orderData = OrderRulePrice::find()->select(['order_id','total_distance','total_time','total_price'])
        ->where(['category'=>1])->andWhere(['IN','order_id',$orderIdStr])->indexBy('order_id')->asArray()->all();
        
        //循环月份取数据
        $driverMonthOrder = array();
        for ($month; $month > 0; $month--){
            $endMonth = $month+1;
            $startMonth = strlen($month) == 2 ? $month : "0".$month;
            $endMonth = strlen($endMonth) == 2 ? $endMonth : "0".$endMonth;
            $monthStartTime = $year."-".$startMonth."-01";
            $monthEndTime = $startMonth == 12 ? ($year+1)."-01-01" : $year."-".$endMonth."-01";
            if ($monthStartTime > $lastMonth){
                $monthOrderData = [];
                $money = 0;
                $times = 0;
                $count = 0;
                $distance = 0;
                if (!empty($order)){
                    foreach ($order as $key=>$value){
                        if (($value['driver_grab_time'] > $monthStartTime) && ($value['driver_grab_time'] < $monthEndTime)){
                            $money += $orderData[$value['id']]['total_price'] ?? 0;
                            $distance += $orderData[$value['id']]['total_distance'] ?? 0;
                            $times += $orderData[$value['id']]['total_time'] ?? 0;
                            $count++;
                        } 
                    }
                    $monthOrderData['tradeMonth'] = $year."-".$startMonth;
                    $monthOrderData['money'] = sprintf('%.2f', $money);
                    $monthOrderData['allDistance'] = sprintf('%.2f', $distance);
                    $monthOrderData['orderCount'] = $count;
                    $monthOrderData['allTimes'] = sprintf('%.2f',$times/60);
                }
            }
            if ($count > 0){
                $driverMonthOrder['list'][] = $monthOrderData;
            }
        }      
        return Json::success($driverMonthOrder);
    }
    
    
    //阶段统计-按天
    /* public function actionDaySummary(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('请传递司机id');
        }
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        $startTime = $request->post('startTime');
        $nowTime = time();
        $currentYear = date("Y", $nowTime);
        //用户没查询月份，从当前月份开始计算每一天的数据，否则查询获取的月份的数据
        if (!empty($startTime)){
            $day = date("t", strtotime($startTime));
            $month = date("n",strtotime($startTime));
            $year = date("Y", strtotime($startTime));
        }else{
            $day = date("d", $nowTime);
            $month = date("n", $nowTime);
            $year = $currentYear;
            $startTime = date("Y-m", $nowTime);
        }
        //最早能显示的日期
        $lastMonth = date(($currentYear-2)."-m");
        //循环天取数据
        $driverDayOrder = array();
        if ($startTime > $lastMonth){
            for ($day; $day > 0; $day--){
                $dayStartTime = $year."-".$month."-".$day;
                $dayEndTime = $year."-".$month."-".$day." 23:59:59";
                $driverOrder = Order::find()->select(['id'])->where(['driver_id'=>$driverId,'status'=>8, 'is_paid'=>1])
                ->andWhere(['>','driver_grab_time',$dayStartTime])->andWhere(['<','driver_grab_time',$dayEndTime])->asArray()->all();
                if (!empty($driverOrder)){
                    $orderCount = count($driverOrder);
                    $orderIdStr = array();
                    foreach ($driverOrder as $item){
                        $orderIdStr[] = $item['id'];
                    }
                    $dayOrderData = OrderRulePrice::find()->select(['SUM(total_distance) AS allDistance','SUM(total_time) AS allTimes','SUM(total_price) AS money'])
                    ->where(['category'=>1])->andWhere(['IN','order_id',$orderIdStr])->asArray()->one();
                    $month = strlen($month) == 2 ? $month : "0".$month;
                    $day = strlen($day) == 2 ? $day : "0".$day;
                    $dayOrderData['tradeDay'] = $year."-".$month."-".$day;
                    $dayOrderData['orderCount'] = $orderCount;
                    $dayOrderData['allTimes'] = sprintf('%.2f',$dayOrderData['allTimes']/60);
                    $driverDayOrder['list'][] = $dayOrderData;
                }
            }
        }
        return Json::success($driverDayOrder);
    } */
    
    
    //阶段统计-按天(new)
    public function actionDaySummary(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('请传递司机id');
        }
        if (!DriverInfo::checkDriver($driverId)){
            return Json::message('司机未注册');
        }
        $startTime = $request->post('startTime');
        $nowTime = time();
        $currentYear = date("Y", $nowTime);
        //用户没查询月份，从当前月份开始计算每一天的数据，否则查询获取的月份的数据
        if (!empty($startTime)){
            $day = date("t", strtotime($startTime));
            $month = date("n",strtotime($startTime));
            $year = date("Y", strtotime($startTime));
        }else{
            $day = date("d", $nowTime);
            $month = date("n", $nowTime);
            $year = $currentYear;
            $startTime = date("Y-m", $nowTime);
        }
        //最早能显示的日期
        $lastMonth = date(($currentYear-2)."-m");
        $month = strlen($month) == 2 ? $month : "0".$month;
        $order = Order::find()->select(['id','driver_grab_time'])->where(['driver_id'=>$driverId,'status'=>8, 'is_paid'=>1])
        ->andWhere(['>','driver_grab_time',$year."-".$month."-00 00:00:00"])->andWhere(['<','driver_grab_time',$year."-".($month+1)."-00 00:00:00"])
        ->asArray()->all();
        
        $orderIdStr = array_column($order, 'id');
        //取订单详情数据
        $orderData = OrderRulePrice::find()->select(['order_id','total_distance','total_time','total_price'])
        ->where(['category'=>1])->andWhere(['IN','order_id',$orderIdStr])->indexBy('order_id')->asArray()->all();
        //循环天取数据
        $driverDayOrder = array();
        if ($startTime > $lastMonth){
            for ($day; $day > 0; $day--){
                $day = strlen($day) == 2 ? $day : "0".$day;
                $dayStartTime = $year."-".$month."-".$day;
                $dayEndTime = $year."-".$month."-".$day." 23:59:59";
                if (!empty($order)){
                    $dayOrderData = [];
                    $money = 0;
                    $times = 0;
                    $count = 0;
                    $distance = 0;
                    foreach ($order as $key=>$value){
                        if (($value['driver_grab_time'] > $dayStartTime) && ($value['driver_grab_time'] < $dayEndTime)){
                            $money += $orderData[$value['id']]['total_price'] ?? 0;
                            $distance += $orderData[$value['id']]['total_distance'] ?? 0;
                            $times += $orderData[$value['id']]['total_time'] ?? 0;
                            $count++;
                        }
                    }
                    $dayOrderData['tradeDay'] = $year."-".$month."-".$day;
                    $dayOrderData['money'] = sprintf('%.2f', $money);
                    $dayOrderData['allDistance'] = sprintf('%.2f', $distance);
                    $dayOrderData['orderCount'] = $count;
                    $dayOrderData['allTimes'] = sprintf('%.2f',$times/60);
                }
                if ($count > 0){
                    $driverDayOrder['list'][] = $dayOrderData;
                }
            }
        }
        return Json::success($driverDayOrder);
    }
    
    
    //首页今日流水、单数
    public function actionTodaySummary(){
        $driverId = $this->driverId;
        $startTime = date("Y-m-d 00:00:00", time());
        $endTime = date("Y-m-d 23:59:59", time());
        $order = Order::find()->select(['id'])->where(['>','order_start_time',$startTime])->andWhere(['<','order_start_time',$endTime])->andWhere(['status'=>8,'is_paid'=>1,'driver_id'=>$driverId])->asArray()->all();
        $orderIds = array_unique(array_column($order, 'id'));
        $money = OrderRulePrice::find()->select('SUM(total_price)')->where(['order_id'=>$orderIds,'category'=>1])->scalar();
        $orderCount = count($order);
        $data['money'] = $money ?? 0;
        $data['orderCount'] = $orderCount;
        return Json::success($data);
    }
    
    //意见反馈
    public function actionAdvice(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        $adviceType = trim($request->post('adviceType'));
        $adviceDesc = Common::compile_str(trim($request->post('adviceDesc')));
        //$phone = $request->post('phone');
        $adviceImage = trim($request->post('adviceImage'));
        if (empty($adviceDesc) || empty($adviceType)){
            return Json::message('请传递完整参数');
        }
        /**
        $driverAdvice = new DriverAdvice();
        $driverAdvice->setAttribute('driver_id', $driverId);
        $driverAdvice->setAttribute('advice_type', $adviceType);
        $driverAdvice->setAttribute('advice_desc', $adviceDesc);
        $driverAdvice->setAttribute('phone', $phone);
        $driverAdvice->setAttribute('advice_image', $adviceImage);
        if (!$driverAdvice->save()){
            return Json::message('提交失败');
        }*/
        //$driverId = 222;
        $Detail = DriverInfo::getDriverDetail($driverId);
        $driver_name  = isset($Detail['driver_name']) ? $Detail['driver_name'] : '';
        $driver_phone = isset($Detail['phone_number']) ? $Detail['phone_number'] : '';
        $Feedback = new Feedback();
        $Feedback->setAttribute('user_id', 0);
        $Feedback->setAttribute('driver_id', $driverId);
        $Feedback->setAttribute('user_name', $driver_name);
        $Feedback->setAttribute('phone', $driver_phone);
        $Feedback->setAttribute('terminal', '2');
        $Feedback->setAttribute('category', $adviceType);
        $Feedback->setAttribute('content', $adviceDesc);
        $Feedback->setAttribute('advice_image', $adviceImage);
        if (!$Feedback->save()){
            \Yii::info($Feedback->getErrors(),"actionAdvice_1");
            return Json::message('提交失败');
        }
        return Json::message('提交成功', 0);
    }

    //获取星级评价对应的标签
    public function actionStarLabel(){
        $rs = \Yii::$app->params['driverCommentPassenger'];
        return Json::success($rs);
    }

    //提交评论
    public function actionFeedback(){
        $request = $this->getRequest();
        $requestData = $request->post();
        \Yii::info($requestData, "feedback0");
        $grade = isset($requestData['grade']) ? trim($requestData['grade']) : '';
        $label = isset($requestData['label']) ? trim($requestData['label']) : '';
        $content = isset($requestData['content']) ? trim($requestData['content']) : '';
        $content = Common::compile_str($content);
        $orderId = isset($requestData['orderId']) ? trim($requestData['orderId']) : '';
        $passengerId = isset($requestData['passengerId']) ? trim($requestData['passengerId']) : 0;
        $driverId = $this->driverId;

        if (empty($grade) || empty($label) || empty($orderId)){
            return Json::message('请传递完整参数');
        }
        $EvaluateDriverToPassenger = new EvaluateDriverToPassenger();
        $EvaluateDriverToPassenger->setAttribute('grade', $grade);
        $EvaluateDriverToPassenger->setAttribute('label', $label);
        $EvaluateDriverToPassenger->setAttribute('content', $content);
        $EvaluateDriverToPassenger->setAttribute('order_id', $orderId);
        $EvaluateDriverToPassenger->setAttribute('passenger_id', $passengerId);
        $EvaluateDriverToPassenger->setAttribute('driver_id', $driverId);
        if (!$EvaluateDriverToPassenger->save()){
            \Yii::info($EvaluateDriverToPassenger->getErrors(), "feedback1");
            return Json::message('提交失败');
        }
        return Json::message('提交成功', 0);
    }

    /**
     * 返回未评价的订单信息
     * @return array
     */
    public function actionNoEvaluateOrder(){
        $driverId = $this->driverId;
        //$driverId = 222;
        if (empty($driverId)){
            return Json::message('缺少司机id参数');
        }
        $order = order::find()->select(['id','passenger_info_id'])->where(["driver_id"=>$driverId, "is_evaluate_driver"=>0])
            ->andWhere([">=", "status", 7])
            ->orderBy("id DESC")->asArray()->one();
        return Json::success(Common::key2lowerCamel($order));
    }

    //消息列表
    public function actionMessageList(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('缺少司机id参数');
        }
        $messageList = MessageLogic::getMessageList(2, $driverId);
        $messageList['list'] = $this->keyMod($messageList['list']);
        return Json::success($messageList);
    }
    
    //消息详情
    public function actionMessageDetail(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        $messageId = $request->post('messageId');
        if (empty($messageId) || empty($driverId)){
            return Json::message('请传递完整参数');
        }
        $messageDetail = MessageLogic::getMessageDetail($messageId, $driverId);
        $messageDetail = $this->keyMod($messageDetail);
        return Json::success($messageDetail);
    }
    
    //删除单条消息
    public function actionDeleteMessage(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        $messageId = $request->post('messageId');
        if (empty($messageId) || empty($driverId)){
            return Json::message('请传递完整参数');
        }
        $result = MessageLogic::deleteSingelMessage($messageId, $driverId);
        if ($result){
            return Json::message('删除成功',0);
        }
        return Json::message('删除失败');
    }
    
    //删除全部消息
    public function actionDeleteBatchMessage(){
        $request = $this->getRequest();
        $driverId = $this->driverId;
        if (empty($driverId)){
            return Json::message('缺少司机id参数');
        }
        $result = MessageLogic::deleteBatchMessage($driverId, 2);
        if ($result){
            return Json::message('删除成功',0);
        }
        return Json::message('删除失败');
    }
    /**
     * 获取司机订单列表
     *
     * @param int $driverId
     * @param int $orderType(是否取预约单1：取全部订单，2：取预约单和服务中订单)
     * @return array
     */
    private static function getOrderList($driverId, $orderType=1){
        $query = Order::find();
        $query->select(['id','order_number','start_address','end_address','passenger_getoff_address','order_start_time','service_type','passenger_phone','mapping_number','other_mapping_number','other_phone','passenger_info_id','status','is_cancel','is_paid','order_type','user_feature']);
        $query->where(['driver_id'=>$driverId]);
        $sort = $orderType == 2 ? 'ASC' : 'DESC';
        if ($orderType == 2){
            $query->andWhere(['IN', 'status', [2,3,4,5,6]])->andWhere(['is_cancel'=>0]);
        }
        $orderList = self::getPagingData($query, ['type'=>$sort,'field'=>'order_start_time'], true);
        return $orderList['data'];
    }
}