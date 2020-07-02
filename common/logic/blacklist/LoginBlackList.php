<?php

namespace common\logic\blacklist;

/**
 * 黑名单规则判断以及记录缓存
 */
class LoginBlackList
{

    const LEVEL1 = 60 * 1;
    const LEVEL2 = 60 * 10;
    const LEVEL3 = 60 * 60 * 24;

    const PREFIX = "blockLogin";//用户黑操作缓存前缀


    /**
     * 推入阻塞名单
     * @param $phone
     * @param int $level
     * @return bool
     */
    public static function pushBlockList($phone, $level = 1)
    {
        \Yii::info($phone, "blockLogin");
        $key = self::PREFIX . $phone;
        if ($level == 1) {
            $ttl = self::LEVEL1;
        } elseif ($level == 2) {
            $ttl = self::LEVEL2;
        } elseif ($level == 3) {
            $ttl = self::LEVEL3;
        } else {
            return false;
        }
        $redis = \Yii::$app->redis;
        $redis->setex($key, $ttl, 1);

        return true;
    }

    /**
     * 秒转剩余时间字符串 x天x时x分x秒
     * @param $sec
     * @return string
     */
    public static function sec2restString($sec)
    {
        $str = '';
        if ($sec >= 86400) {
            $d = $sec / 86400;
            $sec %= 86400;
            $str .= floor($d) . '天';
        }
        if ($sec >= 3600) {
            $h = $sec / 3600;
            $sec %= 3600;
            $str .= floor($h) . '时';
        }
        if ($sec >= 60) {
            $i = $sec / 60;
            $sec %= 60;
            $str .= floor($i) . '分';
        }
        $sec && $str .= $sec . '秒';
        return $str;
    }

    /**
     * 查询阻塞名单
     * @param $phone
     * @return int
     */
    public static function checkBlockList($phone)
    {
        $redis = \Yii::$app->redis;
        $ttl = $redis->ttl(self::PREFIX . $phone);
        if ($ttl < 1) {
            return false;
        }
        return self::sec2restString($ttl);
    }

    /**
     * 弹出阻塞名单
     * @param $phone
     * @return mixed
     */
    public static function popBlockList($phone)
    {
        $redis = \Yii::$app->redis;
        return $redis->del(self::PREFIX . $phone);
    }

}