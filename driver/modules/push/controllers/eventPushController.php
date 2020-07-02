<?php
namespace  driver\modules\push\controllers;

use common\models\Order;
use common\models\PassengerInfo;
use common\services\traits\PublicMethodTrait;
use common\models\CarInfo;
use common\models\DriverInfo;
use common\models\FlightNumber;
use common\util\Common;
use common\models\MessageShow;
//use common\jobs\SendJpush;
use common\models\CarType;
use common\models\EvaluateDriver;
use common\models\OrderRulePrice;


class eventPushController extends \yii\base\Component
{
    use PublicMethodTrait;

    //抢单结果-to司机
    public static function sendOrderGrapResult($event){
        $orderInfo = Order::find()->select(['id','passenger_info_id','driver_id','order_start_time','start_address','end_address',
        'start_longitude','start_latitude','end_longitude','end_latitude','mapping_number'])->where(['id'=>$event->orderId])->asArray()->one();
        \Yii::info(json_encode($event),'request_data');
        \Yii::info(json_encode($orderInfo),'request_order_data');
        $result = Common::getPhoneNumber([['id'=>$orderInfo['passenger_info_id']]], 1);
        if ($event->extInfo['code'] == 0){//抢单成功
            $messageType = 104;
            $data = array(
                'startLat' => $orderInfo['start_latitude'],
                'startLng' => $orderInfo['start_longitude'],
                'orderStartTime' => $orderInfo['order_start_time'],
                'startAddress' => $orderInfo['start_address'],
                'endLat' => $orderInfo['end_latitude'],
                'endLng' => $orderInfo['end_longitude'],
                'endAddress' => $orderInfo['end_address'],
                'id' => $orderInfo['id'],
                'mapping_number' => $orderInfo['mapping_number'],
                'passengerPhone' => $result[0]['phone'] ?? '',
            );

            $insertData = array(
                'acceptIdentity' => 2,
                'acceptId' => (string)$orderInfo['driver_id'],
                'messageType' => $messageType,
                'messageBody' => json_encode($data),
                'sendId' => 'system',
                'sendIdentity' => 0,
            );
            \Yii::info(json_encode($insertData),'request_push_success_data');
            $res = Common::insertLoop($insertData);
            if ($res['code'] == 0){
                //取明文电话
                $result = Common::getPhoneNumber([['id'=>$orderInfo['passenger_info_id']]], 1);
                $phone = substr($result[0]['phone'], 7, 11);
                $message = array(
                    'yid' => $orderInfo['driver_id'],
                    'order_id' => $orderInfo['id'],
                    'title' => '抢单成功',
                    'content' => "预约单抢单成功，时间".$orderInfo['order_start_time']."乘客尾号".$phone."，从".$orderInfo['start_address']."到".$orderInfo['end_address']."的订单抢单成功，请合理安排接乘时间",
                    'show_type' => 2,
                    'push_type' => 3,
                    'send_time' => date("Y-m-d H:i:s", time()),
                    'status' => 1,
                );
                $messageShow = new MessageShow();
                $messageShow->attributes = $message;
                $messageShow->save();
            }
        }elseif ($event->extInfo['code'] != 0){ //抢单失败
            $messageType = 105;
            $data = array('message'=>'抢单失败','content'=>'');
            $insertData = array(
                'acceptIdentity' => 2,
                'acceptId' => (string)$event->extInfo['driverId'],
                'messageType' => $messageType,
                'messageBody' => json_encode($data),
                'sendId' => 'system',
                'sendIdentity' => 0,
            );
            \Yii::info(json_encode($insertData),'request_push_error_data');
            $res = Common::insertLoop($insertData);
            if ($res['code'] == 0){
                $message = array(
                    'yid' => $orderInfo['driver_id'],
                    'order_id' => $orderInfo['id'],
                    'title' => '抢单失败',
                    'content' => "预约单抢单失败",
                    'show_type' => 2,
                    'push_type' => 3,
                    'send_time' => date("Y-m-d H:i:s", time()),
                    'status' => 1,
                );
                $messageShow = new MessageShow();
                $messageShow->attributes = $message;
                $messageShow->save();
            }
        }
    }

