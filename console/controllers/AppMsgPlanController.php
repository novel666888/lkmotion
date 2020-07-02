<?php

namespace console\controllers;

use common\models\PassengerWallet;
use common\models\SmsSendApp;
use common\models\SmsAppTemplate;
use common\models\PeopleTag;
use common\models\PassengerInfo;
use common\models\CarInfo;
use common\models\DriverInfo;
use common\models\DriverBaseInfo;
use common\models\SysUser;
use common\services\traits\PublicMethodTrait;
use common\util\Common;
use common\jobs\SendJpush;
use common\models\CarLevel;
use yii\console\Controller;
use yii\helpers\ArrayHelper;


class AppMsgPlanController extends Controller
{
    use PublicMethodTrait;
    public function actionIndex(){
        //取推送任务
        $smsAppList = $this->getPushList();
        \Yii::info(json_encode($smsAppList),'notice_list');
        //循环未推送消息任务列表
        if (!empty($smsAppList)){
            foreach ($smsAppList as $k=>$v){
                $allTemplate = SmsAppTemplate::find()->select(['id','template_name','sms_url','sms_image','content','send_type'])->indexBy('id')->asArray()->all();//取全部模板信息
                $showType = $v['show_type'];//展示端
                $sendType = $allTemplate[$v['app_template_id']]['send_type'];
                $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
                $templateContent = $allTemplate[$v['app_template_id']]['content'] ?? '';
                $allSystemUser = $this->getSystemUser();//取全部系统用户信息

                //取对应的人群（司机，乘客），若模板选择了人群选取对应人群；若模板没有选择人群，则选择对应平台的全部用户。
                $peopleList =$this->getPeopleList($v['people_tag_id']);
                if (!empty($peopleList)){
                    //取对应人群信息
                    if ($showType == 1 || $showType == 2){
                        $peopleIds = array_column($peopleList,'id');
                        $peopleIdsArr = array_chunk($peopleIds,'1000');
                        $userInfo = [];
                        foreach ($peopleIdsArr as $key=>$value){
                            $temp = $this->getUserInfo($value, $showType, $allSystemUser);
                            $userInfo += $temp;
                        }
                        //获取用户手机号
                        $peopleList = Common::getPhoneNumber($peopleList,  $v['show_type']);
                        \Yii::info(json_encode($peopleList), 'java_get_phone_list');
                    }
                    if (!empty($peopleList)){//循环人群，取对应的人的信息，拼接模板
                        foreach ($peopleList as $kk=>$vv){
                            $peopleTemplateContent = $templateContent;
                            if ($showType == 1 || $showType == 2){
                                $peopleDetail = $userInfo[$vv['id']];
                                if ($showType == 1){//乘客
                                    $peopleDetail['passenger_phone'] = $vv['phone'];
                                }elseif ($showType == 2){//司机
                                    $peopleDetail['driver_phone'] = $vv['phone'];
                                }
                                //循环用户信息，拼接模板
                                foreach ($peopleDetail as $kkk=>$vvv){
                                    $peopleTemplateContent = str_replace('${'.$kkk.'}',$vvv, $peopleTemplateContent);
                                }
                            }
                            \Yii::info("sendId=".$allSystemUser[$v['operator_user']]['id'],'sendId');
                            //极光推送请求信息
                            $requestData = array(
                                'sendId' => $allSystemUser[$v['operator_user']]['id'],//发送者id
                                'sendIdentity' => 1,//发送者身份
                                'acceptId' => $showType == 4 ? $vv['large_screen_device_code'] : $vv['id'],//接收者id
                                'acceptIdentity' => $showType,//接收者身份
                                'title' => $allTemplate[$v['app_template_id']]['template_name'],
                                'messageType' => 801,
                                'messageBody' => array('type'=>$sendType,'messageType'=>801,'title'=>$allTemplate[$v['app_template_id']]['template_name'],'content'=>$peopleTemplateContent,'smsImage'=>$ossFileUrl.$allTemplate[$v['app_template_id']]['sms_image'],'smsUrl'=>$allTemplate[$v['app_template_id']]['sms_url'],'smsSendAppId'=>$v['id'])
                            );
                            //调java消息服务推送消息
                            $this->jpush($v['sms_type'], $requestData, 1, 2);
                        }
                    }
                }
                $result = SmsSendApp::updateAll(['send_status'=>1],['id'=>$v['id']]);
                \Yii::info(json_encode($result),'update_result');
            }
        }
    }

