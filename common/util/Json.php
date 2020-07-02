<?php

namespace common\util;

use phpDocumentor\Reflection\Types\Self_;
use yii;
use yii\web\Response;

/**
 * Class Json --格式化json数据
 * @author: JerryZhang (zhanghongdong@360.net)
 * @package common\util
 */
class Json
{
    public static function bool($result, $data = [])
    {
        if ($result) {
            return self::success($data);
        }

        return Json::error($data);
    }

    public static function data($data = [])
    {
        if (FALSE === $data) {
            return self::error("失败");
        }

        if ($data['code'] != 0) {
            return self::error(isset($data['data']) ? $data['data'] : "", $data['code'], $data['message']);
        }

        return self::success($data['data']);
    }

    public static function success($data = [])
    {
        if (!$data){
            $data = new \stdClass();
        }
        return self::output(0, "success", $data);
    }

    public static function partialSuccess($success = [], $failed = [], $extra = [])
    {
        return self::output(2, "error", [
            'success' => $success,
            'fail' => $failed,
            'extra' => $extra,
        ]);
    }

    public static function error($data = [], $code = 1, $message = "error")
    {
        return self::output($code, "error" == $message && !empty($data) && is_string($data) ? $data : $message, $data);
    }

    public static function message($message = 'success', $code = 1)
    {
        return self::output($code, $message, new \stdClass());
    }

    private static function output($code, $message, $data)
    {
        if (php_sapi_name() !== "cli") {
            Yii::$app->response->format = Response::FORMAT_JSON;
        }
        $data = [
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];

        $log['request_url'] = Yii::$app->request->pathInfo;
        $log['request_params'] = Request::input();
        $log['response'] = $data;
        Yii::info(json_encode($log, JSON_UNESCAPED_UNICODE), 'process');

        return $data;
    }

    public static function emptyList()
    {
        return self::success(['totalCount' => 0, 'list' => []]);
    }
}