    //司机抢单成功推乘客
    static public function sendOrderGrapToPassenger($event){
        if ($event->extInfo['code'] == 0){
            $orderInfo = Order::find()->select(['id','passenger_info_id','driver_id','car_id','order_start_time','start_address','end_address',
                'start_longitude','start_latitude','end_longitude','end_latitude','other_phone','order_type'])->where(['id'=>$event->orderId])->asArray()->one();
            $driverInfo = DriverInfo::find()->select(['driver_name','head_img'])->where(['id'=>$orderInfo['driver_id']])->asArray()->one();
            $carInfo = CarInfo::find()->select(['plate_number','color','car_img','car_type_id'])->where(['id'=>$orderInfo['car_id']])->asArray()->one();
            $carBrand = CarType::find()->select(['brand'])->where(['id'=>$carInfo['car_type_id']])->scalar();
            $driverPhone = Common::getPhoneNumber([['id'=>$orderInfo['driver_id']]], 2);
            //司机星级
            $todayTime = date("Y-m-d 00:00:00", time());
            $grade = EvaluateDriver::find()->select('AVG(grade)')->where(['driver_id'=>$orderInfo['driver_id']])->andWhere(['<', 'create_time', $todayTime])->scalar();
            $driverGrade = sprintf("%.1f", $grade);
            $data = array(
                'messageType'=> 108,
                'orderId'=>$orderInfo['id'],
                'plateNumber'=>$carInfo['plate_number'],
                'brand'=>$carBrand,
                'carImg'=>$carInfo['car_img'],
                'avgGrade'=>$driverGrade,
                'color'=>$carInfo['color'],
                'driverName'=>$driverInfo['driver_name'],
                'driverHeadImg'=>$driverInfo['head_img'],
                'driverPhoneNum'=>$driverPhone[0]['phone'],
                'driverLng'=>$event->extInfo['longitude'] ?? '',
                'driverLat'=>$event->extInfo['latitude'] ?? ''
            );

            $jpushData = array(
                'sendId' => 'system',//发送者id
                'sendIdentity' => 1,//发送者身份
                'acceptId' => (string)$orderInfo['passenger_info_id'],//接收者id
                'acceptIdentity' => 1,//接收者身份 1
                'title' => '司机已接单',
                'messageType' => 1,//1:别名， 2：注册id
                'messageBody' => $data
            );
            $res = self::jpush(3, $jpushData);//推送给乘客
//         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>3, 'pushData'=>$jpushData]));

            //发短信给客户
            if ($orderInfo['order_type'] == 1){//给自己叫车
                $passengerPhone = Common::getPhoneNumber([['id'=>$orderInfo['passenger_info_id']]], 1);
                $phone = $passengerPhone[0]['phone'];
                $smsId = 'SMS_145500020';
                $data = [
                    'time' => Common::convertTimeToNaturalLanguage($orderInfo['order_start_time']),
                    'start_address' => $orderInfo['start_address'],
                    'end_address' => $orderInfo['end_address'],
                ];
                Common::sendMessageNew($phone, $smsId, $data);
            }elseif ($orderInfo['order_type'] == 2){//给他人叫车
                $passengerPhone = Common::getPhoneNumberByEncrypt([['encrypt'=>$orderInfo['other_phone']]]);
                $passengerName = PassengerInfo::find()->select(['passenger_name'])->where(['id'=>$orderInfo['passenger_info_id']])->scalar();
                $phone = $passengerPhone[0]['phone'];
                $smsId = 'SMS_145594354';
                $data = [
                    'passenger_name' => $passengerName ?? '',
                    'time' => Common::convertTimeToNaturalLanguage($orderInfo['order_start_time']),
                    'start_address' => $orderInfo['start_address'],
                    'end_address' => $orderInfo['end_address'],
                    'driver_name' => $driverInfo['driver_name'],
                    'phone' => $driverPhone[0]['phone'],
                    'color' => $carInfo['color'],
                    'plate_number' => $carInfo['plate_number'],
                ];
                Common::sendMessageNew($phone, $smsId, $data);
            }
        }
    }

