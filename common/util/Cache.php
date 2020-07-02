<?php

namespace common\util;

use yii;

/**
 * Class Cache --数据缓存
 * @author: JerryZhang
 * @package common\util
 */
class Cache
{

    /**
     * get --
     * @author JerryZhang
     * @param $prefix_name
     * @param $ids
     * @return array
     * @cache Yes
     */
    public static function get($prefix_name, $ids)
    {
        $cache_keys = self::getCacheKeys($prefix_name, $ids);
        return Yii::$app->cache->multiGet($cache_keys);
    }

    /**
     * set --
     * @author JerryZhang
     * @param $prefix_name
     * @param $data
     * @param int $duration 缓存时长（单位秒，为0是持久有效）
     * @return array
     * @cache No
     */
    public static function set($prefix_name, $data, $duration = 0)
    {
        $cache_keys = self::getCacheKeys($prefix_name, array_keys($data));
        return Yii::$app->cache->multiSet(array_combine($cache_keys, $data), $duration);
    }

    /**
     * delete --
     * @author JerryZhang
     * @param $prefix_name
     * @param $id
     * @return bool
     * @cache No
     */
    public static function delete($prefix_name, $id)
    {
        $cache_keys = self::getCacheKeys($prefix_name, $id);
        return Yii::$app->cache->delete(array_shift($cache_keys));
    }

    /**
     * getCacheKeys --
     * @author JerryZhang
     * @param $prefix_name
     * @param $ids
     * @return array
     * @cache No
     */
    private static function getCacheKeys($prefix_name, $ids)
    {
        !is_array($ids) && $ids = [$ids];
        $ids = array_filter(array_unique($ids));

        $cache_keys = [];
        foreach ($ids as $v) {
            if (!empty($ids)) {
                $cache_keys[] = $prefix_name . '_' . $v;
            }
        }

        return $cache_keys;
    }
}