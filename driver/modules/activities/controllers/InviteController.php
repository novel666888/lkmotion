<?php

namespace driver\modules\activities\controllers;

use common\controllers\ClientBaseController;
use common\models\InviteRecord;
use common\util\Json;

/**
 * Site controller
 */
class InviteController extends ClientBaseController
{

    public function actionStore()
    {
        $driverId = isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0;
        $request = \Yii::$app->request;

        $driverName = $request->post('driverName', '');
        $phoneNumber = $request->post('phoneNumber', '');
        $driveAge = $request->post('driveAge', 1);
        $gender = $request->post('gender', 1);
        $city = $request->post('city', '');

        $storeData = compact('driverName', 'phoneNumber', 'driveAge', 'gender', 'city');

        // 保存推荐关系
        $invite = new InviteRecord();
        $invite->invite_driver_id = $driverId;
        $invite->invitee_info = json_encode($storeData, 256);;

        if (!$invite->save()) {
            return Json::message('提交失败,请稍后再试');
        }

        return Json::success();
    }

}