    //去接乘客（201）--到达上车点（202）--乘客上车（203）--结束行程（204）--司机发起收款(有尾款)（205） --支付成功（206）-- to乘客
    public static function orderStatusChange($event){
        \Yii::info(json_encode($event),'event_message');
        $passenger_id = Order::find()->select(['passenger_info_id'])->where(['id'=>$event->orderId])->scalar();
        $data = array(
            'messageType' => $event->extInfo['messageType'] ?? '',
            'orderId' => $event->orderId ?? 0,
            'driverLongitude' => $event->extInfo['longitude'] ?? '',
            'driverLatitude' => $event->extInfo['latitude'] ?? '',
        );
        //是否存业务消息
        if ($data['messageType'] == 202 || $data['messageType'] == 205 || $data['messageType'] == 206){//存业务消息
            $data['content'] = (string)($data['messageType'] == 202 ? '司机已到达上车地点，请您前往上车地点乘车，祝您旅途愉快' : ($data['messageType'] == 205 ? '您账户余额不足以支付本次订单，点击查看详情>>' : '订单支付成功！'));
            $msgType = 1;
        }else{//不存业务消息
            $msgType = 0;
        }
        $orderStatus = Order::$orderStatus;
        $jpushData = array(
            'sendId' => 'system',//发送者id
            'sendIdentity' => 1,//发送者身份
            'acceptId' => (string)$passenger_id,//接收者id
            'acceptIdentity' => 1,//接收者身份 1
            'title' => $orderStatus[$data['messageType']],
            'messageType' => 1,//1:别名， 2：注册id
            'messageBody' => $data
        );
        $res = self::jpush(3, $jpushData, $msgType);//推送给乘客
//         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>3, 'pushData'=>$jpushData]));
    }

    //支付成功推司机
    public static function orderPaySuccess($event){
        $orderInfo = Order::find()->select(['id','passenger_info_id','driver_id','order_start_time','start_address','end_address',
            'start_longitude','start_latitude','end_longitude','end_latitude'])->where(['id'=>$event->orderId])->asArray()->one();
        //支付金额
        $payPrice = OrderRulePrice::find()->select(['total_price'])->where(['order_id'=>$orderInfo['id'],'category'=>1])->scalar();
        //取明文电话
        $result = Common::getPhoneNumber([['id'=>$orderInfo['passenger_info_id']]], 1);
        $phone = substr($result[0]['phone'], 7, 11);
        $data = array(
            'messageType' => $event->extInfo['messageType'] ?? '',
            'orderId' => $event->orderId ?? 0,
            'content' => "时间".$orderInfo['order_start_time']."分,乘客尾号".$phone.",从".$orderInfo['start_address']."到".$orderInfo['end_address']."的订单，已支付完成".$payPrice."元。",
        );
        $jpushData = array(
            'sendId' => 'system',//发送者id
            'sendIdentity' => 1,//发送者身份
            'acceptId' => (string)$orderInfo['driver_id'],//接收者id
            'acceptIdentity' => 2,//接收者身份 2
            'title' => '支付成功',
            'messageType' => 1,//1:别名， 2：注册id
            'messageBody' => $data
        );
        $res = self::jpush(3, $jpushData, 1);//推送给司机
//         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>3, 'pushData'=>$jpushData, 'msgType'=>1]));
    }

