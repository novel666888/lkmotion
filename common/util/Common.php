<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/9
 * Time: 18:01
 */

namespace common\util;

use common\jobs\SendMessage;
use yii;
use yii\base\Component;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
use yii\base\UserException;

class Common extends Component
{
    const SUCCESS_CODE = 0;
    const ERROR_CODE = 1;
    /**
     * @author By zzr
     * @param $data
     * @param string $separator
     * @return array
     */
    public static function key2lowerCamel($data, $separator = '_')
    {
        if (!$data || !is_array($data)) {
            return $data;
        }
        foreach ($data as $key => $item) {
            if (is_string($key)) {
                if(strpos($key,$separator) == -1){
                    $newKey = $key;
                }else{
                    $newKey = lcfirst(\yii\helpers\Inflector::id2camel($key, $separator));
                }
                unset($data[$key]);
                $data[$newKey] = self::key2lowerCamel($item);
            } else {
                $data[$key] = self::key2lowerCamel($item);
            }
        }
        return $data;
    }

    /**
     * @param string $token
     * @return bool|\stdClass
     */
    public static function getCurrentUser($token = '')
    {
        if (!$token) {
            return false;
        }
        $user           = new \stdClass();
        $user->id       = 1;
        $user->username = 'admin';
        return $user;
    }

    /**
     * @param string $token
     * @return string
     */
    public static function getAdminName($token = 'boss')
    {
        $user = self::getCurrentUser();
        if (!$user || !is_object($user) || !isset($user->username)) {
            return 'unknown_user';
        }
        return $user->username;
    }

    /**
     * select返回的数组进行整数映射转换
     *
     * @param array $map 映射关系二维数组  array(
     *                                          '字段名1'=>array(映射关系数组),
     *                                          '字段名2'=>array(映射关系数组),
     *                                           ......
     *                                       )
     * @return array|mixed
     *
     *  array(
     *      array('id'=>1,'title'=>'标题','status'=>'1','status_text'=>'正常')
     *      ....
     *  )
     *
     */
    public static function int_to_string(&$data, $map)
    {
        if ($data === false || $data === null) {
            return $data;
        }
        $data = (array)$data;
        foreach ($data as $key => $row) {
            foreach ($map as $col => $pair) {
                if (isset($row[$col]) && isset($pair[$row[$col]])) {
                    $data[$key][$col . '_text'] = $pair[$row[$col]];
                }
            }
        }
        return $data;
    }

    /**
     * 二组数组按key排序
     *
     * @author By zzr
     * @param $arr
     * @param $keys
     * @param string $type
     * @return array
     */

    public static function arraySortByKey($arr, $keys, $type = 'desc')
    {
        $keyValue = $newArray = array();
        foreach ($arr as $k => $v) {
            $keyValue[$k] = $v[$keys];
        }
        if ($type == 'asc') {
            asort($keyValue);
        } else {
            arsort($keyValue);
        }
        reset($keyValue);
        foreach ($keyValue as $k => $v) {
            $newArray[$k] = $arr[$k];
        }
        return array_values($newArray);
    }

