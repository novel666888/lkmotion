<?php
namespace application\modules\notice\controllers;

use application\controllers\BossBaseController;
use common\logic\BossMessageLogic;
use common\models\SmsAppTemplate;
use common\models\SmsSendApp;
use common\util\Json;
use common\util\Cache;
use common\logic\LogicTrait;
use yii\helpers\ArrayHelper;
class AppMessageController extends BossBaseController
{
    use LogicTrait;
    //app文案模板列表
    public function actionAppTemplateList(){
        $request = $this->getRequest();
        $appTemplateName = trim($request->post('templateName'));
        $templateType = trim($request->post('templateType'));
        $appTemplateList = BossMessageLogic::getAppTemplateList($appTemplateName, $templateType);
        $this->fillUserInfo($appTemplateList['list'], 'operator_user');
        $appTemplateList['list'] = $this->keyMod($appTemplateList['list']);
        return Json::success($appTemplateList);
    }

    //取文案列表数据
    public function actionGetLabel(){
        //取文案标签
        $SmsAppTemplate = new SmsAppTemplate();
        $msgLabel = $SmsAppTemplate->keyLabel;
        return Json::success($msgLabel);
    }

    //添加app文案模板
    public function actionAddAppTemplate(){
        $request = $this->getRequest();
        $requestData = array(
            'template_name' => trim($request->post('templateName')),
            'template_type' => intval($request->post('templateType')),
            'send_type' => implode(",", $request->post('sendType')),
            'sms_image' => trim($request->post('smsImage')),
            'sms_url' => trim($request->post('smsUrl')),
            'content' => trim($request->post('content')),
            'operator_user' => $this->userInfo['id'],
        );
        if (empty($requestData['template_name']) || empty($requestData['template_type']) || empty($requestData['send_type']) || ($requestData['send_type'] != 2 && empty($requestData['content']))){
            return Json::message('请传递完整参数');
        }
        if (!BossMessageLogic::checkAppTemplateName($requestData['template_name'])){
            return Json::message('文案模板名称已存在');
        }else{
            $SmsAppTemplate = new SmsAppTemplate();
            $SmsAppTemplate->attributes = $requestData;
            // 验证输入内容
            if (!$SmsAppTemplate->validate()) {
                $msg = $SmsAppTemplate->getFirstError();
                return Json::message($msg);
            }
            if (!$SmsAppTemplate->save()){
                return Json::message('添加失败');
            }else{
                //添加成功将数据做redis缓存
                $insertId = $SmsAppTemplate->attributes['id'];
                $insertData = SmsAppTemplate::find()->where(['id'=>$insertId])->indexBy('id')->asArray()->all();
                Cache::set('sms_app_template', $insertData, 0);
            }
        }
        return Json::message('添加成功', 0);
    }

    //修改app文案模板
    public function actionUpdateAppTemplate(){
        $request = $this->getRequest();
        $requestData = array(
            'id' => intval($request->post('id')),
            'template_name' => trim($request->post('templateName')),
            'template_type' => intval($request->post('templateType')),
            'send_type' => implode(",", $request->post('sendType')),
            'sms_image' => trim($request->post('smsImage')),
            'sms_url' => trim($request->post('smsUrl')),
            'content' => trim($request->post('content')),
            'operator_user' => $this->userInfo['id'],
        );
        if (empty($requestData['id']) || empty($requestData['template_name']) || empty($requestData['template_type']) || empty($requestData['send_type']) || ($requestData['send_type'] != 2 && empty($requestData['content']))){
            return Json::message('请传递完整参数');
        }
        if(!BossMessageLogic::checkAppTemplate($requestData['id'])){
            return Json::message('该案模板不存在或不可修改！');
        }else{
            if (!BossMessageLogic::checkAppTemplateName($requestData['template_name'], $requestData['id'])){
                return Json::message('文案模板名称已存在');
            }
            $SmsAppTemplate = new SmsAppTemplate();
            $SmsAppTemplate->attributes = $requestData;
            // 验证输入内容
            if (!$SmsAppTemplate->validate()) {
                $msg = $SmsAppTemplate->getFirstError();
                return Json::message($msg);
            }
            $result = SmsAppTemplate::updateAll($requestData,['id'=>$requestData['id']]);
            if (!$result){
                return Json::message('更新失败');
            }else{
                //修改成功更新redis缓存
                $updateData = SmsAppTemplate::find()->where(['id'=>$requestData['id']])->indexBy('id')->asArray()->all();
                Cache::set('sms_app_template', $updateData, 0);
            }
        }
        return Json::message('修改成功', 0);
    }

