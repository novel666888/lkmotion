<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-28
 * Time: 上午10:41
 */

namespace common\models;

use Yii;

class SmsCode
{
    const EXPIRE = 600; // 验证码有效时间(秒)
    const TIMES = 'smsErrorTimes'; // 验证码验证次数
    const MAX = 'smsMaxPerHour'; //每小时最多发送次数
    const DEF_CODE = '111111'; //不需要验证直接通过的验证码

    /**
     * @param $phoneNumber
     * @param string $template
     * @param int $len
     * @return string
     */
    public static function create($phoneNumber, $template = 'default', $len = 6)
    {
        $count = self::addAndGetCount($phoneNumber);
        if (!$count) { // 应该限制发送
            return false;
        }
        $code = self::createCode($len);
        $redis = \Yii::$app->redis;

        $key = $template . ':' . $phoneNumber;
        $redis->setex($key, self::EXPIRE, $code);
        return $code;
    }

    /**
     * @param $phoneNumber
     * @param string $template
     * @param string $code
     * @return int
     */
    public static function validate($phoneNumber, $code = '', $template = 'default')
    {
        $redis = \Yii::$app->redis;
        $key = $template . ':' . $phoneNumber;
        $store = $redis->get($key);
        if (self::checkWhiteList($phoneNumber, $code) && $code != $store) {
            // CC检测
            $cc = self::checkCc($key, $code);
            if ($cc) {
                return -1; // 输入同一错误验证码超3次, 效果:屏蔽1分
            }
            $number = self::getCodeNumber($phoneNumber, $code);
            if (!$store) {
//                return 0; // 验证码已过期
            }
            return $number;
        }
        self::clearCodeNumber($phoneNumber, $template);
        return true; // 验证成功
    }

    /**
     * 根据验证结果返回消息
     * @param $times
     * @return string
     */
    public static function getMessageByTimes($times)
    {
        if ($times == 0) {
            return ('验证码不正确,请重新输入');
        } elseif ($times == -1) {
            return ('同一错误验证码登录失败次数过多,请 1 分钟后再重试');
        } elseif ($times >= 5) { // 冻结账户
            return ('登录失败次数过多,请 24 小时后再重试');
        } elseif ($times >= 3) {
            return ('登录失败次数过多,请 10 分钟后重试');
        } else {
            return ('验证码错误');
        }
    }

    // 获取验证次数
    private static function getCodeNumber($phoneNumber, $code)
    {
        $redis = \Yii::$app->redis;
        $key = self::TIMES . $phoneNumber;
        $hasOne = $redis->hget($key, $code);
        if (!$hasOne) {
            $redis->hset($key, $code, $code);
        }
        return $redis->hlen($key);
    }

    // 清除验证码和验证次数
    private static function clearCodeNumber($phoneNumber, $template)
    {
        $redis = \Yii::$app->redis;
        $redis->del($template . ':' . $phoneNumber);
        $redis->del(self::TIMES . $phoneNumber);
    }

    // 获取验证次数
    private static function addAndGetCount($phoneNumber)
    {
        $redis = \Yii::$app->redis;
        $key = self::MAX . ':' . $phoneNumber;
        $count = intval($redis->get($key));
        if ($count >= 5) { // 总条数大于5,限制发送
            return false;
        }
        $count++;
        $redis->setex($key, 3600, $count); // 过期一小时

        return $count;
    }

    // 生成验证码
    private static function createCode($len)
    {
        $len = intval($len);
        if ($len < 4) {
            $len = 4;
        } elseif ($len > 8) {
            $len = 8;
        }
        // 生成验证码
        $max = pow(10, $len) - 1;
        $min = pow(10, $len - 1);
        $code = strval(rand($min, $max));
        if (strlen($code) < $len) {
            $code = str_pad($code, $len, '0', 0);
        }
        return $code;
    }

    /**
     * 规则描述: 从第一次点击登录开始,1分钟内连续相同验证码错误达到3次
     * @param $key
     * @param $code
     * @return bool
     */
    private static function checkCc($key, $code)
    {
        $redis = \Yii::$app->redis;
        $key .= $code;
        $cc = intval($redis->get($key));
        if (!$cc) {
            $redis->setex($key, 60, 1); // 一分钟CC
            return false;
        }
        $redis->incr($key);
        return ((++$cc) >= 3); // 判断次数
    }

    public static function checkWhiteList($phone_num, $code)
    {
        $whiteList = Yii::$app->params['phoneWhiteList'];

        if((in_array('/', $whiteList['phoneNum']) || in_array($phone_num, $whiteList['phoneNum'])) && $code == $whiteList['verifyCode']){
            return false;
        }

        return true;
    }

}