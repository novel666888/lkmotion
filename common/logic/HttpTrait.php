<?php

namespace common\logic;

use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 * Trait HttpTrait
 * @package common\logic
 */
trait HttpTrait
{

    private static function httpGet($SrvDotMethod, array $params = [], $urlParams = '')
    {
        $result = self::makeHttpRequest($SrvDotMethod, $params, $urlParams, 'get');
        $info = json_encode($result, 64 | 256);
        if (PHP_SAPI == 'cli') {
            echo 'http_get_response: ', $info, PHP_EOL;
        } else {
            \Yii::info($info, 'http_get_response');
        }
        return $result;
    }

    private static function httpPost($SrvDotMethod, array $params = [], $urlParams = '')
    {
        $result = self::makeHttpRequest($SrvDotMethod, $params, $urlParams);
        $info = json_encode($result, 64 | 256);
        if (PHP_SAPI == 'cli') {
            echo 'http_post_response: ', $info, PHP_EOL;
        } else {
            \Yii::info($info, 'http_post_response');
        }
        return $result;
    }

    private static function makeHttpRequest($SrvDotMethod, array $params = [], $urlParams = '', $method = 'post')
    {
        $url = self::getRequestUrl($SrvDotMethod);
        if (!$url) {
            return ['code' => 500, 'message' => '请求地址异常'];
        }
        // 附加url参数
        if ($urlParams && is_string($urlParams)) {
            $url .= $urlParams;
        }
        if (PHP_SAPI == 'cli') {
            echo $method, ' ', $url, PHP_EOL, json_encode($params, 64 | 256), PHP_EOL;
        }
        $client = new Client(['requestConfig' => ['format' => 'json']]);
        try {
            if ('post' == strtolower($method)) {
                $response = $client->post($url, $params)->send();
            } else {
                $response = (new Client())->get($url, $params)->send();
            }
        } catch (\Exception $e) {
            return ['code' => 500, 'message' => 'JAVA服务连接异常'];
        }
        $result = $response->getData();
        if (!is_array($result) || !isset($result['code'])) {
            return ['code' => 500, 'message' => 'JAVA服务返回异常'];
        }
        return $result;
    }

    /**
     * 根据[服务.方法]拼接url
     * @param $SrvDotMethod
     * @return bool|string
     */
    private static function getRequestUrl($SrvDotMethod)
    {
        $apiConf = \Yii::$app->params['api'];

        $hostInfo = explode('.', $SrvDotMethod);
        if (count($hostInfo) != 2) {
            return '';
        }
        $host = ArrayHelper::getValue($apiConf, $hostInfo[0] . '.serverName');
        $uri = ArrayHelper::getValue($apiConf, $hostInfo[0] . '.method.' . $hostInfo[1]);

        return $host . '/' . $uri;
    }
}