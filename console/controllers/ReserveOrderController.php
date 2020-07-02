<?php
/**
 * 预约单提前一小时提醒司机
 * 
 * Created by Zend Studio
 * User: lijin
 * Date: 2018年9月2日
 * Time: 下午6:29:34
 */

namespace console\controllers;

use common\models\Order;
use common\services\traits\PublicMethodTrait;
use common\models\DriverInfo;
use common\util\Common;
use yii\console\Controller;
use common\jobs\SendJpush;

class ReserveOrderController extends Controller
{
    use PublicMethodTrait;
    public function actionIndex(){//同时推送极光、短信
        $reserveTime = date("Y-m-d H:i:s", time()+3600);
        $reserveTimeStamp = strtotime($reserveTime);
        $startSearch = date("Y-m-d H:i:s", $reserveTimeStamp - 120);
        $orderList = Order::find()->select(['id','start_address','end_address','order_start_time','driver_id'])
        ->where(['and',['=','status',2],
                ['!=','service_type',1],
                ['=','is_cancel',0],
                ['>', 'order_start_time', $startSearch],
                ['<=', 'order_start_time', $reserveTime]])
                ->asArray()
                ->all();
        \Yii::info(json_encode($orderList),'order_list');
        $driverId = array_unique(array_column($orderList, 'driver_id'));
        $driverIdArr = DriverInfo::find()->select(['id'])->where(['IN','id',$driverId])->asArray()->all();
        $driverPhones = Common::getPhoneNumber($driverIdArr, 2);//取用户的明文手机号
        $driverIdPhones = array_column($driverPhones, 'phone', 'id');
        if (!empty($orderList)){
            foreach ($orderList as $key=>$value){
                $startTimeStamp = strtotime($value['order_start_time']);
                if (($reserveTimeStamp - $startTimeStamp >= 0) && ($reserveTimeStamp - $startTimeStamp < 60)){
                    $startTime = date("H:i", strtotime($value['order_start_time']));
                    $pushStartTime = date("Y-m-d H:i", strtotime($value['order_start_time']));
                    $data = array(
                        'messageType' => '106',
                        'content' => $pushStartTime."，从".$value['start_address']."至".$value['end_address']."的预约单即将开始，请立即前往去接乘客。",
                        'orderId' => $value['id']
                    );
                    
                    $pushData = array(
                        'sendId' => 'system',//发送者id
                        'sendIdentity' => 1,//发送者身份
                        'acceptId' => $value['driver_id'],//接收者id
                        'acceptIdentity' => 2,//接收者身份
                        'title' => '预约提醒',
                        'messageType' => 1,//1:别名， 2：注册id
                        'messageBody' => $data
                    );
                    self::Jpush(3, $pushData, 1);
                    //发短信
                    $messageData =  [$startTime, $value['start_address'], $value['end_address']];
                    Common::sendMessageNew($driverIdPhones[$value['driver_id']], 'HX_0005', $messageData);
                }
            }
        }
    }
    
}