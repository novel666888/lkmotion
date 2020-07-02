<?php
namespace passenger\modules\user\controllers;

use common\controllers\ClientBaseController;
use common\models\UserCoupon;
use common\util\Common;
use common\util\Json;

/**
 * 用户中心 - 优惠券相关
 */
class CouponController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 获取优惠券列表
     * @return [type] [description]
     */
    public function actionGetCouponList()
    {
        $requestData=[];
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passengerId'] = $this->userInfo['id'];
        }
        $field=['uc.coupon_id', 'uc.coupon_type', 'uc.reduction_amount', 'uc.minimum_amount', 'uc.discount', 'uc.enable_time', 'uc.expire_time'];
        $data = UserCoupon::getCouponList($requestData, $field);
 		return Json::success(Common::key2lowerCamel($data));
    }

    /**
     * 获取优惠券用卷记录
     * @return [type] [description]
     */
    public function actionGetUsedRecord()
    {
    	$requestData=[];
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passengerId'] = $this->userInfo['id'];
        }
        $field=['uc.coupon_id', 'uc.coupon_type', 'uc.reduction_amount', 'uc.minimum_amount', 'uc.discount', 'uc.enable_time', 'uc.expire_time', 'uc.use_time'];
        $data = UserCoupon::getUsedRecordList($requestData, $field);
 		return Json::success(Common::key2lowerCamel($data));
    }

    /**
     * 获取用户优惠券列表
     * @return array|\yii\web\Response
     */
    public function actionList()
    {
        if (empty($this->userInfo['id'])) {
            return Json::message("请先登录");
        }
        $userId = $this->userInfo['id'];
        $type = \Yii::$app->request->post('type');
        //$userId = 961;
        $data = (new UserCoupon())->getCoupons($userId, $type);

        return $this->asJson(Common::key2lowerCamel($data));
    }
}