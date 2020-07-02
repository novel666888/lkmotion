<?php

namespace application\modules\coupon\controllers;

use common\logic\LogicTrait;
use common\models\CouponActivity;
use common\util\Common;
use common\util\Json;
use application\controllers\BossBaseController;
/**
 * Site controller
 */
class ActivityController extends BossBaseController
{
    use LogicTrait;
    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $activityName = trim($request->post('activityName'));
        $activityStat = trim($request->post('activityStatus'));
        $status = $request->post('status', '');
        $query = CouponActivity::find();
        if ($activityName) {
            $query->where(['activity_name' => ($activityName)]);
        }
        if (in_array($activityStat, ['finished', 'ongoing', 'unstart'])) {
            $dateTime = date('Y-m-d H:i');
            if ($activityStat == 'finish') {
                $query->andWhere('expire_time <=' . $dateTime);
            } elseif ($activityStat == 'unstart') {
                $query->andWhere('enable_time >' . $dateTime);
            } elseif ($activityStat == 'ongoing') {
                $query->andWhere('enable_time <' . $dateTime)->andWhere('expire_time >' . $dateTime);
            }
        }
        if ($status !== '') {
            $query->andWhere(['status' => intval($status)]);
        }
        $activities = CouponActivity::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        if ($activities['code'] == 0) { // 增加状态
            LogicTrait::fillUserInfo($activities['data']['list']);
            foreach ($activities['data']['list'] as &$item) {
                $this->patchStatus($item);
            }
        }

        return $this->asJson(Common::key2lowerCamel($activities));
    }


    public function actionStore()
    {
        $request = \Yii::$app->request;


        $activityName = trim($request->post('activityName'));
        if (!trim($activityName)) {
            return Json::message('活动名称不能为空');
        }
        $activityNo = trim($request->post('activityNo'));

        $enableTime = trim($request->post('enableTime'));
        if (!$enableTime) {
            $enableTime = date('Y-m-d H:i');
        }
        $enableTime = date('Y-m-d H:i:s', strtotime($enableTime));
        $expireTime = trim($request->post('expireTime'));
        if (!$expireTime) {
            return Json::message('活动结束时间不能为空');
        }
        $expireTime = date('Y-m-d H:i:s', strtotime($expireTime));

        $couponRule = $request->post('couponRule');
        //var_dump(json_decode($couponRule));die();
        if (!$expireTime || !is_array(json_decode($couponRule))) {
            //return Json::message('领取设置不正确');
        }
        $getTimes = intval($request->post('getTimes'));
        if ($getTimes < 1) {
            $getTimes = 1;
        } elseif (($getTimes > 99)) {
            $getTimes = 99;
        }
        $activityType = trim($request->post('activityType'));
        $activityDesc = trim($request->post('activityDesc'));

        // 填充参数
        $id = intval($request->post('id'));
        if ($id) {
            $activity = CouponActivity::findOne(['id' => $id]);
            if (!$activity) {
                return Json::message('参数异常');
            }
        } else {
            $activity = new CouponActivity();

        }
        $activity->activity_name = $activityName;
        $activity->activity_no = $activityNo;
        $activity->activity_type = $activityType;
        $activity->coupon_rule = ($couponRule);
        $activity->enable_time = $enableTime;
        $activity->expire_time = $expireTime;
        $activity->get_times = $getTimes;
        $activity->activity_desc = $activityDesc;

        // 录入用户
        $activity->operator_id = $this->userInfo['id'];

        if (!$activity->save()) {
            return Json::message('信息保存失败');
        }

        return Json::message('操作成功', 0);
    }


    public function actionPause()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $stat = intval($request->post('pause'));

        if (!in_array($stat, [0, 1])) {
            return Json::message('参数异常');
        }
        $activity = CouponActivity::find()->where(['id' => $id])->limit(1)->one();
        if (!$activity) {
            return Json::message('参数异常');
        }

        // 检测活动是否可以终止
        if (strtotime($activity->enable_time) < time() && time() < strtotime($activity->expire_time)) {
            return Json::message('活动已开始,不能变更');
        }
        $activity->status = $stat;
        // 录入用户
        $activity->operator_id = $this->userInfo['id'];
        $activity->save();

        return Json::message('设置成功', 0);

    }

    private function patchStatus(&$item)
    {
        if (!$item['status']) {
            $item['activity_status'] = '9';
        }
        $timeString = date('Y-m-d H:i');
        if ($item['enable_time'] > $timeString) {
            $item['activity_status'] = '0';
        } elseif ($item['expire_time'] < $timeString) {
            $item['activity_status'] = '2';
        } else {
            $item['activity_status'] = '1';
        }
    }

}
