<?php

namespace console\controllers;

use common\jobs\BatchSmsMsg;
use common\logic\HttpTrait;
use common\models\Coupon;
use common\models\CouponTask;
use common\models\Decrypt;
use common\models\ListArray;
use common\models\PassengerInfo;
use common\models\PeopleTag;
use common\models\SmsAppTemplate;
use common\models\UserCoupon;
use common\services\traits\PublicMethodTrait;
use common\util\Common;
use yii\console\Controller;

class CouponTaskController extends Controller
{
    use PublicMethodTrait, HttpTrait;

    protected $task = null;

    // 执行任务
    public function actionIndex()
    {
        $task = $this->getTask();
        if (!$task) {
            return '没有需要执行的任务';
        }
        $task->task_status = 1; // 标记任务开始
        $task->start_time = date('Y-m-d H:i:s');
        $task->save();
        set_time_limit(300);
        // 开始执行任务
        $startTime = date('Y-m-d H:i:s');
        $result = $this->execTask($task);
        $task->task_status = 2; // 标记任务开始
        $task->save();
        $endTime = date('Y-m-d H:i:s');
        // 触发优惠券更新
        $this->actionUpdateSum($task->coupon_id);
        return "编号为{$task->id}的任务执行完成,开始时间：$startTime,结束时间$endTime";
    }

    public function actionUpdateSum($couponId = false)
    {
        if (!intval($couponId)) {
            return '未找到优惠券';
        }
        $couponInfo = Coupon::findOne(['id' => $couponId]);
        if (!$couponInfo) {
            return '未找到优惠券';
        }
        $getNumber = UserCoupon::find()->where(['coupon_id' => $couponId])->count();
        $couponInfo->total_number = $getNumber;
        $couponInfo->get_number = $getNumber;
        if ($getNumber) {
            $couponInfo->used_number = UserCoupon::find()->where(['coupon_id' => $couponId, 'is_use' => 1])->count();
        }
        $couponInfo->save();
        return '优惠券ID' . $couponId . '统计数据更新完成';
    }

    public function actionTest()
    {

    }

    private function execTask($task)
    {
        // 获取目标
        if ($task->task_target) {
            $targetPhones = explode(',', $task->task_target);
            $encryptPhones = Decrypt::encryptPhones($targetPhones);
            $targets = PassengerInfo::find()->where(['phone' => $encryptPhones])->select('id,phone')->asArray()->all();
            $targetIds = array_column($targets, 'id');
        } elseif ($task->people_tag_id) {
            $tagMaps = (new PeopleTag())->getTagPhonesByTagId($task->people_tag_id);
            $targetPhones = $tagMaps ? array_values($tagMaps) : [];
            $targetIds = $tagMaps ? array_keys($tagMaps) : [];
        } else {
            return false;
        }
        // 发送乘客优惠券
        if ($targetIds && $task->coupon_id) {
            $activityInfo = [
                'activity_tag' => $task->task_tag,
                'activity_id' => $task->id,
            ];
            $this->pushCoupon($targetIds, $task->coupon_id, $activityInfo, $task->number);
        }
        // 发送APP消息
        if ($task->app_tpl_id) {
            $this->sendAppMessage($targetIds);
        }
        // 发送短信消息
        if ($task->sms_tpl_id) {
            $this->sendSmsMessage($targetPhones);
        }
        return true;
    }

    private function pushCoupon($passengerIds, $couponId, $activityInfo = false, $number = 1)
    {
        if (!is_array($passengerIds) || !count($passengerIds)) {
            return false;
        }
        $phoneMap = (new ListArray())->getPassengerPhoneNumberByIds($passengerIds);
        foreach ($passengerIds as $id) {
            isset($phoneMap[$id]) && $activityInfo['phone_number'] = $phoneMap[$id];
            $count = 0;
            do {
                Coupon::pushOneCoupon($id, $couponId, $activityInfo);
                $count++;
            } while ($count < $number);
        }
        return true;
    }

    private function sendAppMessage($userIds)
    {
        $task = $this->getTask();
        $tpl = SmsAppTemplate::findOne(['id' => $task->app_tpl_id]);
        if (!$tpl) {
            return false;
        }
        $data = array(
            'content' => $tpl->content,
        );
        $reqPrams = [
            'sendId' => 'system',//	发送者Id
            'sendIdentity' => 1,//	发送者身份
            'acceptIdentity' => 1,//	接受者身份
            'messageType' => 1, // 消息类型
            'title' => "优惠券",//	消息标题
            'messageBody' => $data,//	消息体
        ];
        foreach ($userIds as $item) {
            $reqPrams['acceptId'] = $item;
            self::jpush(1, $reqPrams, 1, 2);
        }
        return true;
    }

    private function sendSmsMessage($phones)
    {
        /*
        $task = $this->getTask();
        $tpl = SmsAppTemplate::findOne(['id' => $task->sms_tpl_id]);
        if (!$tpl) {
            return false;
        }
        */
        foreach ($phones as $item) {
            Common::sendMessageNew($item, 'HX_0029', []);
        }

        return true;
    }

    /**
     * @return null|\yii\db\ActiveRecord
     */
    private function getTask()
    {
        if ($this->task) {
            return $this->task;
        }
        $task = CouponTask::find()
            ->where(['task_status' => 0])
            ->andWhere(['<', 'plan_time', date('Y-m-d H:i:s')])
            ->andWhere(['is_cancel' => 0])
            ->limit(1)->one();
        $this->task = $task;
        return $task;
    }

}