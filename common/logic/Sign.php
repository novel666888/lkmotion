<?php
/**
 *  接口签名类
 */

namespace common\logic;


use common\models\Decrypt;
use Faker\Provider\Uuid;

class Sign
{
    protected $debug = false;
    const maxOffset = 300; // 时间差
    const replayPrefix = 'replaySign:'; // 重放攻击检测用key

    /**
     * @param $token
     * @return string
     */
    public function genKey($token)
    {
        $key = Uuid::uuid();

        $redis = \Yii::$app->redis;
        $redis->hset('sign_hash_list', $this->getSub($token), $key);

        return $key;
    }

    public function getSecretByToken($token)
    {
        return $this->getSecret($this->getSub($token));
    }


    /**
     * @param $deviceCode
     * @return string
     */
    public function largeScreenGenKey($deviceCode)
    {
        $key = Uuid::uuid();

        $redis = \Yii::$app->redis;
        $redis->hset('sign_hash_list', $deviceCode, $key);

        return $key;
    }

    public function largeScreenGetSecretByToken($deviceCode)
    {
        return $this->getSecret($deviceCode);
    }


    /**
     * @param null $postData
     * @param string $secret
     * @return array|bool
     */
    public function checkSign($postData = null, $secret = '')
    {
        if (!$secret) {
            $token = Decrypt::getToken();
            $secret = $this->getSecret($this->getSub($token));
        }

        // 获取输入
        if (!is_array($postData) || !$postData) {
            $postData = \Yii::$app->request->post();
        }
        // 检测签名的有效性
        if (!isset($postData['sign']) || !is_string($postData['sign']) || strlen($postData['sign']) < 32) {
            \Yii::info('Invalid Signature：'.$postData['sign'], 'sign_check_error');
            return false;
        }
        $sign = strtolower($postData['sign']);

        //校验万能key,方便自动化测试
        $master_key = \Yii::$app->params['checkSignMasterKey'] ?? '';
        if (!empty($master_key) && strtolower($master_key) === $sign) {
            return true;
        }

        unset($postData['sign']);
        // 排序参数
        ksort($postData);
        // 组装签名串
        $signStr = json_encode($postData,
                JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES  // 不处理0|不进行unicode编码 | 不转义反斜杠
            ) . $secret;
        // 返回签名验证
        if (1) {
            $serverSign = md5($signStr);
            $result = [
                'clientSign' => $sign,
                'secret' => $secret,
                'signString' => $signStr,
                'serverSign' => $serverSign,
                'result' => ($serverSign == $sign)
            ];
            \Yii::debug($result, 'sign_debug_info');
        }
        $result = (md5($signStr) == $sign);
        if ($result) {
            $this->setReplaySign($sign);
        } elseif (1) {
            \Yii::debug(\Yii::$app->request->rawBody, 'post_raw_body');
        }
        return $result;
    }

    /**
     * @param $postData
     * @param $secret
     * @return bool|string
     */
    public function sign($postData, $secret)
    {
        if (!is_array($postData) || !$postData) {
            return false;
        }
        // 排序参数
        ksort($postData);
        // 组装签名串
        $signStr = json_encode($postData,
                JSON_PRESERVE_ZERO_FRACTION | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES  // 不处理0|不进行unicode编码 | 不转义反斜杠
            ) . $secret;
        // 返回签名验证
        return md5($signStr);

    }

    /**
     * @return bool|string
     */
    public static function checkReplayAttack()
    {
        $sign = \Yii::$app->request->post('sign');
        $ts = \Yii::$app->request->post('ts');
        if (!$sign) {
            return '签名异常';
        }
        if (!$ts) {
            return '时间异常';
        }
        $timeOffset = abs(intval($ts / 1000) - time());
        if ($timeOffset > self::maxOffset) {
            return '客户端时差过大';
        }
        $redis = \Yii::$app->redis;
        $sameSign = $redis->get(self::replayPrefix . $sign);
        if ($sameSign) {
            return true;
        }
        return false;
    }

    private function setReplaySign($sign)
    {
        $redis = \Yii::$app->redis;
        $redis->setex(self::replayPrefix . $sign, self::maxOffset, '1');
    }

    private function getSub($token)
    {
        $def = 'default';
        if (!is_string($token)) {
            return $def;
        }
        $jwt = explode('.', $token);
        if (count($jwt) != 3) {
            return $def;
        }
        $info = json_decode(base64_decode($jwt[1]));
        // 返回sub
        return $info->sub ?? $def;
    }

    private function getSecret($sub)
    {
        $redis = \Yii::$app->redis;
        return $redis->hget('sign_hash_list', $sub);
    }

}