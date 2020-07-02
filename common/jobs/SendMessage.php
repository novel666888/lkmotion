<?php
namespace common\jobs;

use yii\base\BaseObject;
use yii\queue\JobInterface;
use common\services\YesinCarHttpClient;

class SendMessage extends BaseObject implements JobInterface
{
    public $phone; //电话号码 string
    public $smsId; //短信模板id string
    public $data; //键值对 一维数组
    
    public function execute($queue){
        if (empty($this->phone) || empty($this->smsId)){
            return;
        }
        $sendData = array(
            'phones' => $this->phone,
            'templateId' => $this->smsId,
            'content' => $this->data,
        );
        $account = \Yii::$app->params['api']['message'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['hxSendMessage'], $sendData);
        \Yii::info(json_encode($sendData,256), 'msg_code_data');
        \Yii::info(json_encode($result,256),'java_msg_code_result');
        return $result;
    }
}