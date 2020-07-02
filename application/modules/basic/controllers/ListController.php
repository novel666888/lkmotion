<?php

namespace application\modules\basic\controllers;

use application\controllers\BossBaseController;
use application\models\Car;
use application\models\Driver;
use application\models\Excel;
use application\models\Insurance;
use common\models\CarBrand;
use common\models\CarInfo;
use common\models\DriverInfo;
use common\models\ListArray;
use common\models\RechargePrice;
use common\util\Common;
use common\util\Json;
use yii\web\UploadedFile;

/**
 * Site controller
 */
class ListController extends BossBaseController
{

    /**
     * 城市列表
     *
     * @return array
     */
    public function actionCity()
    {
        $listModel = new ListArray();
        $list = $listModel->getCityList();

        return Json::success(['list' => array_column($list, 'city_name', 'city_code')]);
    }

    /**
     * 优惠券类型列表
     * @return array
     */
    public function actionCouponClasses()
    {
        $listModel = new ListArray();
        $list = $listModel->getCouponClasses();

        return Json::success(compact('list'));
    }

    /**
     * 车型列表
     * @return array
     */
    public function actionCarType()
    {
        $listModel = new ListArray();
        $list = $listModel->getTypeList();

        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    public function actionBrandModel()
    {
        $brand = CarBrand::find()->select('id,brand')->where('pid = 0')->indexBy('id')->asArray()->all();
        $model = CarBrand::find()->select('id,pid,model,seats')->where('pid > 0')->asArray()->all();
        foreach ($model as $item) {
            $brand[$item['pid']]['models'][] = $item;
        }
        return Json::success(['list' => array_values($brand)]);
    }

    public function actionCarColors()
    {
        $listModel = new ListArray();
        $list = $listModel->getSysConfig('car_color');
        $list = json_decode($list);
        return Json::success(compact('list'));
    }

    /**
     * 车辆级别列表
     * @return array
     */
    public function actionCarLevel()
    {
        $listModel = new ListArray();
        $list = $listModel->getCarLevel();

        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    /**
     * 人群列表
     * @return array
     */
    public function actionPeopleTags()
    {
        $listModel = new ListArray();
        $list = $listModel->getPeopleTags();

        return Json::success(compact('list'));
    }

    /**
     * 通用详情接口
     * @return array
     */
    public function actionDetails()
    {
        $request = \Yii::$app->request;
        $model = ucfirst(trim(strval($request->get('model'))));
        if (!$model) {
            $model = ucfirst(trim(strval($request->post('model'))));
        }
        $id = $this->getId('id');
        if ($id < 1) {
            return Json::message('参数ID不正确1');
        }
        $listModel = new ListArray();
        $details = $listModel->getDetails($model, $id);
        if (is_string($details)) {
            return Json::message($details);
        }
        return Json::success(Common::key2lowerCamel($details));
    }

    public function actionUnbindCars()
    {
        $request = \Yii::$app->request;
        $cityCode = trim($request->get('cityCode'));
        if (!$cityCode) {
            $cityCode = trim($request->post('cityCode'));
        }
        $carId = $this->getId('carId');
        $listModel = new ListArray();
        $list = $listModel->getUnbindCars($cityCode, $carId);

        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    //获取搜索人群
    public function actionGetPeople()
    {
        $request = \Yii::$app->request;
        $showType = $request->post('showType');
        if (empty($showType)) {
            return Json::message('请传递展示端参数');
        }
        $ListArr = new ListArray();
        $peopleList = $ListArr->getPeopleTagList($showType);
        return Json::success(['list' => Common::key2lowerCamel($peopleList)]);
    }

    //搜索消息文案模板
    public function actionGetAppTemplate()
    {
        $request = \Yii::$app->request;
        $templateType = $request->post('templateType');
        if (empty($templateType)) {
            return Json::message('请传递消息类型参数');
        }
        $ListArr = new ListArray();
        $appTemplateList = $ListArr->getAppTemplateList($templateType);
        return Json::success(['list' => Common::key2lowerCamel($appTemplateList)]);
    }

    //取短信模板列表（华信）
    public function actionGetMsgTemplate()
    {
        $listModel = new ListArray();
        $smsTemplateList = $listModel->getSmsTemplateList();
        return Json::success(['list' => Common::key2lowerCamel($smsTemplateList)]);
    }

    //取短信模板列表（阿里）
    public function actionGetAliMsgTemplate()
    {
        $listModel = new ListArray();
        $smsTemplateList = $listModel->getAliSmsTemplateList();
        return Json::success(['list' => Common::key2lowerCamel($smsTemplateList)]);
    }

    //搜索广告位列表
    public function actionGetAdPosition()
    {
        $request = \Yii::$app->request;
        $positionType = $request->post('positionType');
        if (empty($positionType)) {
            return Json::message('请传递广告位类型参数');
        }
        $ListArr = new ListArray();
        $adPositionList = $ListArr->getAdPosition($positionType);
        return Json::success(['list' => Common::key2lowerCamel($adPositionList)]);
    }

    /**
     * 获取司机当前绑定车辆
     * @return array
     */
    public function actionCurrentCar()
    {
        $driverId = $this->getId('driverId');
        $driverInfo = DriverInfo::findOne(['id' => $driverId]);
        if (!$driverInfo) {
            return Json::message('司机不存在');
        }
        $data = [
            'cityCode' => $driverInfo->city_code,
            'driverName' => $driverInfo->driver_name,
            'carId' => '',
            'plateNumber' => '',
        ];
        if ($driverInfo->car_id) {
            $carInfo = CarInfo::findOne(['id' => $driverInfo->car_id]);
            if ($carInfo) {
                $data['carId'] = $carInfo->id;
                $data['plateNumber'] = $carInfo->plate_number;
            }
        }
        return Json::success($data);
    }

    /**
     * 获取可用优惠券
     * @return array
     */
    public function actionActiveCoupons()
    {
        $coupons = (new ListArray())->getActiveCoupons();
        return Json::success(['list' => Common::key2lowerCamel($coupons)]);
    }

    public function actionStorePhotoInfo()
    {
        $request = \Yii::$app->request;
        $data = trim($request->post('data'));
        if (!$data) {
            return Json::message('数据体为空');
        }
        $photoInfo = json_decode($data);
        if (!$photoInfo) {
            return Json::message('数据体JSON解析异常');
        }
        $type = $this->getId('type');
        if (!in_array($type, [1, 2, 3])) {
            return Json::message('类型不正确: 1车辆图片2司机图片3车辆保险图片');
        }
        if ($type == 1) {
            //return $this->asJson($data);
            $result = Car::storePhotoInfo($photoInfo);
        } elseif ($type == 2) {
            $result = Driver::storePhotoInfo($photoInfo);
        } elseif ($type == 3) {
            $result = Insurance::storePhotoInfo($photoInfo);
        } else {
            $result = '如果能显示这个提示, 说明程序有bug';
        }
        return Json::message($result, 0);
    }

    public function actionImport()
    {
        if (\Yii::$app->request->isGet) {
            //return Json::message('method get is not allowed here');
            return '<form enctype="multipart/form-data" method="post"><input type="file" name="file">
                    <button type="submit">提交</button>
                    <input type="text" name="extData" value=\'{"type":2}\'>
                    </form>';
        }
        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            return Json::message('上传文件不能为空');
        }
        $allowType = ['xls', 'xlsx', 'cvs'];
        if (!in_array($file->getExtension(), $allowType)) {
            return Json::message("只支持" . implode(',', $allowType) . "文件类型");
        }
        $result = (new Excel())->importData($this->userInfo['id']);
        return Json::message($result, 0);
    }

    /**
     * @return array
     */
    public function actionChargeTag()
    {
        $list = RechargePrice::find()->select('amount')->where(['is_deleted' => 0])->all();
        return Json::success(['list' => $list]);
    }

    /**
     * @return array
     */
    public function actionServiceType()
    {
        $list = (new ListArray())->getServiceType(false);
        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    private function getId($key)
    {
        $request = \Yii::$app->request;
        $value = intval($request->get($key));
        if (!$value) {
            $value = intval($request->post($key));
        }
        return $value;
    }

}
