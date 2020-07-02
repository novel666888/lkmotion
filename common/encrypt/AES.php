<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/17
 * Time: 14:00
 */

namespace common\encrypt;

class AES
{
    const cipher = 'aes-128-cbc'; // 加密模式

    /**
     * 加密方法
     *
     * 1, 根据密钥创建向量IV
     * 2, AES-CBC模式加密
     * 3, 对字符串进行base64编码输出
     * @param $plaintext
     * @param $secret
     * @return string
     */
    public static function encrypt($plaintext, $secret)
    {
        $iv = self::createIvBySecret($secret);
        $cipherText = openssl_encrypt($plaintext, self::cipher, $secret, OPENSSL_RAW_DATA, $iv);
        return base64_encode($cipherText);
    }

    /**
     * 解密方法
     *
     * 1, 根据密钥创建向量IV
     * 2, 对字符串进行base64解码
     * 3, AES-CBC模式解密
     *
     * @param $cipherText
     * @param $secret
     * @return string
     */
    public static function decrypt($cipherText, $secret)
    {
        $iv = self::createIvBySecret($secret);
        $plaintext = openssl_decrypt(base64_decode($cipherText), self::cipher, $secret, OPENSSL_RAW_DATA, $iv);
        return $plaintext;
    }

    /**
     * 根据密钥创建向量IV
     *
     * 根据加密方式,获取向量IV长度
     * 根据IV长度截取密钥后面部分,若长度不足前面补字符串0
     *
     * @param $secret
     * @return string
     */
    public static function createIvBySecret($secret)
    {
        $ivLen = openssl_cipher_iv_length(self::cipher);
        return str_pad(substr($secret, -$ivLen), $ivLen, '0', STR_PAD_LEFT);
    }
}