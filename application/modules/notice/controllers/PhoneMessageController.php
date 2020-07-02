<?php

namespace application\modules\notice\controllers;

use common\util\Json;
use common\models\SmsTemplate;
use PHPExcel_IOFactory;
use common\models\PassengerInfo;
use common\models\DriverInfo;
use common\models\DriverBaseInfo;
use common\models\CarInfo;
use common\models\SmsAppTemplate;
use common\services\traits\PublicMethodTrait;
use common\util\Common;
use common\models\SmsSendPhone;
use common\models\PhoneMessage;
use yii\base\UserException;
use common\logic\MessageLogic;
use common\logic\LogicTrait;
use common\models\SysUser;
use application\controllers\BossBaseController;
class PhoneMessageController extends BossBaseController
{
    use PublicMethodTrait;
    use LogicTrait;
    //短信模板列表
    public function actionMsgTemplateList(){
        $request = $this->getRequest();
        $templateName =  $request->post('templateName');
        $templateList = SmsTemplate::getTemplateList($templateName);
        $templateList['list'] = $this->keyMod($templateList['list']);
        return Json::success($templateList);
    }
    
    //添加短信推送
    public function actionAddSend(){
        $request = $this->getRequest();
        $request = $request->post();
        $requestData = array(
            'sms_template_id' => !empty($request['smsTemplateId']) ? trim($request['smsTemplateId']) : '',
            'sms_type' => !empty($request['smsType']) ? intval($request['smsType']) : '0', //消息类型（1：营销；2：通知）
            'send_type' => !empty($request['sendType']) ? intval($request['sendType']) : '0', //发送类型（1：单人发送；2：批量发送）
            'phone_number' => !empty($request['phoneNumber']) ? trim($request['phoneNumber']) : '',
            'send_people' => !empty($request['sendPeople']) ? intval($request['sendPeople']) : '0', //1:乘客；2：司机
        );
        \Yii::info(json_encode($requestData),'requestData_from_boss');
        //参数验证
        if (empty($requestData['sms_template_id']) || empty($requestData['sms_type']) || empty($requestData['send_type']) || empty($requestData['send_people'])){
            return Json::message('请传递完整参数');
        }
        if (!empty($_FILES['phoneFile'])){
            $requestData['phone_file'] = $_FILES['phoneFile']['name'];
        }
        //存数据
        $smsSendPhone = new SmsSendPhone();
        $smsSendPhone->attributes = $requestData;
        if (!$smsSendPhone->save()){
            throw new UserException($smsSendPhone->getFirstError(),1002);
        }
        $smsTemplate = SmsTemplate::find()->select(['template_id','content'])->where(['template_id'=>$requestData['sms_template_id']])->asArray()->one();
        //取文案标签
        $SmsAppTemplate = new SmsAppTemplate();
        $msgLabel = $SmsAppTemplate->label;
        $addStr = array(); //模板中的变量
        //将模板中的文案标签取出
        foreach ($msgLabel as $kk=>$vv){
            if (strpos($smsTemplate['content'], $vv)){
                $addStr[] = $vv;
            }
        }
        $secretPhoneList = array();//定义加密手机号数组
        if ($requestData['send_type'] == 1){//输入号码发送
            if (empty($requestData['phone_number'])){
                return Json::message('缺少手机号参数');
            }
            $allPassenger = $this->getPassengerInfo();//取全部乘客信息
            $allDriver = $this->getDriverInfo();//取全部司机信息
            $phoneList = explode(",", $requestData['phone_number']);
            //加密用户手机号
            foreach ($phoneList as $key=>$item){//过滤无效手机号
                if(Common::checkPhoneNum($item)){
                    $secretPhoneList['infoList'][]['phone'] = $item;
                }else{
                    unset($phoneList[$key]);
                }
            }
            $phoneList = array_values($phoneList);
            $secretPhoneList = Common::phoneNumEncrypt($phoneList);
            \Yii::info(json_encode($secretPhoneList),'java_return_phone_number');
            //循环手机号，取模板键值对
            if (!empty($secretPhoneList)){
                foreach ($secretPhoneList as $k=>$v){
                    $templateValues = array();
                    $templateValues[$k]['id'] = $smsTemplate['template_id'];
                    if ($requestData['send_people'] == 1){//乘客
                        $allPassenger[$v['encrypt']]['passenger_phone'] = $v['phone'];
                        $templateMap = $this->getTemplateValues($addStr, $allPassenger[$v['encrypt']]);
                        if ($templateMap == false){
                            continue;
                        }
                        $templateValues[$k]['templateMap'] = $templateMap;
                    }elseif ($requestData['send_people'] == 2){//司机
                        $allDriver[$v['encrypt']]['driver_phone'] = $v['phone'];
                        $templateMap = $this->getTemplateValues($addStr, $allDriver[$v['encrypt']]);
                        if ($templateMap == false){
                            continue;
                        }
                        $templateValues[$k]['templateMap'] = $templateMap;
                    }
                    $res = Common::sendMessage([$phoneList[$k]], array_values($templateValues));
                    //存储数据
                    $content = $smsTemplate['content'];
                    foreach ($templateValues[$k]['templateMap'] as $key=>$value){
                        $content = str_replace('${'.$key.'}',$value, $content);
                    }
                    $message = array(
                        'phone_number' => (string)$phoneList[$k],
                        'sms_content' => $content,
                        'send_time' => date("Y-m-d H:i:s", time()),
                        'push_type' => $requestData['sms_type'],
                        'operator' => 'system'
                    );
                    if ($res['code'] == 0){//发送成功
                        $message['status'] = 1;
                    }else{//发送失败
                        $message['status'] = 0;
                    }
                    $phoneMessage = new PhoneMessage();
                    $phoneMessage->attributes = $message;
                    $phoneMessage->save();
                }
            }
        }elseif ($requestData['send_type'] == 2){//批量发送
            if (empty($requestData['phone_file'])){
                return Json::message('请上传电话文件');
            }
            //解析excel文件，获取手机号
            $filename = $_FILES['phoneFile']['tmp_name'];
            $objReader = PHPExcel_IOFactory::createReaderForFile($filename);
            $objPHPExcel = $objReader->load($filename);
            $phoneList = array();
            for($i=1;;$i++){
                $result = $objPHPExcel->getActiveSheet()->getCell('A'.$i)->getValue();
                if (!empty($result)){
                    $phoneList[] = $result;
                }else{
                    break;
                }
            }
            //加密用户手机号
            if (!empty($phoneList)){
                $allPassenger = $this->getPassengerInfo();//取全部乘客信息
                $allDriver = $this->getDriverInfo();//取全部司机信息
                foreach ($phoneList as $key=>$item){//过滤无效手机号
                    if(Common::checkPhoneNum($item)){
                        $secretPhoneList['infoList'][]['phone'] = $item;
                    }else{
                        unset($phoneList[$key]);
                    }
                }
                $phoneList = array_values($phoneList);
                $secretPhoneList = Common::phoneNumEncrypt($phoneList);
                //循环手机号，取用户信息，发送消息
                if (!empty($secretPhoneList)){
                    foreach ($secretPhoneList as $k=>$v){
                        $templateValues = array();
                        $templateValues[$k]['id'] = $smsTemplate['template_id'];
                        if ($requestData['send_people'] == 1){//乘客
                            $allPassenger[$v['encrypt']]['driver_phone'] = $v['phone'];
                            $templateMap = $this->getTemplateValues($addStr, $allPassenger[$v['encrypt']]);
                            if ($templateMap == false){
                                continue;
                            }
                            $templateValues[$k]['templateMap'] = $templateMap;
                        }elseif ($requestData['send_people'] == 2){//司机
                            $allDriver[$v['encrypt']]['driver_phone'] = $v['phone'];
                            $templateMap = $this->getTemplateValues($addStr, $allDriver[$v['encrypt']]);
                            if ($templateMap == false){
                                continue;
                            }
                            $templateValues[$k]['templateMap'] = $templateMap;
                        }
                        $res = Common::sendMessage([$phoneList[$k]], array_values($templateValues));
                        //存储数据
                        $content = $smsTemplate['content'];
                        foreach ($templateValues[$k]['templateMap'] as $key=>$value){
                            $content = str_replace('${'.$key.'}',$value, $content);
                        }
                        $message = array(
                            'phone_number' => (string)$phoneList[$k],
                            'sms_content' => $content,
                            'send_time' => date("Y-m-d H:i:s", time()),
                            'push_type' => $requestData['sms_type'],
                            'operator' => 'system'
                        );
                        if ($res['code'] == 0){//发送成功
                            $message['status'] = 1;
                        }else{//发送失败
                            $message['status'] = 0;
                        }
                        $phoneMessage = new PhoneMessage();
                        $phoneMessage->attributes = $message;
                        $phoneMessage->save();
                    }
                }
            }
        }
        return Json::message('操作成功', 0);
    }
    
