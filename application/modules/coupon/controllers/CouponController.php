<?php

namespace application\modules\coupon\controllers;

use application\controllers\BossBaseController;
use common\logic\HttpTrait;
use common\models\Coupon;
use common\models\CouponConditions;
use common\models\Decrypt;
use common\models\ListArray;
use common\models\UserCoupon;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;
use passenger\models\PassengerInfo;

/**
 * Site controller
 */
class CouponController extends BossBaseController
{
    use ModelTrait, HttpTrait;

    /**
     * 优惠券列表
     *
     * @return array
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $couponName = trim($request->post('couponName'));
        $getMethod = $request->post('getMethod');
        $query = Coupon::find();
        if ($couponName) {
            $query->where(['like', 'coupon_name', $couponName]);
        }
        if ($getMethod != '') {
            $query->andWhere(['get_method' => intval($getMethod)]);
        }
        $coupons = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);

        return Json::success(Common::key2lowerCamel($coupons['data']));
    }


    public function actionStore()
    {
        $request = \Yii::$app->request;
        $couponClassId = intval($request->post('couponClassId'));
        $couponName = trim($request->post('couponName'));
        \Yii::debug($request->post(), 'coupon.add');
        $listModel = new ListArray();
        $activeClasses = $listModel->getCouponClasses(0, 1);

        if (!isset($activeClasses[$couponClassId])) {
            return Json::message('无该优惠券类型或暂停使用');
        }

        // 获取方法
        $getMethod = intval($request->post('getMethod', 1));
        if (!in_array($getMethod, [1, 2])) {
            return Json::message('不支持的获取方式');
        }

        // 获取有效期参数
        $effectiveType = intval($request->post('effectiveType'));
        if ($effectiveType == 1) {
            $enableTime = trim($request->post('enableTime'));
            if ($enableTime) {
                $enableTime = date('Y-m-d H:i:s', strtotime($enableTime));
            }
            $expireTime = trim($request->post('expireTime'));
            if ($expireTime) {
                $expireTime = date('Y-m-d H:i:s', strtotime($expireTime));
            }
            $effectiveDay = 0;
        } else {
            $enableTime = $expireTime = 0;
            $effectiveDay = intval($request->post('effectiveDay'));
        }

        // 金额限制
        $minimumAmount = floatval($request->post('minimumAmount'));
        $maximumAmount = floatval($request->post('maximumAmount'));

        // 使用说明
        $couponDesc = trim($request->post('couponDesc'));

        $coupon = new Coupon();

        // 冗余参数
        $couponClass = $activeClasses[$couponClassId];
        unset($couponClass['id']);
        foreach ($couponClass as $key => $value) {
            $coupon->{$key} = $value;
        }

        // 输入赋值
        $coupon->coupon_class_id = $couponClassId;
        $coupon->coupon_class_name = $coupon->coupon_name;
        if ($couponName) {
            $coupon->coupon_name = $couponName;
        }
        $coupon->get_method = $getMethod;
        $coupon->minimum_amount = $minimumAmount;
        $coupon->maximum_amount = $maximumAmount;
        $coupon->effective_type = $effectiveType;
        $coupon->effective_day = $effectiveDay;
        $coupon->enable_time = $enableTime;
        $coupon->expire_time = $expireTime;
        $coupon->coupon_desc = $couponDesc;

        // 录入用户
        $operatorId = $this->userInfo['id'] ?? 0;
        if ($operatorId) {
            $coupon->create_user = $this->userInfo['phone'];
            $coupon->operator_id = $operatorId;
        }

        $coupon->validate();
        $res = $coupon->getErrors();
        if ($res) {
            \Yii::debug($res);
        }
        if (!$coupon->save()) {
            return Json::message('优惠券信息保存失败');
        }
        $result = $this->storeUseConditions($coupon->id);

        if (!$result) {
            return Json::message('使用规则保存失败');
        }

        return Json::message('优惠券添加成功', 0);
    }

    /**
     * 优惠券详情
     * @return array
     */
    public function actionDetails()
    {
        $request = \Yii::$app->request;
        $couponId = intval($request->get('id'));
        if (!$couponId) {
            $couponId = intval($request->post('id'));
        }
        $couponInfo = Coupon::find()->where(['id' => $couponId])->limit(1)->asArray()->one();
        if (!$couponInfo) {
            return Json::message('参数异常');
        }
        $condition = CouponConditions::find()->where(['coupon_id' => $couponId])->limit(1)->asArray()->one();
        if ($condition) {
            unset($condition['id']);
            $couponInfo = array_merge($couponInfo, $condition);
        }
        return Json::success(Common::key2lowerCamel($couponInfo));
    }

