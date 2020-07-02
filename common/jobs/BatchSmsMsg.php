<?php

namespace common\jobs;

use common\logic\HttpTrait;
use common\models\PushAccount;
use yii\base\BaseObject;

/**
 * Class NotifyBridge.
 */
class BatchSmsMsg extends BaseObject implements \yii\queue\JobInterface
{
    use HttpTrait;

    public $receiver; // 接收人ID集合
    public $message; // 消息

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        // 检测接收人是否为空
        if (!is_array($this->receiver) || !count($this->receiver)) {
            return;
        }
        $pushData = $this->getMsgParams();
       // 调用批量发送短信接口
    }

    private function getMsgParams()
    {
        $reqPrams = [
            'sendId' => '',//	发送者Id
            'messageBody' => $this->message,//	消息体
        ];
        return $reqPrams;
    }
}
