<?php

namespace largescreen\controllers;

use common\logic\Sign;
use common\controllers\BaseController;
use yii\helpers\ArrayHelper;
use common\util\Common;

class LargeScreenClientBaseController extends BaseController
{
    /**
     * 验签开关说明:
     * 配置状态为false的时候, 所有大屏不开启验证
     * 配置状态为true,所有大屏开启验证
     */
    public function init()
    {
        parent::init();
        $this->checkSign();
    }

    public function checkSign()
    {
        $LargeScreen = ArrayHelper::getValue(\Yii::$app->params,'LargeScreenSignCheckSwitch');
        \Yii::info($LargeScreen, 'LargeScreenSignCheckSwitch');
        $LargeScreenWhiteList = Common::checkUrlWhiteList('LargeScreenWhiteList');

        if($LargeScreen && $LargeScreenWhiteList){
            $result = \Yii::$app->request->post();
            $deviceCode = $result['deviceCode'];
            \Yii::info($result, 'deviceCode');

            $signModel = new Sign();
            $secret = $signModel->largeScreenGetSecretByToken($deviceCode);
            \Yii::info($secret, 'secret');
            $secretKey = $signModel->checkSign($postData = null, $secret);
            \Yii::info($secretKey, 'secretKey');

            if(!$secretKey){
                echo json_encode(['code' => 0, 'message' => '验签失败!', 'data' => []]);
                exit();
            }
        }
    }
}