    /**
     * @return \yii\web\Response
     */
    public function actionGetRecords()
    {
        $request = \Yii::$app->request;
        $couponId = intval($request->post('couponId'));
        $activityTag = trim($request->post('activityTag'));
        $phoneNumber = trim($request->post('phoneNumber'));
        $query = UserCoupon::find()->where(['coupon_id' => $couponId]);
        if ($activityTag) {
            $query->andWhere(['activity_tag' => $activityTag]);
        }
        if ($phoneNumber) {
            $query->andWhere(['passenger_id' => $this->getUserIdByPhone($phoneNumber)]);
        }
        $getList = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        // 获取手机号
        $this->patchPassengerPhone($getList['data']['list']);
        return $this->asJson(Common::key2lowerCamel($getList));
    }

    /**
     * @param $phone
     * @return int
     */
    private function getUserIdByPhone($phone)
    {
        try {
            $encryptPhone = Decrypt::encryptPhone($phone);
        } catch (\Exception $e) {
            $encryptPhone = false;
        }
        if (!$encryptPhone) return 0;
        $passengerInfo = PassengerInfo::findOne(['phone' => $encryptPhone]);
        if (!$passengerInfo) {
            return 0;
        }
        return $passengerInfo->id;
    }

    /**
     * @param $list
     */
    private function patchPassengerPhone(&$list)
    {
        $userIds = array_column($list, 'passenger_id');
        if (!$userIds) {
            return;
        }
        $phoneMap = (new ListArray())->getPassengerPhoneNumberByIds($userIds);
        // 打包信息
        foreach ($list as &$item) {
            $item['phone_number'] = ($phoneMap[$item['passenger_id']]) ?? '';
        }
    }

    /**
     * @param int $couponId
     * @return int
     */
    private function storeUseConditions($couponId = 0)
    {
        $request = \Yii::$app->request;
        // 时间段配置
        $hourRaw = $request->post('hourRaw', '');
        $hourSet = $this->getHourSet($hourRaw);
        // 星期配置
        $weekSet = $request->post('weekSet', '');
        // 特殊日期设!!!!!
        $dateRaw = $request->post('dateRaw', '');
        $dateSet = $this->getDateSet($dateRaw);
        // 城市配置
        $citySet = $request->post('citySet', '');
        // 服务配置
        $serviceSet = $request->post('serviceSet', '');
        // 车辆级别设置
        $levelSet = $request->post('levelSet', '');

        $couponConditions = new CouponConditions();
        $couponConditions->coupon_id = $couponId;
        $couponConditions->hour_set = $hourSet;
        $couponConditions->hour_raw = $hourRaw;
        $couponConditions->week_set = $weekSet;
        $couponConditions->date_set = $dateSet;
        $couponConditions->date_raw = $dateRaw;
        $couponConditions->city_set = $citySet;
        $couponConditions->service_set = $serviceSet;
        $couponConditions->level_set = $levelSet;

        $couponConditions->validate();
        $res = $couponConditions->getErrors();
        if ($res) {
            \Yii::debug($res);
        }
        return $couponConditions->save();
    }

    /**
     * @param $hourRaw
     * @return string
     */
    private function getHourSet($hourRaw)
    {
        $hourSet = '';
        if ($hourRaw) {
            $setGroup = [];
            $hourGroups = explode(',', $hourRaw);
            foreach ($hourGroups as $group) {
                $tmp = explode('/', $group);
                if (count($tmp) != 2) {
                    continue;
                }
                $start = intval($tmp[0]);
                $end = intval($tmp[1]);
                for ($i = $start; $i < $end; $i++) {
                    $setGroup[] = $i;
                }
            }
            array_unique($setGroup);
            if (count($setGroup)) {
                $hourSet = implode(',', $setGroup);
            }
        }
        return $hourSet;
    }

    /**
     * @param $dateRaw
     * @return string
     */
    private function getDateSet($dateRaw)
    {
        $dateSet = '';
        if ($dateRaw) {
            $setGroup = [];
            $hourGroups = explode(',', $dateRaw);
            foreach ($hourGroups as $group) {
                $tmp = explode('/', $group);
                $args = count($tmp);
                if ($args == 1) {
                    $setGroup[] = $tmp[0];
                    continue;
                }
                if ($args == 2) {
                    $start = strtotime(date('Y-m-d', strtotime($tmp[0])));
                    $end = strtotime(date('Y-m-d', strtotime($tmp[1])));
                    for ($i = $start; $i < $end; $i += 86400) {
                        $setGroup[] = date('Y-m-d', $i);
                    }
                }
            }
            array_unique($setGroup);
            if (count($setGroup)) {
                $dateSet = implode(',', $setGroup);
            }
        }
        return $dateSet;
    }

}
