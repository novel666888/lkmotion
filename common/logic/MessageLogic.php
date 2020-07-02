<?php
namespace common\logic;

use common\models\SmsAppTemplate;
use common\services\traits\ModelTrait;
use common\models\SmsSendApp;
use common\models\MessageShow;
use common\models\PhoneMessage;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class MessageLogic
{
    use ModelTrait;
    /**
     * 获取消息列表
     * 
     * @param int $type（1：乘客，2：司机，4：大屏）
     * @param string $yid 司机、乘客id 或大屏设备号
     * @param int $msgType (1:订单消息，2：活动消息)
     * @param return array
     */
    public static function getMessageList($type, $yid, $msgType=0){
        if (empty($type) || empty($yid)){
            throw new UserException('params error');
        }
        \Yii::info(json_encode(['type'=>$type,'yid'=>$yid,'msgType'=>$msgType]),'message_list_request_data');
        $showTime = date("Y-m-d H:i:s", time()-48*3600);
        $query = MessageShow::find()->select(['id','title','content','send_time','push_type','sms_send_app_id','order_id'])->where(['yid'=>$yid,'accept_identity'=>$type,'status'=>1]);
        if ($msgType == 1){
            $query->andWhere(['push_type'=>[3,4]]);
        }elseif ($msgType == 2){
            $query->andWhere(['push_type'=>[1,2]]);
        }
        //乘客、司机端只展示两天的消息
        if ($type == 1 || $type == 2){
            $query->andWhere(['>', 'send_time', $showTime]);
        }
        $messageList = self::getPagingData($query, ['type'=>'desc','field'=>'send_time'], true);
        if (!empty($messageList['data']['list'])){
            $smsSemdAppId = array_unique(array_column($messageList['data']['list'],'sms_send_app_id'));
            $smsSendId = SmsSendApp::find()->select(['id','app_template_id'])->where(['id'=>$smsSemdAppId])->indexBy('id')->asArray()->all();
            $smsSendIdsArr = array_column($smsSendId,'app_template_id','id');
            $smsSendApp = SmsAppTemplate::find()->select(['id','sms_image','sms_url'])->where(['id'=>$smsSendIdsArr])->indexBy('id')->asArray()->all();
            foreach($smsSendIdsArr as $k=>$v){
                $smsSendIdsArr[$k] = $smsSendApp[$v];
            }
            $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
            foreach ($messageList['data']['list'] as $key=>$value){
                $messageList['data']['list'][$key]['sms_image'] = !empty($smsSendIdsArr[$value['sms_send_app_id']]['sms_image']) ? $ossFileUrl.$smsSendIdsArr[$value['sms_send_app_id']]['sms_image'] : '';
                $messageList['data']['list'][$key]['sms_url'] = !empty($smsSendIdsArr[$value['sms_send_app_id']]['sms_url']) ? $smsSendIdsArr[$value['sms_send_app_id']]['sms_url'] : '';
            }
        }
        return $messageList['data'];
    }
    
    /**
     * 获取消息详情
     * 
     * @param int $messageId
     * @param int $yid
     * @return array
     */
    public static function getMessageDetail($messageId, $yid){
        if (empty($messageId) || empty($yid)){
            throw new UserException('params error');
        }
        $messageDetail = MessageShow::find()->select(['title','content','push_type','sms_send_app_id','order_id'])->where(['id'=>$messageId,'yid'=>$yid])->asArray()->one();
        if (isset($messageDetail['sms_send_app_id'])){
            $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
            $appTemplateId = SmsSendApp::find()->select(['app_template_id'])->where(['id'=>$messageDetail['sms_send_app_id']])->scalar();
            $appTemplateInfo = SmsAppTemplate::find()->select(['sms_image','sms_url'])->where(['id'=>$appTemplateId])->asArray()->one();
            $messageDetail['sms_image'] = $ossFileUrl.$appTemplateInfo['sms_image'];
            $messageDetail['sms_url'] = $appTemplateInfo['sms_url'];
        }
        return $messageDetail;
    }
    
    /**
     * 删除单条消息
     * 
     * @param int $messageId
     * @param int $yid
     * @return void|boolean
     */
    public static function deleteSingelMessage($messageId, $yid){
        if (empty($messageId) || empty($yid)){
            throw new UserException('params error');
        }
        $result = MessageShow::updateAll(['status'=>0],['id'=>$messageId,'yid'=>$yid]);
        if (!$result){
            return false;
        }
        return true;
    }
    
    /**
     * 批量删除消息
     * 
     * @param int $yid  司机或乘客id
     * @param int $type （1:乘客，2：司机）
     * @return void|boolean
     */
    public static function deleteBatchMessage($yid, $type){
        if (empty($yid) || empty($type)){
            throw new UserException('params error');
        }
        $result = MessageShow::updateAll(['status'=>0],['yid'=>$yid,'accept_identity'=>$type]);
        if (!$result){
            return false;
        }
        return true;
    }
    
    /**
     * 检查是否有未读消息
     * 
     * @param int $type（1:乘客，2：司机）
     * @param int $yid
     * @return void|boolean
     */
    public static function checkUnReadMessage($type, $yid){
        if (empty($type) || empty($yid)){
            throw new UserException('params error');
        }
        $hasUnRead = MessageShow::find()->select(['id'])->where(['accept_identity'=>$type,'yid'=>$yid,'push_type'=>[1,2],'status'=>1,'is_read'=>0])->scalar();
        if ($hasUnRead){
            return true;
        }
        return false;
    }
    
    /**
     * 更新消息未已读状态
     * 
     * @param int $type （1:乘客，2：司机）
     * @param int $yid 乘客或司机id
     * @return boolean
     */
    public static function updateMessageRead($type, $yid){
        $result = MessageShow::updateAll(['is_read'=>1],['yid'=>$yid,'accept_identity'=>$type,'push_type'=>[1,2]]);
        if (!$result){
            return false;
        }
        return true;
    }
    
    /**
     * 获取短信列表
     * 
     * @param array $requestData
     * @return mixed
     */
    public static function getSmsList($requestData){
        if (empty($requestData)){
            throw new UserException('params error');
        }
        $query = PhoneMessage::find();
        $query->andFilterWhere(['phone_number'=>$requestData['phone_num']]);
        if (!empty($requestData['start_time'])){
            $query->andFilterWhere(['>=', 'send_time', $requestData['start_time']]);
        }
        if (!empty($requestData['end_time'])){
            $requestData['end_time'] = date("Y-m-d 23:59:59", strtotime($requestData['end_time']));
            $query->andFilterWhere(['<=', 'send_time', $requestData['end_time']]);
        }
        $smsList = self::getPagingData($query, ['type'=>'desc','field'=>'send_time'], true);
        return $smsList['data'];
    }
    
    /**
     * 获取短信详情
     * 
     * @param int $smsId
     * @return array
     */
    public static function getSmsDetail($smsId){
        if (empty($smsId)){
            throw new UserException('params error');
        }
        $messageDetail = PhoneMessage::find()->where(['id'=>$smsId])->asArray()->one();
        return $messageDetail;
    }
}
