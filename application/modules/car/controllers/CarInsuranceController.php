<?php

namespace application\modules\car\controllers;

use application\models\Car;
use common\util\Common;
use common\util\Json;
use common\models\CarInsurance;
use application\controllers\BossBaseController;
use common\models\CarInfo;
use yii\helpers\ArrayHelper;
/**
 * Site controller
 */
class CarInsuranceController extends BossBaseController
{
    public function actionIndex(){}


    public function actionPlateNumberList(){
        $CarInfo = CarInfo::find()->select(['plate_number'])
            ->where(['!=', 'plate_number', ''])
            ->asArray()->all();
        $date = date("Y-m-d", time());
        $CarInsurance = CarInsurance::find()->select(['plate_number'])
            ->where(['<', 'insurance_exp', $date])
            ->indexBy('plate_number')->asArray()->all();
        if(!empty($CarInfo)){
            $plate_number = ArrayHelper::getColumn($CarInfo, 'plate_number');
            if(!empty($CarInsurance)){
                foreach($plate_number as $k => $v){
                    if(isset($CarInsurance[$v])){
                        unset($plate_number[$k]);
                    }
                }
            }
            $plate_number = array_values($plate_number);
            return Json::success($plate_number);
        }
        else{
            return Json::success([]);
        }
    }

    /**
     * 车辆管理详情
     */
    public function actionDetail(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'CarInsurance Detail');
        $id             = isset($postData['id'])  ? trim($postData['id'])  : "";
        if(empty($id)){
            return Json::message("Id 为空");
        }
        $data = CarInsurance::getDetail($id);
        return Json::success(Common::key2lowerCamel($data));
    }

    /**
     * 车辆保险列表
     */
    public function actionList(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'CarInsurance list');
        $requestData['plateNumber']  = isset($postData['plateNumber'])  ? trim($postData['plateNumber'])  : "";
        $requestData['startTime']     = isset($postData['startTime'])  ? trim($postData['startTime'])  : "";
        $requestData['endTime']       = isset($postData['endTime'])  ? trim($postData['endTime'])  : "";
        $data = CarInsurance::getList($requestData);
        return Json::success(Common::key2lowerCamel($data));
    }

    /**
     * 车辆保险添加/修改
     */
    public function actionAdd(){
        $request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'CarInsurance add');
        $id                                  = isset($postData['id'])  ? trim($postData['id'])  : "";
        $requestData['company_id']           = isset($postData['companyId'])  ? trim($postData['companyId'])  : "";
        $requestData['plate_number']         = isset($postData['plateNumber'])  ? trim($postData['plateNumber'])  : "";
        $requestData['insurance_company']    = isset($postData['insuranceCompany'])  ? trim($postData['insuranceCompany'])  : "";
        $requestData['insurance_number']     = isset($postData['insuranceNumber'])  ? trim($postData['insuranceNumber'])  : "";
        $requestData['insurance_type']       = isset($postData['insuranceType'])  ? trim($postData['insuranceType'])  : "";
        $requestData['insurance_count']      = isset($postData['insuranceCount'])  ? trim($postData['insuranceCount'])  : "";
        $requestData['insurance_eff']        = isset($postData['insuranceEff'])  ? trim($postData['insuranceEff'])  : "";
        $requestData['insurance_exp']        = isset($postData['insuranceExp'])  ? trim($postData['insuranceExp'])  : "";
        $requestData['insurance_photo']      = isset($postData['insurancePhoto'])  ? ($postData['insurancePhoto'])  : "";
        $requestData['other_photo']          = isset($postData['otherPhoto'])  ? ($postData['otherPhoto'])  : "";
        $requestData['other_photo_2']        = isset($postData['otherPhoto2']) ? ($postData['otherPhoto2'])  : "";

        if(empty($requestData['company_id'])){
            return Json::message("companyId 为空");
        }
        if(empty($requestData['plate_number'])){
            return Json::message("plateNumber 为空");
        }
        if(empty($requestData['insurance_company'])){
            return Json::message("insuranceCompany 为空");
        }
        if(empty($requestData['insurance_number'])){
            return Json::message("insuranceNumber 为空");
        }
        if(empty($requestData['insurance_type'])){
            return Json::message("insuranceType 为空");
        }
        if(empty($requestData['insurance_count'])){
            return Json::message("insuranceCount 为空");
        }
        if(empty($requestData['insurance_eff'])){
            return Json::message("insuranceEff 为空");
        }
        if(empty($requestData['insurance_exp'])){
            return Json::message("insuranceExp 为空");
        }
        if(empty($requestData['insurance_photo'])){
            return Json::message("insurancePhoto 为空");
        }

        //$requestData['insurance_photo'] = implode(",", $requestData['insurance_photo']);
        //$requestData['other_photo']     = implode(",", $requestData['other_photo']);
        //$requestData['other_photo_2']   = implode(",", $requestData['other_photo_2']);

        //$this->userInfo['id']=100;
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['operator_id'] = $this->userInfo['id'];
        }

        if(!empty($id)){
            $rs = CarInsurance::edit(['id'=>$id], $requestData);
        }else{
            //验证参险
            $checkIfInsurance = CarInsurance::checkIfInsurance($requestData['plate_number']);
            if($checkIfInsurance){
                return Json::message("该车牌已拥有保险");
            }
            $rs = CarInsurance::add($requestData);
        }
        if($rs['code']==0){
            return Json::success();
        }else{
            return Json::message($rs['message']);
        }

    }




}
