<?php

namespace application\modules\coupon\controllers;

use common\logic\LogicTrait;
use common\models\Coupon;
use common\models\CouponTask;
use common\models\Decrypt;
use common\models\ListArray;
use common\models\PassengerInfo;
use common\models\PeopleTag;
use common\models\UserCoupon;
use common\util\Common;
use common\util\Json;
use application\controllers\BossBaseController;
/**
 * Site controller
 */
class CouponTaskController extends BossBaseController
{
    use LogicTrait;

    /**
     * 优惠券列表
     *
     * @return array
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $couponName = trim($request->post('couponName'));
        $taskStat = $request->post('taskStat');

        $query = CouponTask::find();

        if (!empty($couponName)) {
            $query->where(['coupon_name' => $couponName]);
        }
        if ($taskStat != '') {
            $query->where(['task_status' => intval($taskStat)]);
        }
        $tasks = CouponTask::getPagingData($query, ['id' => SORT_DESC]);

        LogicTrait::fillUserInfo($tasks['data']['list']);

        return Json::success(Common::key2lowerCamel($tasks['data']));
    }


    /**
     * 存储优惠券发送任务
     * @return array
     */
    public function actionStore()
    {
        $request = \Yii::$app->request;

        // 检测优惠券
        $couponId = intval($request->post('couponId'));
        \Yii::debug($request->post(), 'coupon_task_store');
        $coupon = Coupon::findOne(['id' => $couponId]);
        if (!$coupon) {
            return Json::message('参数错误');
        }
        if ($coupon->effective_type ==1 && strtotime($coupon->expire_time) < (time() + 86400)) {
            return Json::message('优惠券过期时间不足24小时');
        }

        // 处理目标人群
        $peopleTagId = intval($request->post('peopleTagId'));
        if ($peopleTagId) {
            $peopleTag = PeopleTag::find()->where(['id' => $peopleTagId])->limit(1)->one();
            if (!$peopleTag) {
                return Json::message('未找到目标人群');
            }
            $mobiles = '';
        } else {
            $mobileStr = trim($request->post('taskTarget'));
            if (!$mobileStr || !is_string($mobileStr)) {
                return Json::message('参数错误');
            }
            $mobiles = $this->formatPhoneString($mobileStr);
        }

        // 处理数量
        $number = intval($request->post('number'));
        if ($number < 1) {
            $number = 1;
        } elseif (($number > 99)) {
            $number = 99;
        }

        // 获取消息模板
        $appTplId = intval($request->post('appTplId'));
        $smsTplId = intval($request->post('smsTplId'));

        // 获取计划时间
        $planTime = trim($request->post('planTime'));
        if (!$planTime) {
            $taskName = '即时任务';
            $planTime = date('Y-m-d H:i');
        } else {
            $taskName = '计划任务';
        }

        // 填充参数
        $id = intval($request->post('id'));
        if ($id) {
            $task = CouponTask::findOne(['id' => $id]);
            if (!$task) {
                return Json::message('ID参数错误');
            }
        } else {
            $task = new CouponTask();
            $task->task_tag = uniqid();
        }
        $task->coupon_name = $coupon->coupon_name;
        $task->coupon_id = $coupon->id;
        $task->people_tag_id = $peopleTagId;
        $task->task_target = $mobiles;
        $task->number = $number;
        $task->app_tpl_id = $appTplId;
        $task->sms_tpl_id = $smsTplId;
        $task->plan_time = $planTime;

        // 录入用户
        $task->operator_id = $this->userInfo['id'] ? $this->userInfo['id'] : 0;

        if (!$task->save()) {
            return Json::message('任务保存失败');
        }

        return Json::message($taskName . '操作成功', 0);
    }

    /**
     * 任务状态变更
     * @return array
     */
    public function actionPause()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $stat = intval($request->post('pause'));

        if (!in_array($stat, [0, 1])) {
            return Json::message('参数异常');
        }
        $task = CouponTask::find()->where(['id' => $id])->limit(1)->one();
        if (!$task) {
            return Json::message('参数异常');
        }

        // 检测活动是否可以终止
        if ($stat) {
            if (strtotime($task->plan_time) < time() || $task->task_status > 0) {
                return Json::message('任务进行中,不能变更状态');
            }
        } else {
            if (strtotime($task->plan_time) < time()) {
                return Json::message('任务已过期,无法恢复');
            }
        }
        $task->is_cancel = $stat;
        // 录入用户
        $task->operator_id = $this->userInfo['id'];
        $task->save();

        return Json::message('设置成功', 0);

    }

    /**
     * 立即开始任务
     * @return array
     */
    public function actionStart()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $task = CouponTask::find()->where(['id' => $id])->limit(1)->one();
        if (!$task) {
            return Json::message('参数异常');
        }
        // 检测活动是否已经开始
        if (strtotime($task->plan_time) < time() || $task->task_status > 0) {
            return Json::message('任务已经开始,或调度中');
        }

        // 立即开始任务!!!!!
        $task->is_cancel = 0;
        $task->plan_time = date('Y-m-d H:i');
        $task->save();

        return Json::message('任务即将始执行', 0);
    }

    // 手动执行任务
    public function actionRun()
    {
        $task = CouponTask::find()
            ->where(['task_status' => 0])
            ->andWhere(['<', 'plan_time', date('Y-m-d H:i:s')])
            ->andWhere(['is_cancel' => 0])
            ->limit(1)->one();
        if (!$task) {
            return Json::message('没有需要执行的任务');
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
        $this->actionUpdateSum($task->coupon_id);
        return Json::message("编号为{$task->id}的任务执行完成,开始时间：$startTime,结束时间$endTime");
    }

    // 触发优惠券更新
    public function actionUpdateSum($couponId = false)
    {
        if(!intval($couponId)) {
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
//        $phoneMap = (new ListArray())->getPassengerPhoneNumberByIds($passengerIds);
        foreach ($passengerIds as $id) {
//            isset($phoneMap[$id]) && $activityInfo['phone_number'] = $phoneMap[$id];
            $count = 0;
            do {
                Coupon::pushOneCoupon($id, $couponId, $activityInfo);
                $count++;
            } while ($count < $number);
        }
        return true;
    }

    private function sendAppMessage($driverIds)
    {
        \Yii::debug($driverIds, 'send_app_message.driverId');
    }

    private function sendSmsMessage($phones)
    {
        \Yii::debug($phones, 'send_sms_message.phones');
    }

    private function formatPhoneString($str)
    {
        $str = str_replace(' ', '', $str);
        $str = str_replace(["\r", "\n", "\t"], ',', $str);
        return implode(',', array_filter(explode(',', $str)));
    }

}
