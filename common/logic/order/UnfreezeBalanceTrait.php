<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/11/14
 * Time: 18:25
 */
namespace common\logic\order;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;

trait UnfreezeBalanceTrait
{
    /**
     * @param $yid
     * @param $orderId
     * @return array|mixed
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\UserException
     */
    public function unfreezeBalance($yid, $orderId)
    {
        $server     = ArrayHelper::getValue(\Yii::$app->params, 'api.pay');
        $httpClient = new YesinCarHttpClient(['serverURI' => $server['serverName']]);
        $method     = $server['method']['unfreeze'];
        $clientData = compact('yid', 'orderId');
        $response   = $httpClient->post($method, $clientData);
        \Yii::info($response, 'cancel-order');
        return $response;
    }
}