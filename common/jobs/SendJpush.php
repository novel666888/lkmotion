<?php
namespace common\jobs;

use yii\base\BaseObject;
use common\services\YesinCarHttpClient;
use common\models\PushAppMessage;
use common\models\MessageShow;

class SendJpush extends BaseObject implements \yii\queue\JobInterface
{
    public $pushType;  //推送类型  （1：营销通知，2：系统通知，3：订单通知，4：支付通知）
    public $pushData=[];  //推送数据（sendId：发送者Id；sendIdentity：发送者身份；acceptIdentity：接受者身份；acceptId：接受者Id；title：消息标题；messageType：消息类型；messageBody：消息体）
    public $msgType;  //0:不存业务消息; 1:存业务消息:(提前一小时预约单提醒司机, 用户取消订单(无责), 用户取消订单(有责), 支付成功, 系统改派, 在线调账 , 系统通知).
    public $noticeType = 1; //1:透传 ； 2：通知
    
    public function execute($queue){
        if (empty($this->pushData)){
            return false;
        }else{
            $content = $this->pushData['messageBody']['content'] ?? '';
            $orderId = $this->pushData['messageBody']['orderId'] ?? '0';
            $smsSendAppId = $this->pushData['messageBody']['smsSendAppId'] ?? '0';
            $this->pushData['messageBody'] = json_encode($this->pushData['messageBody']);
        }
        \Yii::info(json_encode($this->pushData,256),'push_data');
        $push = \Yii::$app->params['api']['message'];
        $pushUrl = $this->noticeType == 1 ? $push['method']['jpush'] : $push['method']['notice'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$push['serverName']]);
        $res = $YesinCarHttpClient->post($pushUrl, $this->pushData);
        \Yii::info(json_encode($res,256),'push_res');
        //推送成功记录数据
        if ($res['code'] == 0){
            $message = array(
                'yid' => (string)$this->pushData['acceptId'],
                'title' => $this->pushData['title'],
                'push_type' => $this->pushType,
                'content' => $this->pushData['messageBody'],
                'accept_identity' => $this->pushData['acceptIdentity'],
                'sms_send_app_id' => $smsSendAppId,
                'send_time' => date("Y-m-d H:i:s", time()),
            );
            $pushAppMessage = new PushAppMessage();
            $pushAppMessage->attributes = $message;
            if(!$pushAppMessage->save()){
                \Yii::info($pushAppMessage->getFirstError(), 'insert_push_app_message_error');
            }
            //存业务消息
            if ($this->msgType == 1){
                $message = array(
                    'yid' => (string)$this->pushData['acceptId'],
                    'title' => $this->pushData['title'],
                    'order_id' => $orderId,
                    'content' => $content,
                    'accept_identity' => $this->pushData['acceptIdentity'],
                    'push_type' => $this->pushType,
                    'send_time' => date("Y-m-d H:i:s", time()),
                    'sms_send_app_id' => $smsSendAppId,
                );
                $messageShow = new MessageShow();
                $messageShow->attributes = $message;
                if(!$messageShow->save()){
                    \Yii::info($messageShow->getFirstError(), 'insert_message_show_error');
                }
            }
        }
        return $res;
    }
}