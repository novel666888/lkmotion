<?php

namespace passenger\modules\activities\controllers;

use common\controllers\ClientBaseController;
use common\events\PassengerEvent;
use common\logic\HttpTrait;
use common\models\Activities;
use common\models\Decrypt;
use common\models\InviteRecord;
use common\models\Order;
use common\models\PassengerInfo;
use common\models\UserCoupon;
use common\util\Common;
use common\util\Json;

/**
 * Site controller
 */
class InviteController extends ClientBaseController
{
    use HttpTrait;

    /**
     * @return array
     */
    public function actionMyInvite()
    {
        $userId = isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0;
        $query = InviteRecord::find()->select('id,create_time,invitee_info,passenger_id')
            ->where(['invite_passenger_id' => $userId]);
        $list = $query->orderBy('create_time DESC')->asArray()->all();
        $inviteeIds = array_column($list, 'passenger_id');
        if ($inviteeIds) {
            $query = Order::find()->select(['count(id) as total', 'passenger_info_id as passenger_id'])
                ->where(['in', 'passenger_info_id', $inviteeIds])
                ->andWhere(['>', 'driver_id', 0]);
            $orders = $query->groupBy('passenger_id')->asArray()->all();
            $orderMap = array_column($orders, 'total', 'passenger_id');
        } else {
            $orderMap = [];
        }
        if (count($list)) {
            foreach ($list as &$item) {
                $item['invitee_info'] = json_decode($item['invitee_info']);
                if (isset($item['invitee_info']->phone)) {
                    $item['invitee_info']->phone = $this->desPhone($item['invitee_info']->phone);
                }
                $item['is_use'] = $orderMap[$item['passenger_id']] ?? '0';
            }
        }
        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    /**
     * @return array
     */
    public function actionMyBonus()
    {
        $userId = isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0;
        $invites = InviteRecord::find()->select('DISTINCT(activity_no) as activity_no')->where(['invite_passenger_id' => $userId])->asArray()->all();
        if (!$invites) {
            Json::message('暂无数据');
        }
        // 获取拉新活动编号
        $allActivity = Activities::find()->where(['activity_type' => 3])->select('id,activity_no')->all();
        $activityTags = array_column($allActivity, 'activity_no');
        $activityCoupons = UserCoupon::find()
            ->where(['passenger_id' => $userId])
            ->andWhere(['in', 'activity_tag', $activityTags])
            ->select('id,coupon_type,create_time,is_use,reduction_amount,discount')
            ->orderBy('create_time DESC')
            ->asArray()
            ->all();
        return Json::success(['list' => Common::key2lowerCamel($activityCoupons)]);
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionRegister()
    {
        $request = \Yii::$app->request;
        $phoneNumber = trim($request->post('phoneNumber', ''));
        // 手机号检测
        if (!is_numeric($phoneNumber) || strlen($phoneNumber) < 8) {
            return Json::message("手机号不正确");
        }
        try {
            $encryptPhone = Decrypt::encryptPhone($phoneNumber);
        } catch (\Exception $e) {
            return Json::message("注册服务暂不可用");
        }
        $passenger = PassengerInfo::findOne(['phone' => $encryptPhone]);
        if ($passenger) {
            return Json::message('该手机号已领取');
        }
        // 注册
        $requestData = [
            "phoneNum" => $phoneNumber, // 注册手机号
            "registerSource" => 'Browser', // 浏览器
            "marketChannel" => 'unknown', // 未知渠道
        ];
        $response = self::httpPost('account.regist', $requestData);
        if ($response['code']) {
            return $this->asJson($response);
        }
        $passengerId = $response['data']['id'];

        // 保存推荐关系
        $inviteId = intval($request->post('inviterId'));

        $invite = new InviteRecord();
        $invite->invite_passenger_id = $inviteId;
        $invite->passenger_id = $passengerId;
        $invite->invitee_info = json_encode(['phone' => $phoneNumber]);
        $invite->activity_no = $this->getInviteActivityNo();

        if (!$invite->save()) {
            \Yii::debug('邀请注册信息保存失败', 'invite_register');
        }
        // 发送注册成功事件
        (new PassengerEvent())->register($passengerId);

        // 返回注册成功
        return Json::success();
    }

    private function desPhone($phone)
    {
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 获取当前拉新活动编号
     * @return string
     */
    private function getInviteActivityNo()
    {
        $now = date('Y-m-d H:i:s');
        $activity = Activities::find()
            ->where(['activity_type' => 3])
            ->andWhere(['<', 'enable_time', $now])
            ->andWhere(['>', 'expire_time', $now])
            ->select('activity_no')
            ->limit(1)
            ->scalar();
        return strval($activity);
    }
}
