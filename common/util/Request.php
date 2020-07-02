<?php

namespace common\util;

use yii;

/**
 * Class Request --工具类获取请求参数
 * @author: JerryZhang (zhanghongdong@360.net)
 * @package common\util
 */
class Request
{
    private static function getRequest()
    {
        return Yii::$app->getRequest();
    }

    public static function rawInput()
    {
        return self::getRequest()->getRawBody();
    }

    public static function input($param = NULL, $default = NULL)
    {
        $val = self::cookie($param, NULL);
        if ($val !== NULL) {
            return self::cookie($param, $default);
        }

        $val = self::post($param, NULL);
        if(isset($param)){
            if ($val !== NULL) {
                return self::post($param, $default);
            }
        }else{
            if (!empty($val)) {
                return self::post($param, $default);
            }
        }

        return self::get($param, $default);
    }

    public static function input_urldecode($param = NULL, $default = NULL)
    {
        $val = self::cookie($param, NULL);
        if ($val != NULL) {
            return urldecode(self::cookie($param, $default));
        }

        $val = self::post($param, NULL);
        if ($val != NULL) {
            return urldecode(self::post($param, $default));
        }

        return urldecode(self::get($param, $default));
    }

    public static function get($param = NULL, $default = NULL)
    {
        return self::getRequest()->get($param, $default);
    }

    public static function post($param = NULL, $default = NULL)
    {
        return self::getRequest()->post($param, $default);
    }

    public static function params()
    {
        return self::getRequest()->getParams();
    }

    public static function isAjax()
    {
        return self::getRequest()->getIsAjax();
    }

    public static function isPost()
    {
        return self::getRequest()->getIsPost();
    }

    public static function isPut()
    {
        return self::getRequest()->getIsPut();
    }

    public static function isPJax()
    {
        return self::getRequest()->getIsPjax();
    }

    public static function ip()
    {
        return self::getRequest()->getUserIP();
    }

    public static function getHostInfo()
    {
        return self::getRequest()->getHostInfo();
    }

    public static function cookie($key, $default = NULL)
    {
        return self::getRequest()->getCookies()->getValue($key, $default);
    }

    public static function CsrfToken($regenerate = FALSE)
    {
        return self::getRequest()->getCsrfToken($regenerate);
    }
}