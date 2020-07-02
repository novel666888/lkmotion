<?php
/**
 * 发票管理
 * 
 * Created by Zend Studio
 * User: lijin
 * Date: 2018年8月11日
 * Time: 下午5:44:48
 */
namespace application\modules\finance\controllers;

use common\logic\InvoiceLogic;
use common\models\InvoiceRecord;
use common\util\Json;
use common\util\Cache;
use yii\validators\EmailValidator;
use common\services\traits\PublicMethodTrait;
use common\util\Common;
use common\jobs\SendItineraryList;
use common\models\PassengerInfo;
use application\controllers\BossBaseController;
class InvoiceController extends BossBaseController{

    use PublicMethodTrait;
    //发票列表
    public function actionInvoiceList(){
        $request = $this->getRequest();
        $requestData = array(
            'searchId' => $request->post('searchId'),
            'invoiceStatus' => $request->post('invoiceStatus'),
            'createTimeStart' => $request->post('createTimeStart'),
            'createTimeEnd' => $request->post('createTimeEnd'),
        );
        if (!empty($requestData['invoiceStatus']) && !in_array($requestData['invoiceStatus'], [1,2,3,4])){
            return Json::message('发票状态参数错误');
        }
        $invoiceList = InvoiceLogic::getInvoiceList($requestData);
        $invoiceList['list'] = $this->keyMod($invoiceList['list']);
        return Json::success($invoiceList);
    }

    //发票详情
    public function actionInvoiceDetail(){
        $request = $this->getRequest();
        $invoiceId = $request->post('invoiceId');
        //验证参数
        if (empty($invoiceId)){
            return Json::message('缺少发票id参数');
        }
        $invoiceDetail = InvoiceLogic::invoiceDetail($invoiceId);
        if (!$invoiceDetail){
            return Json::message('发票不存在！');
        }
        $invoiceDetail = $this->keyMod(array_values($invoiceDetail));
        return Json::success($invoiceDetail[0]);
    }
    
    //发票邮寄
    public function actionInvoiceSend(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $invoiceId = !empty($requestData['invoiceId']) ? intval($requestData['invoiceId']) : '0';
        $expressCompanyName = !empty($requestData['expressCompanyName']) ? trim($requestData['expressCompanyName']) : '';
        $expressNum = !empty($requestData['expressNum']) ? trim($requestData['expressNum']) : '';
        $invoiceNum = !empty($requestData['invoiceNum']) ? trim($requestData['invoiceNum']) : '';
        
        //验证参数
        if (empty($invoiceId) || empty($expressCompanyName) || empty($expressNum) || empty($invoiceNum)){
            return Json::message('请传递完整参数');
        }
        if(!InvoiceRecord::checkInvoice($invoiceId,$status=2)){
            return Json::message('发票不存在或状态错误');
        }else{
            $invoiceDetail = InvoiceRecord::findOne(['id'=>$invoiceId]);
            $invoiceDetail->express_company_name = $expressCompanyName;
            $invoiceDetail->express_num = $expressNum;
            $invoiceDetail->express_time = date("Y-m-d H:i:s", time());
            $invoiceDetail->invoice_number = $invoiceNum;
            $invoiceDetail->invoice_status = 3;
            // 验证输入内容
            if (!$invoiceDetail->validate()) {
                $msg = $invoiceDetail->getFirstError();
                return Json::message($msg);
            }
            if (!$invoiceDetail->save()){
                return Json::message('邮寄失败');
            }else{
                //更新订单发票状态
                Common::updateOrder($invoiceDetail->order_id_list, 4);
                $updateData = InvoiceRecord::find()->where(['id'=>$invoiceId])->indexBy('id')->asArray()->all();
                Cache::set('invoice_record', $updateData, 0);
                
                //发送短信通知开票人
                $invoiceInfo = InvoiceRecord::find()->select(['create_time','passenger_info_id'])->where(['id'=>$invoiceId])->asArray()->one();
                $passengerPhone = Common::getPhoneNumber([['id'=>$invoiceInfo['passenger_info_id']]], 1);
                $data = array(
                    'create_time' => date("Y-m-d H:i", strtotime($invoiceInfo['create_time'])),
                    'company_name' => $expressCompanyName,
                    'express_num' => $expressNum,
                    'express_time' => date("Y-m-d H:i", time()),
                );
                Common::sendMessageNew($passengerPhone[0]['phone'], 'HX_0023', $data);
            }
        }
        return Json::message('邮寄成功', 0);
    }
    