    //通知车机重新获取验证码-to车机
    public static function sendGetCodeAgainToCarScreen($event){
        $data = array(
            'messageType' => 701,
            'content' => "通知车机重新获取二维码"
        );
        $orderInfo = Order::find()->select(['driver_id','passenger_info_id'])->where(['id'=>$event->orderId])->asArray()->one();
        $carId = DriverInfo::find()->select(['car_id'])->where(['driver_id'=>$orderInfo['driver_id']])->scalar();
        $carScreenDeviceCode = CarInfo::find()->select(['car_screen_device_code'])->where(['id'=>$carId])->scalar();

        $jpushData = array(
            'sendId' => 'system',//发送者id
            'sendIdentity' => 1,//发送者身份
            'acceptId' => $carScreenDeviceCode,//接收者id
            'acceptIdentity' => 3,//接收者身份
            'title' => '重新获取验证码',
            'messageType' => 1,//1:别名， 2：注册id
            'messageBody' => $data
        );
        $res = self::jpush(1,$jpushData);
//         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>1, 'pushData'=>$jpushData]));
    }

    //接到乘客-to大屏
    public static function sendReceivePassenger($event){
        $nowTime = date("Y-m-d H:i:s", time());
        $orderInfo = Order::find()->select(['passenger_info_id','driver_id','car_id','passenger_phone','end_address','receive_passenger_longitude',
            'receive_passenger_latitude','start_longitude','start_latitude','end_longitude','end_latitude'])->where(['id'=>$event->orderId])->asArray()->one();
        $largeScreenDeviceCode = CarInfo::find()->select(['large_screen_device_code'])->where(['id'=>$orderInfo['car_id']])->scalar();
        $flightInfo = FlightNumber::find()->select(['flight_number','flight_date'])->where(['passenger_info_id'=>$orderInfo['passenger_info_id']])
                      ->andWhere(['>','flight_date',$nowTime])->asArray()->one();

        //解密手机号
        $phone = Common::getPhoneNumber([['id'=>$orderInfo['passenger_info_id']]], 1);
        $data = array(
            'messageType' => 1010,
            'content' => '亲爱的乘客，欢迎乘坐逸品专车，开始您的智能空间体验吧～',
            'orderId' => $event->orderId ?? '',
            'passengerPhone' => $phone[0]['phone'],
            'flight_number' => $flightInfo['flight_number'],
            'flight_date' => $flightInfo['flight_date'],
            'passenger_info_id' => $orderInfo['passenger_info_id'],
            'receive_passenger_longitude' => $orderInfo['receive_passenger_longitude'],
            'receive_passenger_latitude' => $orderInfo['receive_passenger_latitude'],
            'start_longitude' => $orderInfo['start_longitude'],
            'start_latitude' => $orderInfo['start_latitude'],
            'end_longitude' => $orderInfo['end_longitude'],
            'end_latitude' => $orderInfo['end_latitude'],
            'end_address' => $orderInfo['end_address'],
            'driver_longitude' => $event->extInfo['longitude'] ?? '',
            'driver_latitude' => $event->extInfo['latitude'] ?? '',
        );

        $jpushData = array(
            'sendId' => 'system',//发送者id
            'sendIdentity' => 1,//发送者身份
            'acceptId' => $largeScreenDeviceCode,//接收者id
            'acceptIdentity' => 4,//接收者身份
            'title' => '接到乘客',
            'messageType' => 1,//1:别名， 2：注册id
            'messageBody' => $data
        );
        $res = self::jpush(3, $jpushData); //推送给大屏
//         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>3, 'pushData'=>$requestData]));

    }

