<?php

namespace application\modules\coupon\controllers;

use common\logic\LogicTrait;
use common\models\CouponClass;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;
use application\controllers\BossBaseController;
/**
 * Site controller
 */
class CouponClassController extends BossBaseController
{
    use ModelTrait;
    use LogicTrait;
    /**
     * @return array
     */
    public function actionIndex()
    {
        $className = \Yii::$app->request->post('className');
        $stat = \Yii::$app->request->post('pause');
        $query = CouponClass::find();
        if ($className) {
            $query->where(['coupon_name' => trim($className)]);
        }
        if ($className != '') {
            $query->andWhere(['is_pause' => intval($stat)]);
        }
        $classes = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);

        LogicTrait::fillUserInfo($classes['data']['list']);

        // 返回数据
        return $this->asJson(Common::key2lowerCamel($classes));
    }

    /**
     * @return array
     */
    public function actionStore()
    {
        $request = \Yii::$app->request;

        $couponName = $request->post('className');
        if (!trim($couponName)) {
            return Json::message('类型名称不能为空');
        }

        $couponClass = new CouponClass();

        $couponTypes = $couponClass->couponTypes;
        $couponType = $request->post('couponType');
        if (!isset($couponTypes[$couponType])) {
            return Json::message('不支持的折扣方式');
        }

        $reductionAmount = round(floatval($request->post('reductionAmount')),2);
        $discount = round(floatval($request->post('discount')), 2);
        if ($couponType == 1) {
            if ($reductionAmount <= 0) {
                return Json::message('金额必须大于0');
            }
            $discount = 0;
        } else {
            if ($discount <= 0 || $discount >= 1) {
                return Json::message('折扣数据错误');
            }
            $reductionAmount = 0;
        }

        $couponClass->coupon_name = $couponName;
        $couponClass->coupon_type = $couponType;
        $couponClass->reduction_amount = $reductionAmount;
        $couponClass->discount = $discount;

        // 添加当前用户名!!!!!
        $couponClass->operator_id = $this->userInfo['id'];


        if (!$couponClass->save()) {
            return Json::message('添加失败');
        }

        return Json::message('添加成功', 0);

    }

    public function actionPause()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $stat = intval($request->post('pause'));

        if (!in_array($stat, [0, 1])) {
            return Json::message('参数异常');
        }
        $couponClass = CouponClass::findOne(['id' => $id]);
        if (!$couponClass) {
            return Json::message('参数错误');
        }
        $couponClass->is_pause = $stat;
        // 添加当前用户名!!!!!
        $couponClass->operator_id = $this->userInfo['id'];
        $couponClass->save();

        return Json::message('设置成功', 0);

    }


}
