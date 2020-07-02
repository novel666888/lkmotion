<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/11/9
 * Time: 14:05
 */
namespace common\services;
/**
 * Class XEncode
 * @package common\services
 */
class XEncode{
    private $_skey = null;

    public function __construct($key = 'yesincar')
    {
        $this->_skey = $key;
    }


    /**
     * 简单对称加密
     * @param string $string [需要加密的字符串]
     * @param string $skey [加密的key]
     * @return [type]   [加密后]
     */
    function encode($string = '')
    {
        $strArr = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($this->_skey) as $key => $value)
            $key < $strCount && $strArr[$key].=$value;
        return str_replace(array('=', '+', '/'), array('O0O0O', 'o000o', 'oo00o'), join('', $strArr));
    }
    /**
     * 简单对称解密
     * @param string $string [加密后的值]
     * @param string $skey [加密的key]
     * @return [type]   [加密前的字符串]
     */
    function decode($string = '')
    {
        $strArr = str_split(str_replace(array('O0O0O', 'o000o', 'oo00o'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($this->_skey) as $key => $value)
            $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        return base64_decode(join('', $strArr));
    }
}
