<?php

namespace passenger\modules\activities\controllers;

use common\controllers\ClientBaseController;
use common\events\PassengerEvent;
use common\logic\FeeTrait;
use common\util\Json;

/**
 * Class ChargeController
 * @package passenger\modules\activities\controllers
 */
class ChargeController extends ClientBaseController
{
    use FeeTrait;

    /**
     * 充值失败通知接口, 目前用于充赠逻辑
     * 充值失败后, 清除充增次数
     * @return array
     */
    public function actionNotify()
    {
        $request = \Yii::$app->request;
        $outTradeNo = trim(strval($request->post('outTradeNo')));
        $status = strtolower(trim(strval($request->post('status'))));
        // 如果同步充值没有失败,
        $passengerId = $this->userInfo['id'] ? intval($this->userInfo['id']) : 0;
        if ($status == 'failed') {
            $this->clearGiveMark($passengerId, $outTradeNo);
        } elseif($status == 'success') { // 充值事件
            (new PassengerEvent())->charge($passengerId);
        }
        return Json::success();
    }

}
