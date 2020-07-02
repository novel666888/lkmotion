<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/15
 * Time: 13:49
 */

namespace application\models;


use common\models\CarInsurance;
use common\models\CarType;
use common\models\City;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use yii\web\UploadedFile;

class Excel
{
    public function importData($operatorId = 0)
    {
        $file = UploadedFile::getInstanceByName('file');
        if ($file->extension == 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }
        $spreadsheet = $reader->load($file->tempName);
        $data = $spreadsheet->getActiveSheet()->toArray();
        $data = array_filter($data);
        if (!$data) {
            return '文件内容为空';
        }
        $extData = (\Yii::$app->request->post('extData'));
        //$extData = '{"type":4}'; // 手工指定类型,用于测试
        if (!$extData) {
            return 'extData不能为空';
        }
        $extData = json_decode($extData);
        if (!isset($extData->type)) {
            return 'extData解析异常，格式应该为{"type":1}';
        }
        $type = $extData->type;
        if ($type == 1) {
            return $this->processCarData($data);
        } elseif ($type == 2) {
            return $this->processDriverData($data);
        } elseif ($type == 3) {
            return $this->processCarType($data);
        } elseif ($type == 4) {
            return $this->processCarInsurance($data, $operatorId);
        }
        return '未知type类型';
    }

    //车辆保险
    private function processCarInsurance($data, $operatorId)
    {
        array_shift($data); // 去掉第一行，字段题注
        $header = array_shift($data); // 提取头部
        $success = $failed = 0;
        $info = "";
        foreach ($data as $item) {
            $tmp = array_combine($header, $item);
            // 过滤空格
            foreach ($tmp as &$val) $val = trim($val);
            if (!empty($tmp['plate_number'])) {
                $result = CarInsurance::find()->select(['id'])->where(['plate_number' => $tmp['plate_number']])->one();
                if ($result) {
                    $failed++;
                    $info .= "车牌号重复:{$tmp['plate_number']}，";
                    continue;
                }
            }
            $tmp['operator_id'] = $operatorId ? $operatorId : 0;
            $carType = new CarInsurance();
            $carType->setAttributes($tmp);
            $carType->validate();
            if ($carType->save()) {
                $success++;
            } else {
                $failed++;
                $error = $carType->getFirstError();
                $info .= "车牌号:{$tmp['plate_number']}（{$error}），";
            }
        }
        return "导入成功{$success}条,失败{$failed}条,错误信息：{$info}";
    }

    // 车辆类型
    private function processCarType($data)
    {
        $header = array_shift($data); // 提取头部
        array_shift($data); // 去掉第一行
        $brands = array_column($data, 0);
        $brandBuffer = [];
        foreach ($brands as $brand) {
            $brand = trim($brand);
            if (!$brand) continue;
            $brandBuffer[$brand]['brand'] = $brand;
        }
        foreach ($data as $item) {
            $tmp = array_combine($header, $item);
            // 过滤空格
            foreach ($tmp as &$val) $val = trim($val);
            $tmp['type_desc'] = $tmp['brand'] . ' ' . $tmp['model'] . '' . $tmp['seats'];
            // 处理品牌型号
            if (!isset($brandBuffer[$tmp['brand']][$tmp['model']])) {
                $brandBuffer[$tmp['brand']][$tmp['model']] = $tmp['model'];
            }
            $carType = new CarType();
            $carType->setAttributes($tmp);
            $carType->validate();
            $carType->save();
        }
        return "导入成功";
    }

    // 车辆信息
    private function processCarData($data)
    {
        $header = array_shift($data); // 提取头部
        array_shift($data); // 去掉第一行
        array_shift($data); // 去掉第一行
        $eof = 0;
        $carTypeMap = CarType::find()->select('id,type_desc')->asArray()->all();
        $carTypeMap = array_column($carTypeMap, 'type_desc', 'id');
        $success = $filed = 0;
        $filedPlate = [];
        foreach ($data as $item) {
            // 数据监测
            $firstCell = trim($item[0]);
            if (!$firstCell) {
                $eof++;
                if ($eof >= 2) break; // 连续读取到两个空行, 结束读取
            } else {
                $eof = 0;
            } // 清除标记

            $tmp = array_combine($header, $item);
            $tmp['fullName'] = $carTypeMap[$tmp['carTypeId']] ?? '';
            // 过滤空格
            foreach ($tmp as &$val) $val = trim($val);
            // 存储车辆信息
            $reqData = Car::compactCarInfo($tmp);
            $result = Car::storeCar($reqData);
            if (is_array($result) && $result['code'] == 0) {
                $success++;
            } else {
                $filedPlate[] = $tmp['plateNumber'];
                $filed++;
            }
        }
        $msg = "导入成功{$success}条,失败{$filed}条";
        if (count($filedPlate)) {
            $msg .= ',失败的车牌号' . implode(',', $filedPlate);
        }
        return $msg;
    }

    // 司机信息
    private function processDriverData($data)
    {
        $header = array_shift($data); // 提取头部
        array_shift($data); // 去掉一行
        array_shift($data); // 去掉一行
        $eof = 0;
        $success = $filed = 0;
        $filedPhone = [];
        $cityList = City::find()->select('city_code,city_name')->asArray()->all();
        $cityMap = array_column($cityList, 'city_code', 'city_name');
        $cityCodes = array_column($cityList, 'city_code');
        foreach ($data as $item) {
            // 数据监测
            $firstCell = trim($item[0]);
            if (!$firstCell) {
                $eof++;
                if ($eof >= 2) break; // 连续读取到两个空行, 结束读取
            } else {
                $eof = 0;
            } // 清除标记
            $tmp = array_combine($header, $item);
            // 过滤空格
            foreach ($tmp as &$val) $val = trim($val);
            // cityCode格式化
            if (!in_array($tmp['cityCode'], $cityCodes)) {
                $tmp['cityCode'] = $cityMap[$tmp['cityCode']] ?? 0;
            }
            // 手机号格式化
            $tmp['phoneNumber'] = strval($tmp['phoneNumber']);
            // 存储车辆信息
            $reqData = Driver::compactDriverInfo($tmp);
            $result = Driver::storeDriver($reqData);
            if (is_array($result) && $result['code'] == 0) {
                $success++;
            } else {
                $filedPhone[] = $tmp['phoneNumber'];
                $filed++;
            }
        }
        $msg = "导入成功{$success}条,失败{$filed}条";
        if (count($filedPhone)) {
            $msg .= ',失败的手机号' . implode(',', $filedPhone);
        }
        return $msg;
    }

}