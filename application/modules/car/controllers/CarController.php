<?php

namespace application\modules\car\controllers;

use application\models\Car;
use common\logic\FileUrlTrait;
use common\models\CarBaseInfo;
use common\models\CarBindChangeRecord;
use common\models\CarInfo;
use common\models\CarType;
use common\models\Decrypt;
use common\models\DriverInfo;
use common\models\ListArray;
use common\util\Common;
use common\util\Json;
use PHPExcel;
use PHPExcel_IOFactory;
use application\controllers\BossBaseController;

/**
 * Site controller
 */
class CarController extends BossBaseController
{

    use FileUrlTrait;

    /**
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $search = trim($request->post('search', ''));
        $cityCode = trim($request->post('cityCode', ''));//城市code
        $useStatus = trim($request->post('useStatus', ''));//运营状态
        $startTime = trim($request->post('startTime', ''));
        $endTime = trim($request->post('endTime', ''));

        if ($search !== '') {
            $query = DriverInfo::find();
            $encryptPhone = Decrypt::encryptPhone($search);
            $query->FilterWhere(['phone_number' => $encryptPhone]);
            $query->orFilterWhere(['driver_name' => $search]);
            $result = $query->select('car_id')->asArray()->all();
            if (!count($result)) {
                $carIds = [];
            } else {
                $carIds = array_column($result, 'car_id');
            }
        } else {
            $carIds = false;
        }

        $sql=[];
        if($search !== '') {
            if (!empty($carIds)) {
                $carIds = implode(",", $carIds);
                $sql[] = "(ci.id in ($carIds) OR ci.plate_number='$search' OR ci.asset_coding='$search' OR cbi.vin_number='$search')";
            }else{
                $sql[] = "(ci.plate_number='$search' OR ci.asset_coding='$search' OR cbi.vin_number='$search')";
            }
        }
        if(!empty($cityCode)){
            $sql[] = "ci.city_code = '$cityCode'";
        }
        if($useStatus!==''){
            $sql[] = "ci.use_status = $useStatus";
        }
        if(!empty($startTime)){
            $startTime = date('Y-m-d', strtotime($startTime));
            $sql[] = "ci.create_time >= '$startTime'";
        }
        if(!empty($endTime)){
            $endTime = date('Y-m-d', strtotime($endTime) + 86400);
            $sql[] = "ci.create_time < '$endTime'";
        }
        if(!empty($sql)){
            $sql = implode(" AND ", $sql);
            $sql = "SELECT ci.id as id FROM tbl_car_info AS ci INNER JOIN tbl_car_base_info AS cbi ON cbi.id = ci.id where $sql";
            $rs = \Yii::$app->db->createCommand($sql)->queryAll();
            $rs = array_column($rs, 'id');
            if(!empty($rs)){
                $query = CarInfo::find()->where(["id"=>$rs]);
            }else{
                //查询数据为空
                return Json::success([
                    'list' => [],
                    'pageInfo' => [
                        'page' => 0,
                        'pageCount' => 0,
                        'pageSize' => 0,
                        'total' => 0
                    ]
                ]);
            }
        }else{
            $query = CarInfo::find();
        }
        $pageList = CarInfo::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        // patch司机信息
        $list = $pageList['data']['list'];
        $carIds = array_column($list, 'id');
        $driversInfo = DriverInfo::find()->where(['car_id' => $carIds])
            ->select('id,car_id,driver_name')
            ->asArray()->all();
        if (count($driversInfo)) {
            $driverIds = array_column($driversInfo, 'id');
            $listArray = new ListArray();
            $driverPhones = $listArray->getDriverPhoneNumberByIds($driverIds);
            $phoneMap = array_column($driversInfo, 'id', 'car_id');
            foreach ($phoneMap as $key => $item) {
                $phoneMap[$key] = $driverPhones[$item];
            }
            $driverIdMap = array_column($driversInfo, 'id', 'car_id');
            $nameMap = array_column($driversInfo, 'driver_name', 'car_id');
        } else {
            $driverIdMap = $phoneMap = $nameMap = [];
        }
        // 获取车辆级别ID
        $listModel = new ListArray();
        $result = $listModel->getCarLevel(0);
        $levelMap = array_column($result, 'label', 'id');
        foreach ($list as &$item) {
            $item['driver_id'] = $driverIdMap[$item['id']] ?? '';
            $item['driver_name'] = $nameMap[$item['id']] ?? '';
            $item['phone_number'] = $phoneMap[$item['id']] ?? '';
            $item['level_text'] = $levelMap[$item['car_level_id']] ?? '';
        }

        // 获取车辆vin
        $CarBaseInfo = new CarBaseInfo();
        $CarBaseInfo = $CarBaseInfo->find()->where(['id'=>$carIds])->select(['id','vin_number'])->indexBy('id')->asArray()->all();
        if(!empty($CarBaseInfo)){
            foreach ($list as $kk => $vv){
                if(isset($CarBaseInfo[$vv['id']])){
                    $list[$kk]['vin_number'] = $CarBaseInfo[$vv['id']]['vin_number'];
                }else{
                    $list[$kk]['vin_number'] = '';
                }
            }
        }
        $pageList['data']['list'] = $list;
        return Json::data(Common::key2lowerCamel($pageList));
    }

    /**
     * 车辆录入/编辑
     * @return array|\yii\web\Response
     */
    public function actionStore()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));

        if ($id) {
            $oldPrams = CarInfo::find()->select($this->getBindParams())->where(['id' => $id])->asArray()->one();
            if (!$oldPrams) {
                return Json::message('参数错误');
            }
        } else {
            $oldPrams = [];
        }

        $reqParams = Car::compactCarInfo($request->post());
        // 获取车辆描述
        $carTypeId = intval($request->post('carTypeId'));
        $typeInfo = CarType::findOne(['id' => $carTypeId]);
        $reqParams['data']['carInfo']['fullName'] = $typeInfo ? $typeInfo->type_desc : '';
        // 请求接口
        if ($id) {
            \Yii::info($reqParams, "updateCar");
            $responseData = Car::updateCar($reqParams);
        } else {
            \Yii::info($reqParams, "storeCar");
            $responseData = Car::storeCar($reqParams);
        }
        // 返回操作结果
        if (is_string($responseData)) {
            return Json::message($responseData);
        }
        // 检测绑定信息
        if ($responseData['code'] == 0) {
            if (!$id) {
                $id = $responseData['data']['id'] ?? 0;
            }
            $newParams = CarInfo::find()->select($this->getBindParams())->where(['id' => $id])->asArray()->one();
            Car::recordChange($id, $oldPrams, $newParams);
        }
        // 返回结果
        return $this->asJson($responseData);
    }

    /**
     * 修改状态
     * @return array|\yii\web\Response
     */
    public function actionChangeStatus()
    {
        $request = \Yii::$app->request;
        $carId = intval($request->post('carId'));
        if (!$carId) {
            return Json::message('参数异常');
        }
        $carInfo = CarInfo::findOne(['id' => $carId]);
        if (!$carInfo) {
            return Json::message('参数异常');
        }
        $status = intval($request->post('useStatus'));

        $result = Car::changeStatus(['id' => $carId, 'useStatus' => $status]);
        if (is_string($result)) {
            return Json::message($result);
        }
        return $this->asJson($result);
    }

    public function actionBindRecords()
    {
        $request = \Yii::$app->request;
        $carId = intval($request->get('carId'));
        if (!$carId) {
            $carId = intval($request->post('carId'));
        }
        $listArray = new ListArray();
        $bindTags = $listArray->getSysConfig('car_bind_type');
        if (!$bindTags) {
            return Json::message('配置异常!');
        }
        $config = json_decode($bindTags, 1);
        //var_dump($config);exit;
        $queryTags = array_column($config, 'key');

        $changeLogs = CarBindChangeRecord::find()->where(['car_info_id' => $carId])->andWhere(['bind_tag' => $queryTags])->asArray()->all();
        // 分组数据
        $splitLogs = [];
        if ($changeLogs) {
            $adminIds = array_column($changeLogs, 'operator_id');
            $adminInfo = $listArray->pluckAdminNamesById($adminIds);
            foreach ($changeLogs as $log) {
                $log['adminName'] = $adminInfo[$log['operator_id']] ?? '';
                $log['bind_value'] = json_decode($log['bind_value']); // 参数解析
                $splitLogs[$log['bind_tag']][] = $log;
            }
        }
        //var_dump($splitLogs);exit;
        return Json::success(Common::key2lowerCamel($splitLogs));
    }

    /**
     * @param bool $carId
     * @return array
     */
    public function actionDetails($carId = false)
    {
        $request = \Yii::$app->request;
        if (!$carId) {
            $carId = $request->post('carId');
        }
        $carInfo = CarInfo::find()->where(['id' => $carId])->limit(1)->asArray()->one();
        if (!$carInfo) {
            return Json::message('参数异常');
        }
        $carBaseInfo = CarBaseInfo::find()->where(['id' => $carId])->limit(1)->asArray()->one();
        if ($carBaseInfo) {
            unset($carBaseInfo['id']);
            $carInfo = array_merge($carInfo, $carBaseInfo);
        }
        //$this->patchUrl($carInfo, Car::getImgKeys());
        $carInfo['oss_file_url'] = \Yii::$app->params['ossFileUrl'] ?? "";
        return Json::success(Common::key2lowerCamel($carInfo));
    }

    /**
     * 导出车辆列表excel
     */
    public function actionExport(){
        $request = \Yii::$app->request;
        $carId = $request->post('carId', []);
        //$carId = [1,2];
        if(empty($carId)){
            return Json::message('Parameters are empty');
        }

        $carInfo = CarInfo::find()
            ->select(['id','plate_number','publish_time','full_name','color','car_img','city_code','car_type_id','car_level_id',
                'regist_date','insurance_start_date','insurance_end_date','annual_end_date','car_license_img','is_free_order',
                'remark','use_status','large_screen_device_code','large_screen_device_brand','car_screen_device_code','car_screen_device_brand',
                'total_mile','asset_coding'])
            ->where(['id' => $carId])->asArray()->all();
        if (!$carInfo) {
            return Json::message('Excel is empty');
        }

        $carBaseInfo = CarBaseInfo::find()->select(['id','company_logo','car_label','car_base_type','car_owner','plate_color','engine_number',
            'car_brain_plate','car_brain_number','vin_number','register_time','fuel_type','engine_capacity','car_img_file_number',
            'transport_number','transport_issuing_authority','business_area','transport_certificate_validity_start','transport_certificate_validity_end',
            'first_register_time','state_of_repair','next_annual_inspection_time','annual_audit_status','invoice_printing_equipment_number',
            'gps_brand','gps_model','gps_imei','gps_install_time','report_time','service_type','charge_type_code','car_invoice_img',
            'quality_certificate_img','vehicle_license_img','registration_certificate_img','tax_payment_certificate_img','transport_certificate_img',
            'other_img1','other_img2','create_time'])->where(['id' => $carId])->indexBy('id')->asArray()->all();
        foreach($carInfo as $k => $v){
            if(isset($carBaseInfo[$v['id']])){
                unset($carBaseInfo[$v['id']]['id']);
                $carInfo[$k] = array_merge($carInfo[$k], $carBaseInfo[$v['id']]);
            }
        }
        if(!empty($carInfo)){
            foreach($carInfo as $k => $v){
                $this->patchUrl($carInfo[$k], Car::getImgKeys());
            }
        }

        $driversInfo = DriverInfo::find()->where(['car_id' => $carId])
            ->select('id,car_id,driver_name')
            ->asArray()->all();
        if (count($driversInfo)) {
            $driverIds = array_column($driversInfo, 'id');
            $listArray = new ListArray();
            $driverPhones = $listArray->getDriverPhoneNumberByIds($driverIds);
            $phoneMap = array_column($driversInfo, 'id', 'car_id');
            foreach ($phoneMap as $key => $item) {
                $phoneMap[$key] = $driverPhones[$item];
            }
            $driverIdMap = array_column($driversInfo, 'id', 'car_id');
            $nameMap = array_column($driversInfo, 'driver_name', 'car_id');
        } else {
            $driverIdMap = $phoneMap = $nameMap = [];
        }

        // 获取车辆级别ID
        $listModel = new ListArray();
        $result = $listModel->getCarLevel(0);
        $levelMap = array_column($result, 'label', 'id');
        foreach ($carInfo as &$item) {
            $item['driver_id']    = $driverIdMap[$item['id']] ?? '';
            $item['driver_name']  = $nameMap[$item['id']] ?? '';
            $item['phone_number'] = $phoneMap[$item['id']] ?? '';
            $item['car_level_id'] = $levelMap[$item['car_level_id']] ?? '';
        }
        //echo "<pre>";
        //print_r($carInfo);
        //exit;
        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $objectPHPExcel = new PHPExcel();
        //设置表格头的输出
        $en = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM'];

        $zh = ['车辆id','车牌号','上架时间','车辆全名','车身颜色','汽车图片','城市','车辆类型','车辆级别','上牌日期','保险生效日期','保险失效日期',
            '年检到期日期','行驶本图片地址','是否开启顺风单','备注','车辆运营状态','大屏编号','大屏品牌名称','车机/脑设备号','车机/脑品牌名称','行驶总里程单位：km', '资产编码',
            '公司标识','车辆厂牌','车辆类型','车辆所有人','车牌颜色','发动机号电动机号','车脑品牌','车脑编号','vin码','注册日期','燃料类型','发动机排量',
            '车辆照片文件编号','运输证字号','车辆运输证发证机构','经营区域','车辆运输证有效期起','车辆运输证有效期止','车辆初次登记日期','车辆检修状态','下次年检时间',
            '年度审核状态','发票打印设备序列号','卫星定位装置品牌','型号','imei','安装日期','报备日期','服务类型','运价类型编码','车辆发票','合格证',
            '行驶证','登记证书','完税证明','汽车运输证','其他一','其他二','创建时间','司机ID','司机姓名','司机手机号',];
        $max=count($en);
        for($i=0;$i<$max;$i++){
            $objectPHPExcel->setActiveSheetIndex()->setCellValue($en[$i].'1', $zh[$i]);
        }

        if(!empty($carInfo)){
            $n=2;
            foreach ($carInfo as $v){
                $ii=0;
                foreach($v as $kk => $vv){
                    $objectPHPExcel->getActiveSheet()->setCellValue($en[$ii].($n) ,$vv);
                    $ii++;
                }
                $n++;
            }
        }

        ob_end_clean();
        ob_start();
        header('Content-Type:application/vnd.ms-excel');//!!!不能有空格
        //设置输出文件名及格式
        header('Content-Disposition:attachment;filename="车辆信息列表'.date("YmdHis").'.xls"');
        //导出.xls格式的话使用Excel5,若是想导出.xlsx需要使用Excel2007
        $objWriter= PHPExcel_IOFactory::createWriter($objectPHPExcel,'Excel5');
        $objWriter->save('php://output');
        ob_end_flush();
        //清空数据缓存
        unset($carInfo);
    }


    private function getBindParams()
    {
        return [
            'plate_number',
            'large_screen_device_code',
            'large_screen_device_brand',
            'car_screen_device_code',
            'car_screen_device_brand',
        ];
    }

}
