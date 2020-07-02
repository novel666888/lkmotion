<?php
namespace common\logic\invoice;

//use common\models\OrderRulePrice;

use common\util\Json;
use common\util\Cache;
use common\models\InvoiceRecord;
use yii\validators\EmailValidator;
use common\jobs\SendItineraryList;
use common\util\Common;
/**
 *
 * 发票发送电子邮件
 * 
 */
trait SendInvoiceTrait
{
	/**
	 * 
	 * SEND发送队列
	 * 
	 */
	public function send($invoiceId, $newEmail=""){

		// 获取并检查行程单信息
        //$request = $this->getRequest();
        //$invoiceId = $request->get('invoiceId',1);
        $invoiceInfo = InvoiceRecord::find()->where(['id' => $invoiceId])->limit(1)->one();
        if (!$invoiceInfo) {
            //return $this->asJson(['status' => '1', 'message' => '发票ID不存在']);
        	return '发票ID不存在';
        }
        // 获取新的邮箱并验证
       	
        $validator = new EmailValidator();
        if ($newEmail && !($validator->validate($newEmail))) {
            //return $this->asJson(['status' => '1', 'message' => '新邮箱地址有误']);
        	return "新邮箱地址有误";
        }
        if (!$newEmail && !$invoiceInfo->email) {
            //return $this->asJson(['status' => '1', 'message' => '用户未填写接收邮箱']);
        	return "用户未填写接收邮箱";
        }
        
		// $res = $this->test($invoiceId);
        // 异步推送[行程单生成和发送]队列
        $queueId = \Yii::$app->queue->push(new SendItineraryList(compact('invoiceId', 'newEmail')));
        
        // 同步返回消息
        return $this->asJson(['status' => '0', 'message' => '推送行程单成功', 'queueId' => $res]);

	}






}