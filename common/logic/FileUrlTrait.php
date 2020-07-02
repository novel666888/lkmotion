<?php

namespace common\logic;

/**
 * Class FileUrl
 * @package common\logic
 * 文件URL处理类
 */
trait FileUrlTrait
{
    protected $url = 'http://yesincar-test-source.oss-cn-hangzhou.aliyuncs.com/';

    /**
     * @param $item
     * @param $fileKeys
     */
    public function patchUrl(&$item, $fileKeys)
    {
        foreach ($fileKeys as $key) {
            if (!isset($item[$key]) || strlen($item[$key]) < 3) {
                continue;
            }
            if (substr($item[$key], 0, 4) == 'http') {
                continue;
            }
            $item[$key] = $this->getOssHost() . $item[$key];
        }
    }

    /**
     * @param $list
     * @param $fileKeys
     */
    public function patchListUrl(&$list, $fileKeys)
    {
        foreach ($list as &$item) {
            $this->patchUrl($item, $fileKeys);
        }
    }

    /**
     * @param $url
     * @return string
     */
    public function patchOne($url)
    {
        if (substr($url, 0, 4) == 'http') {
            return $url;
        }
        return  $this->getOssHost() . $url;
    }

    public function getOssHost()
    {
        $params = \Yii::$app->params;
        return $params['ossFileUrl'] ?? null;
    }
}