    //短信列表
    public function actionMsgList(){
        $request = $this->getRequest();
        $requestData = array(
            'start_time' => $request->post('startTime'),
            'end_time' => $request->post('endTime'),
            'phone_num' => $request->post('phoneNum')
        );
        $smsList = MessageLogic::getSmsList($requestData);
        if (empty($smsList)){
            return Json::message('暂无数据');
        }
        $this->fillUserInfo($smsList['list'],'operator');
        $smsList['list'] = $this->keyMod($smsList['list']);
        return Json::success($smsList);
    }
    
    //短信详情
    public function actionMsgDetail(){
        $request = $this->getRequest();
        $smsId = intval($request->post('id'));
        if (empty($smsId)){
            return Json::message('缺少id参数');
        }
        $smsDetail = MessageLogic::getSmsDetail($smsId);
        $smsDetail = $this->keyMod($smsDetail);
        return Json::success($smsDetail);
    }
    
    /**
     * 获取模板变量信息
     * 
     * @param array $addStr
     * @param array $peopleDetail
     * @return array
     */
    private function getTemplateValues($addStr, $peopleDetail){
        //删除多余键值对
        if (!empty($peopleDetail)){
            foreach ($peopleDetail as $key=>$value){
                if (!in_array($key, $addStr)){
                    unset($peopleDetail[$key]);
                }
            }
        }else{
            return false;
        }
        return $peopleDetail;
    }
    
