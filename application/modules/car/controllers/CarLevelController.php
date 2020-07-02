<?php

namespace application\modules\car\controllers;

use common\logic\LogicTrait;
use common\models\CarLevel;
use common\models\ListArray;
use common\util\Common;
use common\util\Json;
use application\controllers\BossBaseController;
/**
 * Site controller
 */
class CarLevelController extends BossBaseController
{

    use LogicTrait;

    /**
     * 优惠券列表
     *
     * @return array
     */
    public function actionIndex()
    {
        $list = CarLevel::find()->asArray()->orderBy('create_time desc')->all();
        $adminIds = array_column($list, 'operator_id');
        $listModel = new ListArray();
        $admins = $listModel->pluckAdminNamesById($adminIds);
        foreach ($list as $key => $item) {
            $list[$key]['adminName'] = $admins[$item['operator_id']] ?? '';
            $list[$key]['status'] = $item['enable']; // 冗余信息
            $list[$key]['ossFileUrl'] = \Yii::$app->params['ossFileUrl'];
        }

        LogicTrait::fillUserInfo($list);

        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    /**
     * 存储/编辑
     * @return array
     */
    public function actionStore()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $status = intval($request->post('status'));
        $label = trim($request->post('label'));
        $levelPic = trim($request->post('levelPic'));

        if (!in_array($status, [0, 1])) {
            return Json::message('参数异常');
        }
        if (empty($levelPic)){
            return Json::message('参数缺少级别图片');
        }
        if ($id) {
            $carLevel = CarLevel::find()->where(['id' => $id])->limit(1)->one();
            if (!$carLevel) {
                return Json::message('参数异常');
            }
        }else{
            $carLevel = new CarLevel();
        }
        $carLevel->label = $label;
        $carLevel->enable = $status;
        $carLevel->icon = $levelPic;

        // 录入用户!!!!!
        $carLevel->operator_id = $this->userInfo['id'];

        if (!$carLevel->save()) {
            return Json::message('操作失败');
        }
        return Json::message('操作成功', 0);
    }

}
