<?php

namespace application\modules\activities\controllers;

use application\controllers\BossBaseController;
use common\logic\LogicTrait;
use common\models\Activities;
use common\models\Coupon;
use common\models\PeopleTag;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;

/**
 * Site controller
 */
class ManageController extends BossBaseController
{
    use ModelTrait, LogicTrait;

    protected $minExpire = 86400; // 优惠券最小过期时间

    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $activityName = trim($request->post('activityName'));
        $activityStatus = $request->post('activityStatus', '');
        $status = $request->post('status', '');
        $query = Activities::find();
        if ($activityName) {
            $query->where(['activity_name' => ($activityName)]);
        }
        if (in_array($activityStatus, ['0', '1', '2'])) {
            $dateTime = date('Y-m-d H:i');
            if ($activityStatus == '2') {
                $query->andWhere(['<=', 'expire_time', $dateTime]);
            } elseif ($activityStatus == '1') {
                $query->andWhere(['<', 'enable_time', $dateTime])->andWhere(['>', 'expire_time', $dateTime]);
            } else {
                $query->andWhere(['>', 'enable_time', $dateTime]);
            }
        }
        if ($status !== '') {
            $query->andWhere(['status' => intval($status)]);
        }
        $activities = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
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

        $activityName = trim(strval($request->post('activityName')));
        if (!$activityName) {
            return Json::message('活动名称不能为空');
        }
        $id = intval($request->post('id'));
        $hasOne = Activities::findOne(['activity_name' => $activityName]);
        if ($hasOne && $hasOne->id != $id) {
            return Json::message('活动名称不能重复');
        }
        $enableTime = trim($request->post('enableTime'));
        if (!$enableTime) {
            $enableTime = date('Y-m-d H:i');
        }
        $enableTime = date('Y-m-d H:i:s', strtotime($enableTime));
        $expireTime = trim($request->post('expireTime'));
        if (!$expireTime) {
            return Json::message('活动结束时间不能为空');
        }
        $joinCycle = trim($request->post('joinCycle'));
        $bonusesRule = trim($request->post('bonusesRule'));
        $activityDesc = trim($request->post('activityDesc'));

        $activity = new Activities();
        $types = $activity->types;
        $activityType = intval($request->post('activityType'));
        if (!isset($types[$activityType])) {
            return Json::message('未识别的活动方式');
        }
        // 返券数据检测
        if ($activityType === 1) {
            // 优惠券过期时间检测
            $message = $this->checkCouponExpire($bonusesRule);
            if ($message) {
                return Json::message($message);
            }
        }
        // 检测人群模板
        $peopleTagId = intval($request->post('peopleTag'));
        $peopleTag = PeopleTag::findOne(['id' => $peopleTagId]);
        if (!$peopleTag) {
            return Json::message('未找到目标人群');
        }
        // 活动时间检测
        $result = $this->checkTimeCover($activityType, $enableTime, $expireTime);
        if ($result) {
            return Json::message($result);
        }
        // 填充参数
        if ($id) {
            $activity = Activities::findOne(['id' => $id]);
            if (!$activity) {
                return Json::message('参数异常');
            }
        } else {
            $activity = new Activities();

        }
        $activity->activity_name = $activityName;
        $activity->activity_type = $activityType;
        $activity->enable_time = $enableTime;
        $activity->expire_time = $expireTime;
        $activity->join_cycle = $joinCycle;
        $activity->bonuses_rule = $bonusesRule;
        $activity->activity_desc = $activityDesc;
        $activity->people_tag = $peopleTagId;

        // 录入用户
        $activity->operator_id = $this->userInfo['id'] ?? 0;
        // 保存活动信息
        if (!$activity->save()) {
            return Json::message('信息保存失败');
        }
        // 更新活动编号
        $activity->activity_no = $types[$activityType]['short'] . str_pad($activity->id, 6, '0', STR_PAD_LEFT);
        $activity->save();