    /**
     * 获取所有乘客信息
     *
     * @return array
     */
    private function getPassengerInfo(){
        $allPassenger = PassengerInfo::find()
        ->select(['id','passenger_name','phone as passenger_phone','birthday as passenger_birthday','register_time as passenger_register_time','gender as passenger_gender','balance as passenger_balance'])
        ->asArray()->indexBy('passenger_phone')->all();
        return $allPassenger;
    }
    
    /**
     * 获取所有司机信息
     *
     * @return array
     */
    private function getDriverInfo(){
        $allDriver = DriverInfo::find()
        ->select(['id','driver_name','phone_number as driver_phone','gender as driver_gender','balance','car_id','driver_leader'])
        ->asArray()->indexBy('driver_phone')->all();
        $driverBaseInfo = DriverBaseInfo::find()->select(['id','birthday'])->asArray()->indexBy('id')->all();
        $carInfo = CarInfo::find()->select(['id','car_level_id','plate_number'])->asArray()->indexBy('id')->all();
        $driverLeader = SysUser::find()->select(['username','id'])->indexBy('id')->asArray()->all();
        if (!empty($allDriver)){
            foreach ($allDriver as $kkk=>$vvv){
                $allDriver[$kkk]['driver_birthday'] = !empty($driverBaseInfo[$vvv['id']]['birthday']) ? $driverBaseInfo[$vvv['id']]['birthday'] : '';
                $allDriver[$kkk]['car_level_id'] = !empty($carInfo[$vvv['car_id']]['car_level_id']) ? $carInfo[$vvv['car_id']]['car_level_id'] : '';
                $allDriver[$kkk]['plate_number'] = !empty($carInfo[$vvv['car_id']]['plate_number']) ? $carInfo[$vvv['car_id']]['plate_number'] : '';
                $allDriver[$kkk]['driver_manage'] = !empty($driverLeader[$vvv['driver_leader']]['username']) ? $driverLeader[$vvv['driver_leader']]['username'] : '';
            }
        }
        return $allDriver;
    }
    
}