    //删除app文案模板
    public function actionDeleteAppTemplate(){
        $request = $this->getRequest();
        $appTemplateId = intval($request->post('id'));
        if (empty($appTemplateId)){
            return Json::message('请传递完整参数');
        }
        if(!BossMessageLogic::checkAppTemplate($appTemplateId)){
            return Json::message('该模板不存在或不可删除！');
        }else{
            $query = SmsAppTemplate::find()->where(['id'=>$appTemplateId])->one();
            $result = $query->delete();
            if (!$result){
                return Json::message('删除失败');
            }else{
                Cache::delete('sms_app_template', $appTemplateId);
            }
        }
        return Json::message('删除成功', 0);
    }

    //app文案模板详情
    public function actionAppTemplateDetail(){
        $request = $this->getRequest();
        $appTemplateId = intval($request->post('id'));
        if (empty($appTemplateId)){
            return Json::message('请传递完整参数');
        }
        $appTemplateDetail = BossMessageLogic::getAppTemplateDetail($appTemplateId);
        if (!$appTemplateDetail){
            return Json::message('app文案模板不存在');
        }
        $appTemplateDetail= $this->keyMod(array_values($appTemplateDetail));
        $appTemplateDetail[0]['ossLink'] = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
        return Json::success($appTemplateDetail[0]);
    }

    //app消息推送列表
    public function actionPushList(){
        $request = $this->getRequest();
        $requestData = array(
            'name' =>  $request->post('name'),
            'status' =>  $request->post('status'),
            'send_status' =>  $request->post('sendStatus')
        );
        if (!empty($requestData['status']) && !in_array($requestData['status'], [0,1])){
            return Json::message('启用状态参数错误');
        }
        if (!empty($requestData['send_status']) && !in_array($requestData['send_status'], [0,1])){
            return Json::message('推送状态参数错误');
        }
        $pushList = BossMessageLogic::getPushList($requestData);
        self::fillUserInfo($pushList['list'],'operator_user');
        $pushList['list'] = $this->keyMod($pushList['list']);
        return Json::success($pushList);
    }

    //添加app消息推送
    public function actionAddPush(){
        $request = $this->getRequest();
        $requestData = array(
            'start_time' => trim($request->post('startTime')),
            'show_type' => intval($request->post('showType')),
            'sms_type' => intval($request->post('smsType')),
            'sms_level' => intval($request->post('smsLevel')),
            'people_tag_id' => intval($request->post('peopleTagId')),
            'app_template_id' => trim($request->post('appTemplateId')),
            'operator_user' => $this->userInfo['id'],
        );
        \Yii::info($requestData, 'getData');
        if (empty($requestData['start_time']) || empty($requestData['show_type']) || empty($requestData['sms_type']) || empty($requestData['app_template_id'])){
            return Json::message('请传递完整参数');
        }
        $requestData['send_number'] = date("Ymd", time()).rand(100000,999999);

        $smsSendApp = new SmsSendApp();
        $smsSendApp->attributes = $requestData;
        // 验证输入内容
        if (!$smsSendApp->validate()) {
            $msg = $smsSendApp->getFirstError();
            return Json::message($msg);
        }
        if (!$smsSendApp->save()){
            return Json::message('添加失败');
        }else{
            //添加成功，做redis缓存
            $insertId = $smsSendApp->attributes['id'];
            $insertData = SmsSendApp::find()->where(['id'=>$insertId])->indexBy('id')->asArray()->all();
            Cache::set('sms_send_app', $insertData, 0);
        }
        return Json::message('添加成功', 0);
    }

    //app消息推送详情
    public function actionPushDetail(){
        $request = $this->getRequest();
        $sendAppId = intval($request->post('id'));
        if (empty($sendAppId)){
            return Json::message('缺少id参数');
        }
        $sendAppDetail = BossMessageLogic::getSendAppDetail($sendAppId);
        if (!$sendAppDetail){
            return Json::message('消息推送不存在！');
        }
        $sendAppDetail = $this->keyMod(array_values($sendAppDetail));
        $sendAppDetail[0]['ossLink'] = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
        return Json::success($sendAppDetail[0]);
    }

    //冻结/解冻app消息推送
    public function actionFreezePush(){
        $request = $this->getRequest();
        $sendAppId = intval($request->post('id'));
        $status = intval($request->post('status'));
        if (empty($sendAppId)){
            return Json::message('缺少id参数');
        }
        if (!BossMessageLogic::checkSendApp($sendAppId)){
            return Json::message('消息推送不存在');
        }else{
            if ($status == 0 || $status == 1){
                $result = SmsSendApp::updateAll(['status'=>$status], ['id'=>$sendAppId]);
                if (!$result){
                    return Json::message('冻结/解冻失败');
                }else{
                    //更新成功，更新redis缓存数据
                    $updateData = SmsSendApp::find()->where(['id'=>$sendAppId])->indexBy('id')->asArray()->all();
                    Cache::set('sms_send_app', $updateData, 0);
                }
            }else{
                return Json::message('status参数错误');
            }
        }
        return Json::message('冻结/解冻成功', 0);
    }
}