        return Json::message('操作成功', 0);
    }

    /**
     * 活动详情
     * @return array
     */
    public function actionShow()
    {
        $activityId = $this->getActivityId();
        if (!$activityId) {
            return Json::message('参数ID异常');
        }
        $activityInfo = Activities::findOne(['id' => $activityId]);
        if (!$activityInfo) {
            return Json::message('参数ID异常');
        }
        isset($activityInfo->activity_type) && $activityInfo->activity_type = strval($activityInfo->activity_type);
        isset($activityInfo->bonuses_rule) && $activityInfo->bonuses_rule = json_decode($activityInfo->bonuses_rule);
        return Json::success(Common::key2lowerCamel($activityInfo->toArray()));
    }


    public function actionPause()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $status = intval($request->post('status'));

        if (!in_array($status, [0, 1])) {
            return Json::message('参数异常');
        }
        $activity = Activities::findOne(['id' => $id]);
        if (!$activity) {
            return Json::message('参数异常');
        }
        $activity->status = $status;
        // 录入用户
        $activity->operator_id = $this->userInfo['id'];
        $activity->save();

        return Json::message('设置成功', 0);

    }

    private function patchStatus(&$item)
    {
        $timeString = date('Y-m-d H:i');
        if ($item['enable_time'] > $timeString) {
            $item['activity_status'] = '0';
        } elseif ($item['expire_time'] < $timeString) {
            $item['activity_status'] = '2';
        } else {
            $item['activity_status'] = '1';
        }
    }

    private function getActivityId()
    {
        $request = \Yii::$app->request;
        $rawId = $request->get('activityId');
        $activityId = intval($rawId);
        if ($activityId) return $activityId;

        $activityId = intval(substr($rawId, 2));
        if ($activityId) return $activityId;

        $rawId = $request->post('activityId');
        $activityId = intval($rawId);
        if ($activityId) return $activityId;

        $activityId = intval(substr($rawId, 2));
        return $activityId;
    }

    /**
     * 返券-优惠券参数检测
     * @param $bonusesRule
     * @return bool|string
     */
    private function checkCouponExpire($bonusesRule)
    {
        if (!$bonusesRule) {
            return '返券设置不能为空';
        }
        if (is_string($bonusesRule)) {
            $bonusesRule = json_decode($bonusesRule, 1);
        }
        if (!$bonusesRule || !isset($bonusesRule['coupons']) || !$bonusesRule['coupons']) {
            return '返券设置参数异常';
        }
        $couponIds = array_unique(array_column($bonusesRule['coupons'], 'couponId'));
        $expire = Coupon::find()
            ->where(['in', 'id', $couponIds])
            ->andWhere(['effective_type' => 1])// 固定期限的优惠券
            ->andWhere(['<', 'expire_time', date('Y-m-d H:i:s', time() + $this->minExpire)])
            ->limit(1)->one();
        if ($expire) {
            return "[$expire->coupon_name]将于" . intval($this->minExpire / 3600) . '小时内过期';
        }
        return false;
    }

    /**
     * @param $type
     * @param $enableTime
     * @param $expireTime
     * @return bool|string
     */
    private function checkTimeCover($type, $enableTime, $expireTime)
    {
        $now = date('Y-m-d H:i:s');
        if ($enableTime < $now) {
            return '活动开始时间不能晚于今天';
        }
        if ($expireTime <= $enableTime) {
            return '活动结束时间必须大于活动开始时间';
        }
        if ($type == 1) {
            return false;
        }
        $activeActivity = Activities::find()->where(['activity_type' => $type, 'status' => 1])->andWhere(['>', 'expire_time', $now])->all();
        if (!$activeActivity) {
            return false;
        }
        foreach ($activeActivity as $item) {
            if ($item->enable_time < $enableTime && $enableTime < $item->expire_time) {
                return '活动开始时间和活动[' . $item->activity_name . ']有重复';
            }
            if ($item->enable_time < $expireTime && $expireTime < $item->expire_time) {
                return '活动结束时间和活动[' . $item->activity_name . ']有重复';
            }
        }
        return false;
    }

}
