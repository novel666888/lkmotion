<?php
/**
 * ServiceLogic.php
 */

namespace common\logic;

use common\models\DriverInfo;
use yii\helpers\ArrayHelper;
use common\services\YesinCarHttpClient;
use common\models\BaseInfoCompany;
use common\models\BaseInfoCompanyPay;
use common\models\BaseInfoCompanyPermit;
use common\models\BaseInfoCompanyService;


class BaseInfoLogic
{

    /**
     * companyUpdate --
     * @param array $requestData
     * @return array | bool
     * @cache No
     */
    public static function companyUpdate($requestData)
    {
        $companySql = BaseInfoCompany::find()->asArray()->one();
        \Yii::info($companySql, 'companysql');
        if($companySql && empty($requestData['id'])) return array("status"=>1,"errorinfo"=>"修改数据id不能为空，请检查数据id");
        if(empty($requestData['id'])){
            $update = BaseInfoCompany::add($requestData);
            $data['operation'] = "insert";
            $id = $update;
        }else{
            $company = BaseInfoCompany::find()->where(['id'=>$requestData['id']])->asArray()->one();
            if(!$company) return array("status"=>1,"errorinfo"=>"修改数据不存在，请检查数据id是否存在！");

            $update = BaseInfoCompany::edit($requestData['id'],$requestData,true);
            $data['operation'] = "update";
            $id = $requestData['id'];
        }
        \Yii::info($update, 'update');
        \Yii::info($data, 'operation');
        \Yii::info($id, 'id');

        if(!$update) return array("status"=>1,"errorinfo"=>"操作失败！");

        $doReportData =  \Yii::$app->params['doReportData'];
        \Yii::info($doReportData, 'doReportData');

        if($doReportData){
            $baseServer = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
            \Yii::info($baseServer, 'baseServer');
            $basePath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.company')."/".$id;
            \Yii::info($basePath, 'basePath');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$baseServer]);
            $companyData = $httpClient->get($basePath, $data,2);
            \Yii::info($companyData, 'companyData');
        }
        return array("status"=>0,"errorinfo"=>"操作成功！");
    }


    /**
     * companyPayUpdate --
     * @param array $requestData
     * @return array | bool
     * @cache No
     */
    public static function companyPayUpdate($requestData)
    {
        $companySql = BaseInfoCompanyPay::find()->asArray()->one();
        \Yii::info($companySql, 'companysql');
        if($companySql && empty($requestData['id'])) return array("status"=>1,"errorinfo"=>"修改数据id不能为空，请检查数据id");
        if(empty($requestData['id'])){
            $update = BaseInfoCompanyPay::add($requestData);
            $data['operation'] = "insert";
            $id = $update;
        }else{
            $company = BaseInfoCompanyPay::find()->where(['id'=>$requestData['id']])->asArray()->one();
            if(!$company) return array("status"=>1,"errorinfo"=>"修改数据不存在，请检查数据id是否存在！");

            $update = BaseInfoCompanyPay::edit($requestData['id'],$requestData,true);
            $data['operation'] = "update";
            $id = $requestData['id'];
        }
        \Yii::info($update, 'update');
        \Yii::info($data, 'operation');
        \Yii::info($id, 'id');

        if(!$update) return array("status"=>1,"errorinfo"=>"操作失败！");

        $doReportData =  \Yii::$app->params['doReportData'];
        \Yii::info($doReportData, 'doReportData');
        if($doReportData){
            $baseServer = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
            \Yii::info($baseServer, 'baseServer');
            $basePath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.companyPay')."/".$id;
            \Yii::info($basePath, 'basePath');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$baseServer]);
            $companyData = $httpClient->get($basePath, $data,2);
            \Yii::info($companyData, 'companyData');
        }

        return array("status"=>0,"errorinfo"=>"操作成功！");
    }


    /**
     * companyServiceUpdate --
     * @param array $requestData
     * @return array | bool
     * @cache No
     */
    public static function companyServiceUpdate($requestData)
    {
        $companySql = BaseInfoCompanyService::find()->asArray()->one();
        \Yii::info($companySql, 'companysql');
        if($companySql && empty($requestData['id'])) return array("status"=>1,"errorinfo"=>"修改数据id不能为空，请检查数据id");
        if(empty($requestData['id'])){
            $update = BaseInfoCompanyService::add($requestData);
            $data['operation'] = "insert";
            $id = $update;
        }else{
            $company = BaseInfoCompanyService::find()->where(['id'=>$requestData['id']])->asArray()->one();
            if(!$company) return array("status"=>1,"errorinfo"=>"修改数据不存在，请检查数据id是否存在！");

            $update = BaseInfoCompanyService::edit($requestData['id'],$requestData,true);
            $data['operation'] = "update";
            $id = $requestData['id'];
        }
        \Yii::info($update, 'update');
        \Yii::info($data, 'operation');
        \Yii::info($id, 'id');

        if(!$update) return array("status"=>1,"errorinfo"=>"操作失败！");

        $doReportData =  \Yii::$app->params['doReportData'];
        \Yii::info($doReportData, 'doReportData');
        if($doReportData){
            $baseServer = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
            \Yii::info($baseServer, 'baseServer');
            $basePath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.companyService')."/".$id;
            \Yii::info($basePath, 'basePath');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$baseServer]);
            $companyData = $httpClient->get($basePath, $data,2);
            \Yii::info($companyData, 'companyData');
        }
        return array("status"=>0,"errorinfo"=>"操作成功！");
    }


    /**
     * companyPermitUpdate --
     * @param array $requestData
     * @return array | bool
     * @cache No
     */
    public static function companyPermitUpdate($requestData)
    {
        $companySql = BaseInfoCompanyPermit::find()->asArray()->one();
        \Yii::info($companySql, 'companysql');
        if($companySql && empty($requestData['id'])) return array("status"=>1,"errorinfo"=>"修改数据id不能为空，请检查数据id");
        if(empty($requestData['id'])){
            $update = BaseInfoCompanyPermit::add($requestData);
            $data['operation'] = "insert";
            $id = $update;
        }else{
            $company = BaseInfoCompanyPermit::find()->where(['id'=>$requestData['id']])->asArray()->one();
            if(!$company) return array("status"=>1,"errorinfo"=>"修改数据不存在，请检查数据id是否存在！");

            $update = BaseInfoCompanyPermit::edit($requestData['id'],$requestData,true);
            $data['operation'] = "update";
            $id = $requestData['id'];
        }
        \Yii::info($update, 'update');
        \Yii::info($data, 'operation');
        \Yii::info($id, 'id');

        if(!$update) return array("status"=>1,"errorinfo"=>"操作失败！");

        $doReportData =  \Yii::$app->params['doReportData'];
        \Yii::info($doReportData, 'doReportData');
        if($doReportData){
            $baseServer = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
            \Yii::info($baseServer, 'baseServer');
            $basePath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.companyPermit')."/".$id;
            \Yii::info($basePath, 'basePath');
            $httpClient = new YesinCarHttpClient(['serverURI'=>$baseServer]);
            $companyData = $httpClient->get($basePath, $data,2);
            \Yii::info($companyData, 'companyData');
        }
        return array("status"=>0,"errorinfo"=>"操作成功！");
    }

    /**
     * companyPermitUpdate --
     * @return array | bool
     * @cache No
     */
    public static function companyInfo()
    {
        $data['company'] = BaseInfoCompany::find()->asArray()->one();
        $data['companyPay'] = BaseInfoCompanyPay::find()->asArray()->one();
        $data['companyService'] = BaseInfoCompanyService::find()->asArray()->one();
        $data['companyPermit'] = BaseInfoCompanyPermit::find()->asArray()->one();
        return $data;
    }

    /**
     * 根据司机ID获取车辆ID
     * @param $driverId
     * @return int
     */
    public static function getDriverCarId($driverId)
    {
        $driverId = intval($driverId);
        $hashTbl = 'driverId2CarIdMap';
        $redis = \Yii::$app->redis;
        $carId = $redis->hget($hashTbl, $driverId);
        if ($carId) {
            return intval($carId);
        }
        $carId = intval(DriverInfo::find()->where(['id' => $driverId])->select('car_id')->scalar());
        $redis->hset($hashTbl, $driverId, $carId);
        return $carId;
    }

    /**
     * 设置司机的车辆ID
     * @param $driverId
     * @param $carId
     * @return mixed
     */
    public static function setDriverCarId($driverId, $carId)
    {
        $hashTbl = 'driverId2CarIdMap';
        $redis = \Yii::$app->redis;
        return $redis->hset($hashTbl, intval($driverId), intval($carId));
    }

}