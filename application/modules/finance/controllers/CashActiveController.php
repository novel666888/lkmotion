<?php
namespace application\modules\finance\controllers;

use common\models\PassengerWalletRecord;
use common\util\Json;
use common\logic\InvoiceLogic;
use application\controllers\BossBaseController;
use yii\base\UserException;

class CashActiveController extends BossBaseController
{
    //用户充值记录列表
    public function actionRechargeOrRefund(){
        $request = $this->getRequest();
        $trade_type = $request->post('tradeType');
        if (empty($trade_type)){
            return Json::message('缺少交易类型参数');
        }
        $requestData = array(
            'id' => $request->post('id'),
            'phone_number' => $request->post('phoneNumber'),
            'passenger_name' => $request->post('passengerName'),
            'pay_type' => $request->post('payType'),
            'start_time' => $request->post('startTime'),
            'end_time' => $request->post('endTime')
        );
        if (!empty($requestData['pay_type']) && !in_array($requestData['pay_type'], [1,2,3,4])){
            return Json::message('充值渠道参数错误');
        }
        $rechargeList = InvoiceLogic::getRechargeOrRefundList($trade_type, $requestData);
        if (is_string($rechargeList)){
            return Json::message($rechargeList);
        }
        $rechargeList['list'] = $this->keyMod($rechargeList['list']);
        return Json::success($rechargeList);
    }
    
    /**
     * 导出充值、退款记录
     * 
     */
//    public function actionExportCharge(){
//        $request = $this->getRequest();
//        $trade_type = $request->post('tradeType');
//        if (empty($trade_type)){
//            return Json::message('缺少交易类型参数');
//        }
//        $requestData = array(
//            'id' => $request->post('id'),
//            'phone_number' => $request->post('phoneNumber'),
//            'passenger_name' => $request->post('passengerName'),
//            'pay_type' => $request->post('payType'),
//            'start_time' => $request->post('startTime'),
//            'end_time' => $request->post('endTime')
//        );
//        if (!empty($requestData['pay_type']) && !in_array($requestData['pay_type'], [1,2,3,4])){
//            return Json::message('充值渠道参数错误');
//        }
//        PassengerWalletRecord::outPutChargeExcel($trade_type, $requestData);
//        return Json::message('导出成功', 0);
//    }


    /**
     * 导出充值、退款记录
     *
     * @return array
     * @throws UserException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionExportCharge(){
        $request = $this->getRequest();
        $trade_type = $request->post('tradeType');
        if (empty($trade_type)){
            return Json::message('缺少交易类型参数');
        }
        $requestData = array(
            'id' => $request->post('id'),
            'phone_number' => $request->post('phoneNumber'),
            'passenger_name' => $request->post('passengerName'),
            'pay_type' => $request->post('payType'),
            'start_time' => $request->post('startTime'),
            'end_time' => $request->post('endTime')
        );
        if (!empty($requestData['pay_type']) && !in_array($requestData['pay_type'], [1,2,3,4])){
            return Json::message('充值渠道参数错误');
        }
        try{
            PassengerWalletRecord::outPutChargeExcel($trade_type, $requestData);
        }catch (\UserException $e){
            return Json::message('导出充值、退款列表异常');
        }
        return Json::message('导出成功', 0);
    }
}

