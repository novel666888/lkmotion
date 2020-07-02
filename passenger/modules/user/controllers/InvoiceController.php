<?php
namespace passenger\modules\user\controllers;

use common\logic\InvoiceLogic;
use yii;
use common\util\Json;
use common\util\Common;
use common\controllers\ClientBaseController;
use common\models\InvoiceRecord;
use common\util\Cache;
use yii\validators\EmailValidator;
use common\models\Order;
use common\models\City;
//use common\services\traits\PublicMethodTrait;
use common\jobs\SendItineraryList;
//use common\logic\blacklist\BlacklistDashboard;

class InvoiceController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }
    
    /**
     * 添加发票
     * @return [type] [description]
     */
    public function actionAdd(){
		$request = $this->getRequest();
        $postData = $request->post();
        \Yii::info($postData, 'inv');
        $requestData['order_id_list'] = isset($postData['orderIdList']) ? $postData['orderIdList'] : [];
        $requestData['invoice_price'] = isset($postData['invoicePrice']) ? trim($postData['invoicePrice']) : '';
        $requestData['invoice_status'] = 1;//申请开票(待开票)
        $requestData['invoice_type'] = 1;//全是普票
        $requestData['invoice_body'] = isset($postData['invoiceBody']) ? trim($postData['invoiceBody']) : '';
        $requestData['invoice_title'] = isset($postData['invoiceTitle']) ? trim($postData['invoiceTitle']) : '';
        $requestData['invoice_content'] = isset($postData['invoiceContent']) ? trim($postData['invoiceContent']) : '';
        $requestData['taxpayer_id'] = isset($postData['taxpayerId']) ? trim($postData['taxpayerId']) : '';
        $requestData['reg_address'] = isset($postData['regAddress']) ? trim($postData['regAddress']) : '';
        $requestData['reg_phone'] = isset($postData['regPhone']) ? trim($postData['regPhone']) : '';
        $requestData['deposit_bank'] = isset($postData['depositBank']) ? trim($postData['depositBank']) : '';
        $requestData['bank_account'] = isset($postData['bankAccount']) ? trim($postData['bankAccount']) : '';
        $requestData['receiver_name'] = isset($postData['receiverName']) ? trim($postData['receiverName']) : '';
        $requestData['receiver_phone'] = isset($postData['receiverPhone']) ? trim($postData['receiverPhone']) : '';
        $requestData['receiver_address'] = isset($postData['receiverAddress']) ? trim($postData['receiverAddress']) : '';
        $requestData['email'] = isset($postData['email']) ? trim($postData['email']) : '';
        //$requestData['passenger_info_id'] = isset($postData['passengerId']) ? trim($postData['passengerId']) : '';
        if(empty($requestData['order_id_list']) ||
            empty($requestData['invoice_type']) ||
            empty($requestData['invoice_body']) ||
            empty($requestData['invoice_title']) ||
            empty($requestData['invoice_content']) ||
            empty($requestData['receiver_name']) ||
            empty($requestData['receiver_phone']) ||
            empty($requestData['receiver_address'])
        ){
            return Json::message("参数不完整");
        }

        if($requestData['invoice_body']==2){
            if(empty($requestData['taxpayer_id'])){
                return Json::message("纳税人识别号不可以为空");
            }
        }

        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }else{
            $requestData['passenger_info_id'] = $this->userInfo['id'];
        }
        if(!is_array($requestData['order_id_list']) || count($requestData['order_id_list'])<=0){
            return Json::message("订单ID非数组或为空");
        }
        $trans = $this->beginTransaction();
        $data = InvoiceRecord::add($requestData);
        if($data['code']==0){
            $trans->commit();
            //判断发送email
            if(!empty($requestData['email'])){
                $newEmail = $requestData['email'];
                $invoiceId = $data['data'];
                // 异步推送[行程单生成和发送]队列
                $queueId = \Yii::$app->queue->push(new SendItineraryList(compact('invoiceId', 'newEmail')));
                //$this->test($invoiceId, $newEmail);
                \Yii::info($queueId, "send Invoice");
            }
            return Json::success();
        }else{
            $trans->rollBack();
            return Json::message($data['message']);
        }
    }


    /**
     * 重新发送电子发票
     */
    public function actionReSend(){
        //return BlacklistDashboard::checkDeviceBlacklist("bd6a168a-1212-4ba6-a38f-0b911e99c341", 1);

        $request = $this->getRequest();
        $invoiceId = $request->post('invoiceId');
        if(empty($invoiceId) || !is_numeric($invoiceId)){
            return Json::message("Parameter error");
        }

        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }

        $invoiceInfo = InvoiceRecord::find()->where(['id'=>$invoiceId, 'passenger_info_id'=>$this->userInfo['id']])->limit(1)->one();
        if (!$invoiceInfo) {
            return Json::message("发票ID错误");
        }
        // 获取新的邮箱并验证
        $newEmail = $request->post('email');
        if(!empty($newEmail)){
            $validator = new EmailValidator();
            if ($newEmail && !($validator->validate($newEmail))) {
                return Json::message("新邮箱地址错误");
            }else{
                $invoiceInfo->email = $newEmail;
                $invoiceInfo->save();
            }
        }

        if(empty($invoiceInfo->email)){
            return Json::message("用户不可以发送邮件");
        }

        $newEmail = $invoiceInfo->email;
        \Yii::info([$invoiceId,$newEmail], "ReSend invoice");
        // 异步推送[行程单生成和发送]队列
        $queueId = \Yii::$app->queue->push(new SendItineraryList(compact('invoiceId', 'newEmail')));
        //$this->test($invoiceId, $newEmail);
        // 同步返回消息
        return $this->asJson(['code' => '0', 'message' => '已发送电子行程单']);
    }


    /**
     * 获取发票历史列表
     */
    public function actionList(){
        //$this->userInfo['id'] = 23311;
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $Data=[];
        $Data['passengerId']       =       $this->userInfo['id'];
        $select=[
            "id","passenger_info_id","invoice_price","invoice_status","create_time"
        ];
        $rs = InvoiceLogic::getInvoiceList($Data, true, $select);
        if(!empty($rs) && is_array($rs)){
            //return Json::success(Common::key2lowerCamel($rs));
            return Json::success($rs);
        }else{
            $rs = $_rs['list'] = [];
            return Json::success($rs);
        }
    }

    /**
     * 获取发票详情信息
     */
    public function actionDetail(){
        $request = $this->getRequest();
        $postData = $request->post();
        $requestData['invoiceId'] = isset($postData['invoiceId']) ? trim($postData['invoiceId']) : '';
        if(!is_numeric($requestData['invoiceId'])){
            return Json::message("invoiceId error");
        }
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }else{
            $requestData['passengerId'] = $this->userInfo['id'];
        }
        \Yii::info($requestData, 'fapiao_detail');
        $rs = InvoiceLogic::invoiceDetail($requestData['invoiceId'], $requestData['passengerId']);
        if(!empty($rs) && is_array($rs)){
            $rs = array_values($rs);
            if(isset($rs[0])){
                return Json::success(Common::key2lowerCamel($rs[0]));
            }else{
                return Json::success([]);
            }
        }else{
            return Json::success([]);
        }
    }


}