    /**
     * 将明文手机号转换为加密码（最新）
     * @param $phoneNum 一维数组，index=>手机号 / 字符串，单个手机号
     * @return 一维数组 key=>密文，value=>明文
     */
    public static function phoneEncrypt($phoneNum){
        if(empty($phoneNum)){
            return false;
        }
        if(!is_array($phoneNum)){
            $phoneNum = [$phoneNum];
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.createEncrypt');
        $data=[];
        foreach($phoneNum as $phoneN){
            $data['infoList'][] = ['phone'=>$phoneN];
        }
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $rs = $httpClient->post($methodPath, $data);
        if(isset($rs['code']) && $rs['code']==0){
            if(empty($rs['data']['infoList'])){
                throw new UserException("Phone createEncrypt error!", 1);
            }
            $phoneNumArr  = $rs['data']['infoList'];
            $cipherKeys   = ArrayHelper::getColumn($phoneNumArr, 'phone');
            $phoneNumbers = ArrayHelper::getColumn($phoneNumArr, 'encrypt');
            $finalData    = array_combine($cipherKeys, $phoneNumbers);
            if (count($finalData) == 1) {
                return array_values($finalData)[0];
            }
            return $finalData;
        }else{
            throw new UserException("Phone createEncrypt error!", 1);
        }

    }

    
    /**
     * 将明文手机号转换为加密码（停用）
     * @param $phoneNum 一维数组，index=>手机号
     * @return 二维数组（明文/密文一组）/false
     */
    public static function phoneNumEncrypt($phoneNum){
        if(empty($phoneNum)){
            return false;
        }
        if(!is_array($phoneNum)){
            $phoneNum = [$phoneNum];
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.account.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.account.method.createEncrypt');
        $data=[];
        foreach($phoneNum as $phoneN){
            $data['infoList'][] = ['phone'=>$phoneN];
        }
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $rs = $httpClient->post($methodPath, $data);
        if(isset($rs['code']) && $rs['code']==0){
            return $rs['data']['infoList'];
        }else{
            throw new UserException("Phone createEncrypt error!", 1);
        }
    }
    
    /**
     * 通过用户id获取明文手机号
     * 
     * @param arr $uidArr  乘客/司机的id二维数组
     * @param int $userType  1：乘客 ，2：司机
     * @return array（二维数组）
     */
    public static function getPhoneNumber($uidArr, $userType){
        $query_arr = array();
        $query_arr['idType'] = $userType;
        $query_arr['infoList'] = $uidArr;
        $account = \Yii::$app->params['api']['account'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $phones = $YesinCarHttpClient->post($account['method']['getPhoneList'], $query_arr);
        \Yii::info(json_encode($query_arr), 'query_data');
        \Yii::info(json_encode($phones), 'java_get_phone_result');
        return $phones['data']['infoList'];
    }
    
    /**
     * 通过密文获取明文手机号
     *
     * @param arr $phone  手机号二维数组
     * @return array（二维数组）
     */
    public static function getPhoneNumberByEncrypt($phone){
        $query_arr = array();
        $query_arr['infoList'] = $phone;
        $account = \Yii::$app->params['api']['account'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $phones = $YesinCarHttpClient->post($account['method']['decryptPhone'], $query_arr);
        \Yii::info(json_encode($query_arr), 'query_data');
        \Yii::info(json_encode($phones), 'java_get_phone_result');
        return $phones['data']['infoList'];
    }
    
    /**
     * 发送短信
     * 
     * @param array $phones 一维数组
     * @param array $data 结构参考java消息短信接口data参数
     * @throws Exception
     */
    public static function sendMessage($phones, $data){
        if (empty($data) || empty($phones)){
            throw new UserException('params_error');
        }
        $sendData = array('receivers'=>$phones,'data'=>$data);
        $account = \Yii::$app->params['api']['message'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['sendMessage'], $sendData);
        \Yii::info(json_encode($sendData,256), 'send_phone_message_data');
        \Yii::info(json_encode($result,256),'send_phone_message_result');
        return $result;
    }
    
    /**
     * 新的发短信方法
     * 
     * @param string $phone
     * @param string $smsId
     * @param array $data 一维数组
     * @return array|mixed
     */
    public static function sendMessageNew($phone, $smsId, $data){
        if (empty($phone) || empty($smsId)){
            throw new UserException('params_error');
        }
        $phone = [$phone];
        $data = array_values($data);
        $result = \Yii::$app->queue->push(new SendMessage(['phone'=>$phone,'smsId'=>$smsId,'data'=>$data]));
        return $result;
    }
    
    /**
     * 登录验证码
     * 
     * @param string $phone
     * @param string $code
     * @return array|mixed
     */
    public static function sendLoginCode($phone, $code){
        if (empty($phone) || empty($code)){
            throw new UserException('params_error');
        }
        $data = [
            'code' => $code
        ];
        return self::sendMessageNew($phone, 'HX_0037', $data);
    }
    
    /**
     * 调java插入轮询消息
     * 
     * @param unknown $data
     * @throws Exception
     */
    public static function insertLoop($data){
        if (empty($data)){
            throw new UserException('params_error');
        }
        $account = \Yii::$app->params['api']['message'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['insertLoop'], $data);
        \Yii::info(json_encode($data,256), 'loop_data');
        \Yii::info(json_encode($result,256), 'java_insert_loop_result');
        return $result;
    }
    
    /**
     * 修改司机信息
     * 
     * @param unknown $data
     * @return void|array|mixed
     */
    public static function updateDriverInfo($data){
        if (empty($data)){
            throw new UserException('params_error');
        }
        $account = \Yii::$app->params['api']['account'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['updateDriverInfo'], $data);
        \Yii::info(json_encode($data,256));
        \Yii::info(json_encode($result,256),'update_driver_msg_res');
        return $result;
    }
    
    /**
     * 解密司机地址
     * 
     * @param string $address
     * @return void|array|mixed
     */
    public static function  addressDecryption($address){
        if (empty($address)){
            throw new UserException('params_error');
        }
        $data = [
            'address'=>$address
        ];
        $account = \Yii::$app->params['api']['account'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['addressDecryption'], $data);
        \Yii::info(json_encode($data,256));
        \Yii::info(json_encode($result,256),'get_driver_address_result');
        if ($result['code'] == 0){
            return $result['data'];
        }
        return false;
    }
    
    
    /**
     * 修改订单发票状态
     * 
     * @param int $orderId
     * @param int $status
     * @return void|array|mixed
     */
    public static function updateOrder($orderId, $status){
        if (empty($orderId) || empty($status)){
            throw new UserException('params_error');
        }
        $data = [
            'orderIds' => $orderId,
            'invoiceType' => $status
        ];
        $account = \Yii::$app->params['api']['order'];
        $YesinCarHttpClient = new YesinCarHttpClient(['serverURI'=>$account['serverName']]);
        $result = $YesinCarHttpClient->post($account['method']['updateBatchOrder'], $data);
        \Yii::info(json_encode($data), 'updateData');
        \Yii::info(json_encode($result, 256), 'update_result');
        return $result;
    }
    
    /**
     * 过滤用户输入的基本数据，防止script攻击
     *
     * @param      string
     * @return     string
     */
    public static function compile_str($str){
        $arr = array('<' => '＜', '>' => '＞','"'=>'”',"'"=>'’');
        return strtr($str, $arr);
    }
    
    /**
     * 验证手机号
     *
     * @param string $phoneNum
     * @return number
     */
    public static function checkPhoneNum($phoneNum)
    {
        return preg_match("/^1[34578]{1}\d{9}$/",$phoneNum);
    }

    /**
     * 验证链接地址是否正确
     *
     * @param $url
     * @return bool
     */
    public static function checkUrl($url){
        if(!preg_match('/http|https:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is',$url)){
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @param array $arr
     * @return array
     */

    public static function filterNull(Array $arr)
    {
        return array_filter($arr,function ($item){return !is_null($item);});
    }

    /**
     * decryptCipherText
     *
     * @param array | string $cipher
     * @param bool $raw 如果该值为true,并且返回的电话号码只有一个则只返回电话号码，而非关联数组
     * @return array | string
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     * @author  By zzr
     */
    public static function decryptCipherText($cipher,$raw = false)
    {
        if(empty($cipher)) return '';
        if (!is_array($cipher)) {
            $cipher = [$cipher];
        }
        $infoList = [];
        foreach ($cipher as $v) {
            $infoList[] = ['encrypt' => $v];
        }
        $postData   = ['infoList' => $infoList];
        $server     = ArrayHelper::getValue(\Yii::$app->params, 'api.account');
        $httpClient = new YesinCarHttpClient(['serverURI' => $server['serverName']]);
        $response   = $httpClient->post($server['method']['decryptPhone'], $postData);
        if (!isset($response['code']) || $response['code'] != 0) {
            throw new UserException('Decrypt phone number cipher text error!');
        }
        $phoneNumArr  = $response['data']['infoList'];
        $cipherKeys   = ArrayHelper::getColumn($phoneNumArr, 'encrypt');
        $phoneNumbers = ArrayHelper::getColumn($phoneNumArr, 'phone');
        $finalData    = array_combine($cipherKeys, $phoneNumbers);
        if (count($finalData) == 1 && $raw) {
            return array_values($finalData)[0];
        }
        return $finalData;
    }

    /**
     *
     * @author by zzr
     * @param array $arr
     * @return array
     */
    public static function getUniqueAndNotEmptyValueFromArray(array $arr)
    {
        return array_values(array_unique(array_filter($arr,function($v){
            return !empty($v);
        })));
    }

    /**
     * 将dateTime 日期转换成 自然语言 如今天 明天 昨天
     *
     * @author by zzr
     * @param $targetTime //format '2018-9-19 18:34:34'
     * @return false|string
     */

    public static function convertTimeToNaturalLanguage($targetTime)
    {
        $targetTime = strtotime($targetTime);

        $todayLast = strtotime(date('Y-m-d 23:59:59'));
        if ($targetTime < $todayLast) {
            $agoTime     = $todayLast - $targetTime;
            $agoDay      = floor($agoTime / 86400);
            if ($agoDay == 0) {
                $result = '今天 ' . date('H时i分', $targetTime);
            } elseif ($agoDay == 1) {
                $result = '昨天 ' . date('H时i分', $targetTime);
            } elseif ($agoDay == 2) {
                $result = '前天 ' . date('H时i分', $targetTime);
            } elseif ($agoDay > 2 && $agoDay < 16) {
                $result = $agoDay . '天前 ' . date('H时i分', $targetTime);
            } else {
                $format = date('Y') != date('Y', $targetTime) ? "Y年m月d分 H时i分" : "m月d日 H时i分";
                $result = date($format, $targetTime);
            }
        } else {
            $futureTime = $targetTime - $todayLast;
            $futureDay  = floor($futureTime / 86400);
            if ($futureDay == 0) {
                $result = '明天 ' . date('H点i分', $targetTime);
            } else {
                $format = date('Y') != date('Y', $targetTime) ? "Y年m月d日 H时i分" : "m月d日 H时i分";
                $result = date($format, $targetTime);
            }
        }
        return $result;

    }

    /**
     * 获取电话屏蔽中位四位或后四位 默认后四位,tail 为false 则将电话中间四们隐藏掉
     *
     * @param $phoneNumber
     * @param $tail
     * @return bool|string
     */

    public static function getHidePhone($phoneNumber,$tail = true)
    {
        if(strlen($phoneNumber)!==11){
            return $phoneNumber;
        }
        if($tail){
            return substr($phoneNumber,-4);
        }else{
            return preg_replace('/(1[3456789]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$phoneNumber);
        }
    }

    /**
     * @param $content
     * @param $to
     * @param $subject
     * @param $from
     * @return bool
     */

    public static function mail($content,$to,$subject)
    {
        $mail = \Yii::$app->mailer->compose();
        $mail->setFrom(\Yii::$app->params['supportEmail']);
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setHtmlBody($content);    //发布可以带html标签的文本
        if($mail->send()){
            return true;
        }
        return false;
    }

    /**
     * 从二维数组中筛选出指定的列
     *
     * @param array $list
     * @param array $keys
     * @return array
     */
    public static function getCertainColumnFromTowDimensionalArray(array $list,array $keys = [])
    {
        if(empty($keys)){
            return $list;
        }
        $newList = [];
        foreach ($list as $k=>$v){
            $allKeys = array_keys($v);
            $diffKeys = array_diff($allKeys,$keys);
            foreach ($diffKeys as $item){
                unset($v[$item]);
            }
            $newList[] = $v;
        }
        return $newList;
    }

    /**
     * checkUrlWhiteList --请求地址白名单校验
     * @author JerryZhang
     * @param $paramWhiteListKey
     * @return bool
     * @cache No
     */
    public static function checkUrlWhiteList($paramWhiteListKey)
    {
        $pathInfo = Yii::$app->getRequest()->pathInfo;

        if (substr($pathInfo, 0, 1) == '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        $whiteList = Yii::$app->params[$paramWhiteListKey];
        return (in_array('/', $whiteList) || in_array($pathInfo, $whiteList)) ? false : true;
    }


    public static function ratedPassenger($orderId=null){
        if(empty($orderId) || !is_numeric($orderId)){
            return false;
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.ratedPassenger');
        $methodPath.= "/".$orderId;
        $data = [
            'operation' => 'insert'
        ];
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $rs = $httpClient->get($methodPath, $data, 0);
        \Yii::info($rs, 'ratedPassenger');
        if(isset($rs['code']) && $rs['code']==0){
            return true;
        }else{
            throw new UserException("java ratedPassenger error!", 1);
        }
    }

    public static function ratedPassengerComplaint($orderId=null){
        if(empty($orderId) || !is_numeric($orderId)){
            return false;
        }
        $server = ArrayHelper::getValue(\Yii::$app->params,'api.base.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.base.method.ratedPassengerComplaint');
        $methodPath.= "/".$orderId;
        $data = [
            'operation' => 'insert'
        ];
        $httpClient = new YesinCarHttpClient(['serverURI'=>$server]);
        $rs = $httpClient->get($methodPath, $data, 0);
        \Yii::info($rs, 'ratedPassengerComplaint');
        if(isset($rs['code']) && $rs['code']==0){
            return true;
        }else{
            throw new UserException("java ratedPassengerComplaint error!", 1);
        }
    }

    /**
     * 判断数组$a是否是数组$b的子集
     *
     * @param $a
     * @param $b
     * @return bool
     */

    public static function arrIsContained($a,$b)
    {
        if ($a == array_intersect($a, $b)) {
            return true;
        } else {
            return false;
        }

    }



}