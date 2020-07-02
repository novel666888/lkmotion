<?php
/**
 * Created by PhpStorm.
 * User: 13240
 * Date: 2018/8/23
 * Time: 11:25
 */
namespace common\actions;

use yii\base\Action;
use common\util\Json;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;


class OssSecurityTokenAction extends  Action
{
    public function run()
    {
        $requestType = \Yii::$app->request->post('requestType');
        if(!$requestType){
            return Json::message("请提交正确的参数！");
        }

        $flightServer = ArrayHelper::getValue(\Yii::$app->params,'api.file.serverName');
        $methodPath = ArrayHelper::getValue(\Yii::$app->params,'api.file.method.securityToken')."/".$requestType;
        $httpClient = new YesinCarHttpClient(['serverURI'=>$flightServer]);

        $res = $httpClient->get($methodPath, array(),2);

        if($res['code']==1){
            return Json::message('获取阿里oss token失败，服务器内部错误！');
        }else{
            return $httpClient->get($methodPath, array(),1);
        }
    }
}