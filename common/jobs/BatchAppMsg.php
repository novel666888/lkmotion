<?php

namespace common\jobs;

use common\services\traits\PublicMethodTrait;
use yii\base\BaseObject;

/**
 * Class NotifyBridge.
 */
class BatchAppMsg extends BaseObject implements \yii\queue\JobInterface
{
    use PublicMethodTrait;

    public $receiver; // 接收人ID集合
    public $title; // 消息标题
    public $message; // 消息
    const debug = true;

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
        foreach ($this->receiver as $item) {
            $pushData['acceptId'] = $item;
            if (self::debug) {
                $info = json_encode($pushData, 256);
                if (PHP_SAPI == 'cli') {
                    echo $info, PHP_EOL;
                } else {
                    \Yii::debug($info, 'app_massage_push_tag');
                }
            }
            if (empty($pushData['acceptIdentity'])) {
                continue;
            }
            // 异步推送消息
            self::jpush(1, $pushData, 1, 1);
        }
    }

    private function getMsgParams()
    {
        $reqPrams = [
            'sendId' => '',//	发送者Id
            'sendIdentity' => 1,//	发送者身份
            'acceptIdentity' => 1,//	接受者身份
            'acceptId' => '', //接受者Id
            'messageType' => 1, // 消息类型
            'title' => $this->title,//	消息标题
            'messageBody' => $this->message,//	消息体
        ];
        return $reqPrams;
    }
}
