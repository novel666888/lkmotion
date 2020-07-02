<?php

namespace application\modules\car\controllers;

use common\logic\FileUrlTrait;
use common\logic\LogicTrait;
use common\models\CarBrand;
use common\models\CarType;
use common\models\ListArray;
use common\util\Common;
use common\util\Json;
use application\controllers\BossBaseController;
/**
 * Site controller
 */
class CarTypeController extends BossBaseController
{

    use LogicTrait, FileUrlTrait;

    /**
     * 优惠券列表
     *
     * @return array
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $model = trim($request->post('model'));
        $seats = intval($request->post('seats'));
        $query = CarType::find();
        if ($model) {
            $query->where(['model' => $model]);
        }
        if ($seats) {
            $query->where(['seats' => $seats]);
        }
        $resource = CarType::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        $data = $resource['data'];
        $this->patchListUrl($data['list'], ['img_url']);

        LogicTrait::fillUserInfo($data['list']);

        return Json::success(Common::key2lowerCamel($data));
    }


    public function actionStore()
    {
        $request = \Yii::$app->request;

        $id = intval($request->post('id'));
        $brand = trim($request->post('brand'));
        $model = trim($request->post('model'));
        $imgUrl = trim($request->post('imgUrl'));
        $stat = intval($request->post('status'));
        $seats = intval($request->post('seats'));
        if ($seats < 1) {
            $seats = 1;
        }

        // 输入赋值
        if ($id) {
            $carType = CarType::find()->where(['id' => $id])->limit(1)->one();
            if (!$carType) {
                return Json::message('参数异常');
            }
        } else {
            $carType = new CarType();
        }

        // 类型转换
        $listArray = new ListArray();
        $brandMap = $listArray->getBrandMap();
        $brand = $brandMap[$brand] ?? $brand;
        $model = $brandMap[$model] ?? $model;

        $carType->brand = $brand;
        $carType->model = $model;
        $carType->seats = $seats;
        $carType->status = $stat;
        $carType->img_url = $imgUrl;
        $carType->type_desc = implode(' ', [$brand, $model, $seats . '座']);

        // 录入用户!!!!!
        $carType->operator_id = $this->userInfo['id'];


        if (!$carType->save()) {
            return Json::message('操作失败');
        }

        return Json::message('操作成功', 0);
    }

    /**
     * 状态变更
     * @return array
     */
    public function actionPause()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        $status = intval($request->post('status'));

        if (!in_array($status, [0, 1])) {
            return Json::message('参数异常');
        }
        $carType = CarType::find()->where(['id' => $id])->limit(1)->one();
        if (!$carType) {
            return Json::message('参数异常');
        }

        $carType->status = $status;
        // 录入用户!!!!!
        $carType->operator_id = $this->userInfo['id'];
        $carType->save();

        return Json::message('设置成功', 0);

    }


}
