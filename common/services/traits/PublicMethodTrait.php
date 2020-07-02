<?php
/**
 * controller trait --some public method for controller
 *
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/7/27
 * Time: 15:30
 */
namespace common\services\traits;

use common\jobs\SendJpush;
use yii\web\Response;

trait  PublicMethodTrait
{
    public static $defaultPageSize = 15;

    /**
     * @param \Throwable $e
     * @return mixed
     */
    public function renderErrorJson(\Throwable $e)
    {
        \Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'code'=>($e->getCode() == 0)?1:$e->getCode(),
            'message'=>$e->getMessage()
        ];
    }

    /**
     * 极光推送
     * @param int $pushType 推送类型  （1：营销通知，2：系统通知，3：订单通知，4：支付通知）
     * @param array $pushData   推送数据（sendId：发送者Id；sendIdentity：发送者身份；acceptIdentity：接受者身份；acceptId：接受者Id；title：消息标题；messageType：消息类型；messageBody：消息体）
     * @param int $msgType  0:不存业务消息; 1:存业务消息:(提前一小时预约单提醒司机, 用户取消订单(无责), 用户取消订单(有责), 支付成功, 系统改派, 在线调账 , 系统通知).
     * @param int $noticeType 1:透传 ； 2：通知
     * @return array|mixed
     */
    public static function jpush($pushType, $pushData=[], $msgType = 0, $noticeType = 1){
        if (empty($pushData)){
            return false;
        }
        $result = \Yii::$app->queue->push(new SendJpush(['pushType'=>$pushType, 'pushData'=>$pushData, 'msgType'=>$msgType,'noticeType'=>$noticeType]));
        return $result;

    }
}