<?php
/**
 * Created by PhpStorm.
 * User: sun
 * Date: 18-10
 * Time: 上午10:47
 */

namespace application\models;

use common\models\CarBaseInfo;
use common\models\CarInsurance;
use common\models\CarInfo;
use common\services\YesinCarHttpClient;
use common\util\Common;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class Insurance
{
    public static function storePhotoInfo($photoInfo)
    {
        $urlMap = self::getImgKeys();
        $success = $field = 0;
        $fieldVin = [];
        foreach ($photoInfo as $plate => $photos) {
            $tmp = [];
            foreach ($photos as $pos => $photo) {
                $key = $urlMap[$pos] ?? false;
                if ($key) {
                    $tmp[$key] = $photo->path;
                }
            }
            if (!$tmp) continue;
            $result = self::storeUrl($plate, $tmp);
            if ($result) $success++;
            else {
                $field++;
                $fieldVin[] = $plate;
            }
        }
        $message = "成功{$success}条,失败{$field}条";
        if (count($fieldVin)) {
            $message .= '失败的车辆车牌码:' . implode($fieldVin);
        }
        return $message;
    }

    private static function storeUrl($plate, $data)
    {
        $carInfo = CarInfo::findOne(['plate_number' => $plate]);
        if (!$carInfo) {
            return false;
        }
        $car = CarInsurance::find()->where(['plate_number' => $plate])->one();
        //$car = CarBaseInfo::findOne(['id' => $carInfo->id]);
        if (!$car) {
            return false;
        }
        $car->setAttributes($data);
        return $car->save();
    }

    public static function getImgKeys()
    {
        return ['_0', // 编号占位
            'insurance_photo',
            'other_photo',
            'other_photo_2',
        ];
    }

}