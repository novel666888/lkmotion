<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/2
 * Time: 20:11
 */

namespace application\modules\order\components;

use common\services\YesinCarHttpClient;
use common\util\Common;
use yii\base\Component;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class PhoneNumber extends Component
{
    /**
     * @param array | string $cipher
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public static function decryptCipherText($cipher)
    {
        if (!is_array($cipher)) {
            $cipher = [$cipher];
        }
        $infoList = [];
        foreach ($cipher as $v) {
            $infoList[] = ['encrypt' => $v];
        }
        $postData   = ['infoList' => $infoList];
        $server     = ArrayHelper::getValue(\Yii::$app->params, 'api.account');
        $httpClient = new YesinCarHttpClient(['serverURI' => $server['serverName']]);
        $response   = $httpClient->post($server['method']['decryptPhone'], $postData);
        if (!isset($response['code']) || $response['code'] != 0) {
            throw new UserException('Decrypt phone number cipher text error!');
        }
        $phoneNumArr  = $response['data']['infoList'];
        $cipherKeys   = ArrayHelper::getColumn($phoneNumArr, 'encrypt');
        $phoneNumbers = ArrayHelper::getColumn($phoneNumArr, 'phone');
        $finalData    = array_combine($cipherKeys, $phoneNumbers);
        if (count($finalData) == 1) {
            return array_values($finalData)[0];
        }
        return $finalData;
    }

    /**
     * mapping cipher to phoneNumber
     *
     * @param $list
     * @param $keys
     * @return array
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    public static function mappingCipherToPhoneNumber($list, $keys)
    {
        $allPhones = [];
        foreach ($keys as $v) {
            $allPhones = ArrayHelper::merge($allPhones, ArrayHelper::getColumn($list, $v));
        }
        $allPhones          = array_values(array_unique(array_filter($allPhones, function ($v) {
            return !empty($v);
        })));
        $afterDecryptPhones = Common::decryptCipherText($allPhones);
        $newList            = [];
        foreach ($list as $k => $v) {
            foreach ($keys as $item) {
                if (!empty($v[$item])) {
                    $v[$item] = $afterDecryptPhones[$v[$item]];
                }
            }
            $newList[] = $v;
        }
        return $newList;
    }

    public static function dd($array)
    {
        echo '<pre>';
        print_r($array);
    }


}