    //发票撤销
    public function actionInvoiceCancel(){
        $request = $this->getRequest();
        $invoiceId = $request->post('invoiceId');
        $cancelDesc = $request->post('cancelDesc');
        if (empty($invoiceId) || empty($cancelDesc)){
            return Json::message('请传递完整参数');
        }
        if(!InvoiceRecord::checkInvoice($invoiceId)){
            return Json::message('发票不存在');
        }else{
            $invoiceDetail = InvoiceRecord::findOne(['id'=>$invoiceId]);
            $invoiceDetail->cancel_desc = $cancelDesc;
            $invoiceDetail->invoice_status = 4;
            if (!$invoiceDetail->save()){
                return Json::message('撤销失败');
            }else{
                //更新订单发票状态
                Common::updateOrder($invoiceDetail->order_id_list, 5);
                $updateData = InvoiceRecord::find()->where(['id'=>$invoiceId])->indexBy('id')->asArray()->all();
                Cache::set('invoice_record', $updateData, 0);
                
                //发送短信通知开票人
                $passengerId = InvoiceRecord::find()->select(['passenger_info_id'])->where(['id'=>$invoiceId])->scalar();
                $passengerName = PassengerInfo::find()->select(['passenger_name'])->where(['id'=>$passengerId])->scalar();
                $passengerPhone = Common::getPhoneNumber([['id'=>$passengerId]], 1);
                $data = array(
                    'passenger_name' => $passengerName,
                );
                Common::sendMessageNew($passengerPhone[0]['phone'], 'HX_0018', $data);
            }
        }
        return Json::message('撤销成功', 0);
    }
    
    //发送发票邮件
    public function actionSendItinerary()
    {
        // 获取并检查行程单信息
        $request = $this->getRequest();
        $invoiceId = $request->post('invoiceId');
        $invoiceInfo = InvoiceRecord::find()->where(['id' => $invoiceId])->limit(1)->one();
        if (!$invoiceInfo) {
            return Json::message('发票ID不存在');
        }
        // 获取新的邮箱并验证
        $newEmail = $request->post('email');
        $validator = new EmailValidator();
        if ($newEmail && !($validator->validate($newEmail))) {
            return Json::message('新邮箱地址有误');
        }
        if (!$newEmail && !$invoiceInfo->email) {
            return Json::message('用户未填写接收邮箱');
        }
        
        // 异步推送[行程单生成和发送]队列
        $queueId = \Yii::$app->queue->push(new SendItineraryList(compact('invoiceId', 'newEmail')));

        // 同步返回消息
        if ($queueId){
            $msg = '推送行程单成功';
        }else{
            $msg = '推送行程单失败';
        }
        return Json::message($msg,0);
    }
//
//    //导出发票
//    public function actionInvoiceExport(){
//        $request = $this->getRequest();
//        $requestData = array(
//            'searchId' => $request->post('searchId'),
//            'invoiceStatus' => $request->post('invoiceStatus'),
//            'createTimeStart' => $request->post('createTimeStart'),
//            'createTimeEnd' => $request->post('createTimeEnd'),
//        );
//        if (isset($requestData['invoiceStatus']) && !in_array($requestData['invoiceStatus'], [1,2,3,4])){
//            return Json::message('发票状态参数错误');
//        }
//        InvoiceRecord::outPutInvoiceExcel($requestData);
//        return Json::message('导出成功', 0);
//    }

    //导出发票
    public function actionInvoiceExport(){
        $request = $this->getRequest();
        $requestData = array(
            'searchId' => $request->post('searchId'),
            'invoiceStatus' => $request->post('invoiceStatus'),
            'createTimeStart' => $request->post('createTimeStart'),
            'createTimeEnd' => $request->post('createTimeEnd'),
        );
        if (isset($requestData['invoiceStatus']) && !in_array($requestData['invoiceStatus'], [1,2,3,4])){
            return Json::message('发票状态参数错误');
        }
        try{
            InvoiceRecord::outPutInvoiceExcel($requestData);
        }catch (\UserException $e){
            return Json::message('导出发票异常');
        }
        return Json::message('导出成功', 0);
    }
    
    
    
    
    
    
    
    
    
    
}