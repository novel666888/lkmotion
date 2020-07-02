<?php
namespace application\controllers;

use application\controllers\BossBaseController;
use yii\helpers\ArrayHelper;

class CheckBaseController extends BossBaseController
{
    public function init()
    {
        parent::init();
        $bossCheck = ArrayHelper::getValue(\Yii::$app->params,'bossSignCheckSwitch');
        if($bossCheck == 1){
            $this->checkEncryptParam();
        }
    }

    public function checkEncryptParam()
    {
        $result = \Yii::$app->request->post();
        \Yii::info($result, 'getData');

        if(!$result['sign']){
            echo json_encode(['code' => 403, 'messages' => '接口参数验证失败!', 'data' => []]); exit();
        }
        $result = array_filter($result, function($v) {
            if (!is_array($v)) {
                return true;
            }
        });
        \Yii::info($result, 'result');
        $sign = $result['sign'];
        $dateline = $result['dateline'];
        unset($result['sign']);
        unset($result['dateline']);

        ksort($result);
        $result['dateline'] = $dateline;
        $result['secretKey'] = ArrayHelper::getValue(\Yii::$app->params,'secretKey');
        \Yii::info($result, 'paramresult');

        $param = http_build_query($result, null,'&', PHP_QUERY_RFC3986);
        \Yii::info($param, 'param');

        $checkSign = md5($param);
        \Yii::info($checkSign, 'checkSign');

        if( $sign != $checkSign ){
            echo json_encode(['code' => 403, 'messages' => '接口参数验证失败!', 'data' => []]); exit();
        }
    }

}