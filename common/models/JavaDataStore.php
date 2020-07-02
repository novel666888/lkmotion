<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-9
 * Time: 下午8:03
 */

namespace common\models;

use common\services\YesinCarHttpClient;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class JavaDataStore
{
    /**
     * @param bool $dummy
     * @return bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function getTokenInfo($dummy = false)
    {
        $header = \Yii::$app->request->headers->toArray();
        $jwt = $header['authorization'][0] ?? null;
        $jwtData = explode('.', $jwt);
        if (count($jwtData) != 3) {
            return false;
        }
        $action = 'checkToken';
        $reqParams = ['token' => $jwt];
        $result = self::makeAccountRequest($reqParams, $action);
        if (!$result) {
            return false;
        }
        return json_decode(base64_decode($jwtData[1]));
    }

    /**
     * 注册token
     * @param $requestData
     * @return bool|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function generateToken($requestData)
    {
        if (!isset($requestData['type']) || !isset($requestData['phoneNum']) || !isset($requestData['id'])) {
            return false;
        }
        $action = 'generateToken';
        $result = self::makeAccountRequest($requestData, $action);
        if (!$result) {
            return false;
        }
        return is_string($result['data']) ? $result['data'] : false;
    }

    /**
     * 注册token
     * @param $token
     * @return bool|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function checkToken($token)
    {
        if (!$token) {
            return false;
        }
        $action = 'checkToken';
        $reqParams = ['token' => $token];
        $result = self::makeAccountRequest($reqParams, $action);
        if (!$result) {
            return false;
        }
        return true;
    }

    /**
     * @param $phoneNumber
     * @return bool|string
     * @throws \yii\base\InvalidConfigException
     */
    public static function encryptPhone($phoneNumber)
    {
        $action = 'createEncrypt';
        $reqParams = ['infoList' => [['phone' => $phoneNumber]]];
        $result = self::makeAccountRequest($reqParams, $action);
        if (!$result) {
            return false;
        }
        return $result['data']['infoList'][0]['encrypt'] ?? false;
    }

    /**
     * @param string $token
     * @return bool
     */
    public static function checkBossToken($token = '')
    {
        if (!$token) {
            $header = \Yii::$app->request->headers->toArray();
            $token = isset($header['authorization'][0]) ? $header['authorization'][0] : null;
        }
        $token = (new Parser())->parse(strval($token));
        // 检测注册时间
        $iat = $token->getClaim('iat');
        if (((10 * 3600) + $iat) < time()) {
            return false;
        }
        // 检测签名
        return $token->verify(self::getSigner(), self::getSignKey());
    }

    /**
     * boss产生token
     * @param $adminId
     * @return bool|\Lcobucci\JWT\Token
     */
    public static function storeCarInfo($requestParam)
    {
        $result = self::makeAccountRequest($requestParam, 'carStore');

    }

    /**
     * @param $reqParams
     * @param $action
     * @return array|bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    private static function makeAccountRequest($reqParams, $action)
    {
        $apiConf = \Yii::$app->params['api'];
        $server = ArrayHelper::getValue($apiConf, 'account.serverName');
        $methodPath = ArrayHelper::getValue($apiConf, 'account.method.' . $action);

        try {
            $httpClient = new YesinCarHttpClient(['serverURI' => $server]);
            $responseData = $httpClient->post($methodPath, $reqParams);
            if (!isset($responseData['code'])) {
                return false;
            }
            if ($responseData['code'] !== 0) {
                return $responseData['message'] ?? '';
            }
        } catch (UserException $exception) {
            return false;
        }
        return $responseData;
    }
}