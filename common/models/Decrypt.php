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

class Decrypt
{
    /**
     * @param bool $dummy
     * @return bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function getTokenInfo($dummy = false)
    {
        $jwt = self::getToken();
        if (!$jwt) {
            return false;
        }
        $jwtData = explode('.', $jwt);
        // 验证 boss端token
        $result = self::checkBossToken($jwt);
        if ($result) {
            return json_decode(base64_decode($jwtData[1]));
        }
        // 验证 客户端token
        $reqParams = ['token' => $jwt];
        $result = self::makeAccountRequest($reqParams, 'checkToken');
        if (!$result) {
            return false;
        }
        return json_decode(base64_decode($jwtData[1]));
    }

    public static function bossGetTokenInfo()
    {
        $jwt = self::getToken();
        if (!$jwt) {
            return false;
        }
        $jwtData = explode('.', $jwt);
        // 验证 boss端token
        $result = self::checkBossToken($jwt);

        if (!$result) {
            return false;
        }
        return json_decode(base64_decode($jwtData[1]));
    }

    public static function clientGetTokenInfo()
    {
        $jwt = self::getToken();
        if (!$jwt) {
            return false;
        }
        $jwtData = explode('.', $jwt);

        // 验证 客户端token
        $reqParams = ['token' => $jwt];
        $result = self::makeAccountRequest($reqParams, 'checkToken');
        if (!$result) {
            return false;
        }
        return json_decode(base64_decode($jwtData[1]));
    }

    public static function getToken()
    {
        $header = \Yii::$app->request->headers->toArray();
        $jwt = $header['authorization'][0] ?? null;
        $jwtData = explode('.', $jwt);
        if (count($jwtData) != 3) {
            return false;
        }
        return $jwt;
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

    public static function encryptPhones($phoneNumbers)
    {
        if (!is_array($phoneNumbers)) {
            return false;
        }
        $list = [];
        foreach ($phoneNumbers as $item) {
            $list[] = ['phone' => $item];
        }
        $action = 'createEncrypt';
        $reqParams = ['infoList' => $list];
        $result = self::makeAccountRequest($reqParams, $action);
        if (!$result) {
            return false;
        }
        if ($result['data']['infoList'] && is_array($result['data']['infoList'])) {
            $encryptList = $result['data']['infoList'];
            return array_column($encryptList, 'encrypt');
        }
        return false;
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
    public static function createBossToken($adminId)
    {
        $user = SysUser::find()->where(['id' => $adminId])->limit(1)->one();
        if (!$user) {
            return false;
        }
        // sub信息
        $userStr = '9_' . $user->username . '_' . $user->id;
        $token = (new Builder())->set('uid', 1)
            ->setSubject($userStr)
            ->setIssuedAt(time())
            ->sign(self::getSigner(), self::getSignKey())
            ->getToken();

        return $token;
    }

    /**
     * @param $reqParams
     * @param $action
     * @return array|bool|mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function makeAccountRequest($reqParams, $action)
    {
        $apiConf = \Yii::$app->params['api'];
        $server = ArrayHelper::getValue($apiConf, 'account.serverName');
        $methodPath = ArrayHelper::getValue($apiConf, 'account.method.' . $action);

        try {
            $httpClient = new YesinCarHttpClient(['serverURI' => $server]);
            $responseData = $httpClient->post($methodPath, $reqParams);
            if (!isset($responseData['code']) || $responseData['code'] !== 0) {
                return false;
            }
        } catch (UserException $exception) {
            return false;
        }
        return $responseData;
    }

    /**
     * 签名方式
     * @return Sha256
     */
    private static function getSigner()
    {
        return new Sha256();
    }

    /**
     * 签名密钥
     * @return string
     */
    private static function getSignKey()
    {
        $key = \Yii::$app->params['jwt-secret'] ?? null;
        if (!$key) { // 如果key不存在,写入local配置
            $key = 'jwt-e' . substr(microtime(), 2, 6) . uniqid();
            // 检测是否是boss模块中
            $module = \Yii::$app->id;
            if ($module != 'app-boss') {
                return $key;
            }
            $filename = \Yii::getAlias('@application/config/params-local.php');
            if(!is_writeable($filename)) {
                return 'file can not writable!';
            }
            $content = '<?php return [' . "'jwt-secret' =>'{$key}'" . '];';
            file_put_contents($filename, $content . "\n");
        }
        return $key;
    }

}