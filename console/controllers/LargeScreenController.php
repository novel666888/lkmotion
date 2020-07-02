<?php
/**
 * 大屏app升级指令
 * 
 * Created by Zend Studio
 * User: lijin
 * Date: 2018年9月2日
 * Time: 下午6:29:55
 */
namespace console\controllers;

use yii\console\Controller;
use common\services\traits\PublicMethodTrait;
use common\models\TvApps;
use common\models\CarInfo;
use common\jobs\SendJpush;

class LargeScreenController extends Controller{
    use PublicMethodTrait;
    
    //大屏app升级更新
    public function actionIndex(){
        $nowTimeStamp = time();
        $nowTime = date("Y-m-d H:i:s", $nowTimeStamp);
        $apps = TvApps::find()->select(['start_time'])->where(['<','start_time',$nowTime])->andWhere(['use_status'=>1])->asArray()->all();
        if (!empty($apps)){
            foreach ($apps as $key=>$value){
                $startTime = strtotime($value['start_time']);
                if (($nowTimeStamp - $startTime > 0) && ($nowTimeStamp - $startTime < 60)){
                    //触发极光推送通知大屏
                    $allLargeScreen = CarInfo::find()->select(['large_screen_device_code'])->where(['use_status'=>1,'operation_status'=>0])->asArray()->all();
                    if (!empty($allLargeScreen)){
                        $jpushData = array(
                            'sendId' => 'system',//发送者id
                            'sendIdentity' => 1,//发送者身份
                            'acceptIdentity' => 4,//接收者身份 1
                            'title' => '大屏app更新指令',
                            'messageType' => 1,//1:别名， 2：注册id
                            'messageBody' => json_encode(array('messageType'=>1012))
                        );
                        foreach ($allLargeScreen as $k=>$v){
                            $jpushData['acceptId'] = $v['large_screen_device_code'];//接收者id
                            $res = $this->jpush(2, $jpushData);//推送给大屏
//                             $res = \Yii::$app->queue->push(new SendJpush(['pushType'=>2, 'pushData'=>$jpushData]));
                            
                        }
                    }
                }
            }
        }
    }
}