    //乘客下车-to大屏
    public static function sendPassengerGetOff($event){
        $orderInfo = Order::find()->select(['driver_id','passenger_info_id'])->where(['id'=>$event->orderId])->asArray()->one();
        $carId = DriverInfo::find()->select(['car_id'])->where(['id'=>$orderInfo['driver_id']])->scalar();
        $largeScreenDeviceCode = CarInfo::find()->select(['large_screen_device_code'])->where(['id'=>$carId])->scalar();

        $data = array(
            'messageType' => 1011,
            'orderId' => $event->orderId ?? '',
            'content' => '亲爱的乘客，目的地已到达，请带好随身物品，欢迎再次乘坐逸品专车～'
        );

        $jpushData = array(
            'sendId' => 'system',//发送者id
            'sendIdentity' => 1,//发送者身份
            'acceptId' => $largeScreenDeviceCode,//接收者id
            'acceptIdentity' => 4,//接收者身份
            'title' => '乘客下车',
            'messageType' => 1,//1:别名， 2：注册id
            'messageBody' => $data
        );
        $res = self::jpush(3, $jpushData); //推送给大屏
        //         $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>3, 'pushData'=>$requestData]));
    }

    //用户取消-to司机
//     public static function sendOrderCancel($event){
//         $orderInfo = Order::find()->select(['id','status','driver_id','end_address'])->where(['id'=>$event->orderId])->asArray()->one();
//         $data = array(
//             'messageType' => $event->extInfo['messageType'] ?? '',
//             'orderId' => $orderInfo['id'],
//             'startStatus' => $orderInfo['status'],
//             'message' => '取消订单',
//             'content' => $event->extInfo['cancelTime']."乘客尾号3679,到".$orderInfo['end_address']."的订单已取消。"
//         );

//         $jpushData = array(
//             'sendId' => 'system',//发送者id
//             'sendIdentity' => 1,//发送者身份
//             'acceptId' => (string)$orderInfo['driver_id'],//接收者id
//             'acceptIdentity' => 2,//接收者身份
//             'title' => '取消订单',
//             'messageType' => 1,//1:别名， 2：注册id
//             'messageBody' => json_encode($data)
//         );
//         $res = self::jpush(3, $jpushData, 1);
//     }

    //通知车机，司机登录成功
    /* static public function sendDriverLoginToCarScreen($event){
     $data = array(
     'messageType' => 1006,
     );
     $orderInfo = Order::find()->select(['driver_id','passenger_info_id'])->where(['id'=>$event->orderId])->asArray()->one();
     $carId = DriverInfo::find()->select(['car_id'])->where(['driver_id'=>$orderInfo['driver_id']])->scalar();
     $carScreenDeviceCode = CarInfo::find()->select(['car_screen_device_code'])->where(['id'=>$carId])->scalar();

     $jpushData = array(
     'sendId' => 'system',//发送者id
     'sendIdentity' => 1,//发送者身份
     'acceptId' => $carScreenDeviceCode,//接收者id
     'acceptIdentity' => 3,//接收者身份
     'title' => '车机登录成功',
     'messageType' => 1,//1:别名， 2：注册id
     'messageBody' => json_encode($data)
     );
     $res = $this->jpush($jpushData);
     }

     //车机退出，给司机消息
     static public function sendCarScreenLoginOutToDriver($event){
     $data = array(
     'messageType' => 1007,
     'content' => "车机端已退出，改为app控制接单"
     );

     $jpushData = array(
     'sendId' => 1,//发送者id
     'sendIdentity' => 1,//发送者身份
     'acceptId' => $event['identity']['driverId'],//接收者id
     'acceptIdentity' => 2,//接收者身份
     'title' => '车机退出',
     'messageType' => 1,//1:别名， 2：注册id
     'messageBody' => json_encode($data)
     );
     $res = $this->jpush($jpushData);
     }

     //司机退出，给车机消息
     static public function sendAppLoginOutToCarScreen($event){
     $data = array(
     'messageType' => 1006,
     'content' => "手机 操控车机收车/退出"
     );
     $jpushData = array(
     'sendId' => 'system',//发送者id
     'sendIdentity' => 1,//发送者身份
     'acceptId' => $event['identity']['carScreenDeviceCode'],//接收者id
     'acceptIdentity' => 3,//接收者身份
     'title' => '车机退出',
     'messageType' => 1,//1:别名， 2：注册id
     'messageBody' => json_encode($data)
     );
     $res = $this->jpush($jpushData);
     } */





}