    /**
     * 获取人群中的每个人的id
     *
     * @param $peopleId
     * @return array|\yii\db\ActiveRecord[]
     */
    public function getPeopleList($peopleId){
        if ($peopleId > 0){
            $peopleTag = PeopleTag::getPeopleTagDetail($peopleId);
            if (!empty($peopleTag)){
                if ($peopleTag['tag_type'] == 1){//乘客
                    $query = PassengerInfo::find()->select(['id']);
                    if ($peopleTag['tag_conditions'] != '*'){
                        $tag_condition = json_decode($peopleTag['tag_conditions'],true);
                        $regStart = $tag_condition['regStart'];
                        $regEnd = $tag_condition['regEnd'];
                        $query->where(['>','register_time',$regStart])->andWhere(['<','register_time',$regEnd]);
                    }
                }elseif ($peopleTag['tag_type'] == 2){//司机
                    $query = DriverInfo::find()->select(['id']);
                    if ($peopleTag['tag_conditions'] != '*'){
                        $tag_condition = json_decode($peopleTag['tag_conditions'],true);
                        $carList = CarInfo::find()->select(['id'])->where(['plate_number'=>$tag_condition])->asArray()->all();
                        $carList = array_column($carList, 'id');
                        $query->where(['car_id'=>$carList]);
                    }
                }
                $peopleList = $query->asArray()->all();
                return $peopleList;
            }
        }else{
            $peopleList = CarInfo::find()->select(['large_screen_device_code'])->where(['use_status'=>1])->asArray()->all();
            return $peopleList;
        }
        return [];
    }

    /**
     * 取用户信息
     *
     * @param $userIdsArr
     * @param $showType
     * @param $systemUser
     * @return array|bool|\yii\db\ActiveRecord[]
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UserException
     */
    public function getUserInfo($userIdsArr, $showType, $systemUser){
        if ($showType == 1){//乘客信息
            $allPassenger = PassengerInfo::find()
                ->select(['id','passenger_name','birthday as passenger_birthday','register_time as passenger_register_time','gender as passenger_gender'])
                ->where(['id'=>$userIdsArr])->asArray()->indexBy('id')->all();
            $passengerBalance = PassengerWallet::find()->select(['passenger_info_id','capital','give_fee'])
                ->where(['passenger_info_id'=>$userIdsArr])->indexBy('passenger_info_id')->asArray()->all();
            foreach ($passengerBalance as $k => $v){
                $passengerBalance[$k]['balance'] = $v['capital'] + $v['give_fee'];
            }
            foreach ($allPassenger as $kk => $vv){
                $allPassenger[$kk]['passenger_balance'] = $passengerBalance[$vv['id']]['balance'] ?? '0.00';
            }
            return $allPassenger;
        }elseif ($showType == 2){//司机信息
            $allDriver = DriverInfo::find()
                ->select(['id','driver_leader','driver_name','gender as driver_gender','balance as driver_balance','car_id'])
                ->where(['id'=>$userIdsArr])->asArray()->indexBy('id')->all();
            $driverBaseInfo = DriverBaseInfo::find()->select(['id','birthday'])->where(['id'=>$userIdsArr])->asArray()->indexBy('id')->all();
            $carInfo = CarInfo::find()->select(['id','car_level_id','plate_number'])->asArray()->indexBy('id')->all();
            $carLevel = CarLevel::find()->select(['id','label',])->indexBy('id')->asArray()->all();
            //取出需要解密的手机号
            $phoneText = [];
            foreach ($systemUser as $item) {
                if ($item['phone'])$phoneText[]= $item['phone'];
            }
            $res =Common::decryptCipherText($phoneText);
            //将系统用户加密手机号替换成明文手机号
            foreach ($systemUser as $kk=>$vv) {
                $systemUser[$kk]['phone'] = $res[$vv['phone']] ?? '';
            }
            foreach ($carInfo as $k => $v){
                $carInfo[$k]['label'] = $carLevel[$v['car_level_id']]['label'] ?? '';
            }
            if (!empty($allDriver)){
                foreach ($allDriver as $kkk=>$vvv){
                    $allDriver[$kkk]['driver_birthday'] = $driverBaseInfo[$vvv['id']]['birthday'] ?? '';
                    $allDriver[$kkk]['car_level_id'] = $carInfo[$vvv['car_id']]['label'] ?? '';
                    $allDriver[$kkk]['plate_number'] = $carInfo[$vvv['car_id']]['plate_number'] ?? '';
                    $allDriver[$kkk]['driver_manage'] = $systemUser[$vvv['driver_leader']]['username'] ?? '';
                    $allDriver[$kkk]['driver_manage_phone'] = $systemUser[$vvv['driver_leader']]['phone'] ?? '';
                }
            }
            return $allDriver;
        }
        return false;
    }

    /**
     * 获取所有系统用户信息
     *
     * @return array
     */
    public function getSystemUser(){
        $allSystemUser = SysUser::find()->select(['id','username','phone'])->asArray()->indexBy('id')->all();
        return $allSystemUser;
    }

    /*
     * 获取推送任务列表
     *
     * @return array
     */
    private function getPushList(){
        $nowTime = date("Y-m-d H:i:s", time());
        $list = SmsSendApp::find()->where(['status'=>1,'send_status'=>0])->andWhere(['<', 'start_time', $nowTime])->asArray()->all();
        return $list;
    }
}