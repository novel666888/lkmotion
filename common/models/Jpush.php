<?php

namespace common\models;

use yii\base\Model;

/**
 * Class Jpush
 * @package common\models
 */
class Jpush extends Model
{
    const PREFIX = 'jp_alias';
    const TTL = 300; // 5分钟

    public static function genAlias($deviceCode = '')
    {
        $appId = \Yii::$app->id;
        $appId = str_replace('app-', '', $appId); // 去掉这部分,极光别名不能用中划线
        $env = \Yii::$app->params['env'] ?? 'nil';
        $dataArray = [$env, $appId,];
        if ($deviceCode) {
            $dataArray[] = $deviceCode;
        }
        $dataArray[] = uniqid();
        $alias = implode('_', $dataArray);
        self::pushAlias($alias);
        return $alias;
    }

    public static function checkAlias($alias = '')
    {
        // 别名检测
        if (!$alias || !is_string($alias)) {
            return false;
        }
        return self::popAlias($alias);
    }

    private static function pushAlias($alias)
    {
        $redis = \Yii::$app->redis;
        $redis->setex(self::PREFIX . ':' . $alias, self::TTL, '1');
    }

    private static function popAlias($alias)
    {
        $redis = \Yii::$app->redis;
        return $redis->del(self::PREFIX . ':' . $alias);
    }
}
