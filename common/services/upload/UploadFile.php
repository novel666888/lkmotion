<?php
/**
 * 文件上传类
 * 
 * Created by Zend Studio
 * User: lijin
 * Date: 2018年8月11日
 * Time: 下午5:09:55
 */
namespace common\services\upload;

use yii\base\BaseObject;
USE OSS\OssClient;
USE OSS\Core\OssException;

class UploadFile extends BaseObject
{
    
    const ACCESSKEYID = "";
    const ACCESSKEYSECRET = "";
    const ENDPOINT = "";
    const BUCKET = "";
    //文件名称
//     const OBJECT = "";
    // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
    const FILEPATH = "";
    
    /**
     * 上传图片文件
     * 
     * @return string
     */
    public static function uploadPicture(){
        $allowImg = array("jpg", "jpeg", "png", "gif", "bmp");  //图片格式
        //检查文件格式
        $ext = self::extend($_FILES['file']['name']); //获取文件后缀名
        if(!in_array($ext,$allowImg)){
            echo '图片格式不正确';
            exit;
        }
        //检查文件大小(不超过5G)
        if ($_FILES['file']['size'] > 5*1024*1024*1024){
            echo '文件太大无法上传';
            exit;
        }
        $fileName = md5(time().$_FILES['file']['name']).'.'.$ext;
        
        //检查存储空间是否存在
        if (self::checkBucket()){
           $url = self::upload($fileName);
           return $url;
        }else{
            echo '存储空间不存在'; //是否创建存储空间****
            exit;
        }
    }
    
    /**
     * 上传视频文件
     * 
     * @return string
     */
    public static function uploadVideo(){
        $allowVideo = array("avi", "wmv", "rmvb", "mpeg4", "mp4", "mov", "mkv");  //视频格式
        //检查文件格式
        $ext = self::extend($_FILES['file']['name']); //获取文件后缀名
        if(!in_array($ext,$allowVideo)){
            echo '视频格式不正确';
            exit;
        }
        //检查文件大小(不超过5G)
        if ($_FILES['file']['size'] > 5*1024*1024*1024){
            echo '视频太大无法上传';
            exit;
        }
        $fileName = md5(time().$_FILES['file']['name']).'.'.$ext;
        //检查存储空间是否存在
        if (self::checkBucket()){
            $url = self::upload($fileName);
            return $url;
        }else{
            echo '存储空间不存在'; //是否创建存储空间****
            exit;
        }
    }
    
    /*
     * 上传文件
     * 
     * @param string $fileName
     * @return string
     */
    public static function upload($fileName){
        //上传文件
        try {
            $ossClient = new OssClient(self::ACCESSKEYID, self::ACCESSKEYSECRET, self::ENDPOINT);
            $result  = $ossClient->putObject(self::BUCKET, $fileName, self::FILEPATH);
            // 设置建立连接的超时时间，单位秒，默认10秒。
            $ossClient->setConnectTimeout(20);
            return $result['info']['url'];
        } catch (OssException $e) {
            print $e->getMessage();
        }
        return false;
    }
    
    /**
     * 检查存储空间是否存在
     * 
     * @return boolean
     */
    public static function checkBucket(){
        try {
            $ossClient = new OssClient(self::ACCESSKEYID, self::ACCESSKEYSECRET, self::ENDPOINT);
            $res = $ossClient->doesBucketExist(self::BUCKET);
            return $res;
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return false;
        }
    }
    
    /**
     * 获取文件后缀名
     * 
     * @param string $file_name
     * @return string
     */
    public static function extend($file_name){
        $extend = pathinfo($file_name);
        $extend = strtolower($extend["extension"]);
        return $extend;
    } 
    
    
}