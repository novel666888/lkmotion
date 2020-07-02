<?php

namespace common\jobs;

use Knp\Snappy\Pdf;
use yii\base\BaseObject;
use common\models\InvoiceRecord;
use common\models\Order;
use common\models\City;
use common\models\CarInfo;
use common\models\CarType;
use common\models\OrderRulePrice;
use common\util\Common;

/**
 * Class NotifyBridge.
 */
class SendItineraryList extends BaseObject implements \yii\queue\JobInterface
{
    public $invoiceId; // 发票ID
    public $newEmail = null;  // 新的邮箱地址

    protected $max_retry = 5; // 最大尝试次数
    
    private $log_cat_key = 'queue_send_email';

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $invoiceId = $this->invoiceId;

        \Yii::info("invoiceId=".$invoiceId, $this->log_cat_key);        
        // 获取和检测发票信息
        $invoiceInfo = InvoiceRecord::find()->where(['id' => $invoiceId])->limit(1)->asArray()->one();
        if (!$invoiceInfo) {
            \Yii::info(json_encode($invoiceInfo), 'invoiceInfo_empty');
            return false;
        }

        $orderIds = explode(',', $invoiceInfo['order_id_list']);
        if (!$orderIds) {
            // 记录日志 ???
            \Yii::info(json_encode($orderIds), 'orderIds_empty');
            return false;
        }

        // 获取订单信息
        $orders = Order::find()->where(['id' => $orderIds])->indexBy('id')->asArray()->all();
        $orderIdsArr = array_unique(array_column($orders, 'id'));
        $orderDetail = OrderRulePrice::find()->select(['order_id','total_distance','total_price','city_code'])->where(['order_id'=>$orderIdsArr,'category'=>1])->indexBy('order_id')->asArray()->all();
        if (!empty($orders)){
            foreach ($orders as $key=>$value){
                $orders[$key]['city_code'] = $orderDetail[$value['id']]['city_code'] ?? "";
            }
        }
        
        $cityCode = array_unique(array_column($orderDetail, 'city_code'));
        $cityCodeArr = City::find()->select(['city_code','city_name'])->where(['city_code'=>$cityCode])->asArray()->all();
        $cityList = array_unique(array_column($cityCodeArr, 'city_name','city_code'));
        if (!$orders) {
            // 订单异常,记录日志 ???
            \Yii::info(json_encode($orders),'order_empty');
            return false;
        }

        // 获取车型
        $carIds = array_unique(array_column($orders, 'car_id'));
        $carTypeIds = CarInfo::find()->select(['id','car_type_id'])->where(['id'=>$carIds])->indexBy('id')->asArray()->all();
        $carTypeIdsArr = array_unique(array_column($carTypeIds, 'car_type_id'));
        $carType = CarType::find()->select(['type_desc','id'])->where(['id'=>$carTypeIdsArr])->asArray()->all();
        $carDesc = array_unique(array_column($carType, 'type_desc','id'));
        if (!empty($carTypeIds)){
            foreach ($carTypeIds as $key=>$value){
                $carList[$key] = $carDesc[$value['car_type_id']] ?? "";
            }
        }
        // 计算总里程(暂行办法)
        $totalDistance = array_sum(array_column($orderDetail, 'total_distance'));

        $passengerId = [['id'=>$invoiceInfo['passenger_info_id']]];
        $phoneNumber = Common::getPhoneNumber($passengerId, 1);
        $invoiceInfo['reg_phone'] = $phoneNumber[0]['phone'];

        // 生成行程单
        $html = \Yii::$app->view->renderFile('@application/views/pdf/itineraryList.php', compact('invoiceInfo', 'orders', 'cityList', 'carList', 'totalDistance', 'orderDetail'));
        
        $binPath = \Yii::$app->params['pdfBin'];
        if (!$binPath) {
            $binPath = '@vendor/h4cc/wkhtmltopdf-amd64/bin/wkhtmltopdf-amd64';
        }
        if (substr($binPath, 0, 1) == '@') {
            $binPath = \Yii::getAlias($binPath);
        }
        $snappy = new Pdf($binPath);
        $pdfContent = $snappy->getOutputFromHtml($html);
        
        $filename = '电子行程单_' . date('Y-m-d') . '_' . uniqid() . '.pdf';
        // 记录本地-开发用,
        //$snappy->generateFromHtml($html,  $filename);
        // 做邮箱二次检测 ???
        $email = $this->newEmail ? $this->newEmail : $invoiceInfo->EMAIL;
        \Yii::info(json_encode($email), 'email_account');
        $result = \Yii::$app->mailer->compose()
        ->setTo($email)
        ->setSubject('电子行程单')
        ->attachContent($pdfContent, ['fileName' => $filename, 'contentType' => 'application/pdf'])
        ->send();
        \Yii::info("result=".$result, 'send_email_result');
        // 返回
        //var_dump($result);
        //exit;
        return $result;
    }
}
