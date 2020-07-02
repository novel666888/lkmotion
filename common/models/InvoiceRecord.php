<?php

namespace common\models;

use common\util\Cache;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use common\services\traits\ModelTrait;
use PHPExcel;
use PHPExcel_IOFactory;
use SimpleExcel\SimpleExcel;
use common\util\Common;
use yii\base\UserException;
use common\models\Order;
use common\models\OrderPayment;

/**
 * This is the model class for table "{{%invoice_record}}".
 *
 * @property int $id 发票ID
 * @property int $passenger_info_id 乘客ID
 * @property string $order_id_list 订单list，逗号分隔
 * @property string $invoice_price 发票总价
 * @property int $invoice_status 1：申请开票(待开票)，2：已开票,3:已驳回，4：已撤销,5：已邮寄
 * @property int $invoice_type 发票类型：1：普票，2：专票
 * @property int $invoice_body 发票主体：1：个人，2：企业
 * @property string $invoice_title 发票抬头
 * @property string $invoice_content 发票内容
 * @property string $taxpayer_id 纳税人识别号
 * @property string $reg_address 注册地址
 * @property string $reg_phone 注册电话
 * @property string $deposit_bank 开户银行
 * @property string $bank_account 银行账号
 * @property string $receiver_name 收件人姓名
 * @property string $receiver_phone 收件人电话
 * @property string $receiver_address 收件人地址
 * @property string $create_time 申请开票时间
 * @property int $express_company 快递公司
 * @property string $express_num 快递号
 * @property string $express_time 邮寄时间
 * @property int $reject_id 驳回原因ID
 * @property string $cancel_desc 撤销原因
 * @property string $email 邮件地址
 * @property string $invoice_number 发票号
 * @property string $express_company_name 快递公司名称
 */
class InvoiceRecord extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%invoice_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'invoice_status', 'invoice_type', 'invoice_body', 'express_company', 'reject_id'], 'integer'],
            [['order_id_list'], 'required'],
            [['invoice_price'], 'required'],
            [['create_time', 'express_time'], 'safe'],
            [['order_id_list'], 'string', 'max' => 512],
            [['invoice_title', 'invoice_content', 'email', 'express_company_name'], 'string', 'max' => 128],
            [['taxpayer_id', 'reg_phone', 'bank_account', 'receiver_name', 'express_num', 'cancel_desc', 'invoice_number'], 'string', 'max' => 32],
            [['reg_address', 'receiver_address'], 'string', 'max' => 256],
            [['deposit_bank'], 'string', 'max' => 218],
            [['receiver_phone'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'order_id_list' => 'Order Id List',
            'invoice_price' => 'Invoice Price',
            'invoice_status' => 'Invoice Status',
            'invoice_type' => 'Invoice Type',
            'invoice_body' => 'Invoice Body',
            'invoice_title' => 'Invoice Title',
            'invoice_content' => 'Invoice Content',
            'taxpayer_id' => 'Taxpayer ID',
            'reg_address' => 'Reg Address',
            'reg_phone' => 'Reg Phone',
            'deposit_bank' => 'Deposit Bank',
            'bank_account' => 'Bank Account',
            'receiver_name' => 'Receiver Name',
            'receiver_phone' => 'Receiver Phone',
            'receiver_address' => 'Receiver Address',
            'create_time' => 'Create Time',
            'express_company' => 'Express Company',
            'express_num' => 'Express Num',
            'express_time' => 'Express Time',
            'reject_id' => 'Reject ID',
            'cancel_desc' => 'Cancel Desc',
            'email' => 'Email',
            'invoice_number' => 'Invoice Number',
            'express_company_name' => 'Express Company Name',
        ];
    }
    
    /**
     * 导出发票excel表
     * 
     * @param array $requestData 查询条件
     */
//    public static function outPutInvoiceExcel($requestData){
//        //设置内存
//        ini_set("memory_limit", "2048M");
//        set_time_limit(0);
//        //获取传过来的信息（时间，公司ID之类的，根据需要查询资料生成表格）
//        $objectPHPExcel = new PHPExcel();
//        //设置表格头的输出
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('A1', '序号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('B1', '用户姓名');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('C1', '用户手机号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('D1', '申请时间');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('E1', '发票主体');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('F1', '发票金额');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('G1', '发票类型');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('H1', '发票状态');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('I1', '发票号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('J1', '快递公司');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('K1', '邮寄时间');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('L1', '快递单号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('M1', '是否包邮');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('N1', '发票抬头');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('O1', '纳税人识别号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('P1', '发票内容');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('Q1', '注册地址');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('R1', '注册电话');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('S1', '开户行');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('T1', '银行帐号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('U1', '收件人');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('V1', '收件人电话');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('W1', '收件人地址');
//
//        //跳转到recharge这个model文件的statistics方法去处理数据
//        $data = self::_getData($requestData);
//        if (!empty($data)){
//            foreach ($data as $key=>$value){
//                $data[$key]['invoice_body'] = $value['invoice_body'] == 1 ? '个人' : '企业';
//                $data[$key]['invoice_type'] = $value['invoice_type'] == 1 ? '普票' : '专票';
//                $data[$key]['invoice_status'] = ($value['invoice_status']==1 ? '待开票' :  ($value['invoice_status']==2 ? '已开票' : ($value['invoice_status']==3 ? '已驳回' : ($value['invoice_status']==4 ? '已撤销' : '已邮寄'))));
//            }
//        }
//        //指定开始输出数据的行数
//        $n = 2;
//        foreach ($data as $v){
//            $objectPHPExcel->getActiveSheet()->setCellValue('A'.($n) ,$v['id']);//序号
//            $objectPHPExcel->getActiveSheet()->setCellValue('B'.($n) ,$v['passenger_name']);//用户姓名
//            $objectPHPExcel->getActiveSheet()->setCellValue('C'.($n) ,$v['phone_number'].' ');//用户电话
//            $objectPHPExcel->getActiveSheet()->setCellValue('D'.($n) ,$v['create_time']);//申请时间
//            $objectPHPExcel->getActiveSheet()->setCellValue('E'.($n) ,$v['invoice_body']);//发票主题
//            $objectPHPExcel->getActiveSheet()->setCellValue('F'.($n) ,$v['invoice_price']);//发票金额
//            $objectPHPExcel->getActiveSheet()->setCellValue('G'.($n) ,$v['invoice_type']);//发票类型
//            $objectPHPExcel->getActiveSheet()->setCellValue('H'.($n) ,$v['invoice_status']);//发票状态
//            $objectPHPExcel->getActiveSheet()->setCellValue('I'.($n) ,$v['invoice_number']);//发票号
//            $objectPHPExcel->getActiveSheet()->setCellValue('J'.($n) ,$v['express_company_name']);//快递公司
//            $objectPHPExcel->getActiveSheet()->setCellValue('K'.($n) ,$v['express_time']);//邮寄时间
//            $objectPHPExcel->getActiveSheet()->setCellValue('L'.($n) ,$v['express_num'].' ');//快递单号
//            $objectPHPExcel->getActiveSheet()->setCellValue('M'.($n) ,'');//是否包邮
//            $objectPHPExcel->getActiveSheet()->setCellValue('N'.($n) ,$v['invoice_title']);//发票抬头
//            $objectPHPExcel->getActiveSheet()->setCellValue('O'.($n) ,$v['taxpayer_id']);//纳税人识别号
//            $objectPHPExcel->getActiveSheet()->setCellValue('P'.($n) ,$v['invoice_content']);//发票内容
//            $objectPHPExcel->getActiveSheet()->setCellValue('Q'.($n) ,$v['reg_address']);//注册地址
//            $objectPHPExcel->getActiveSheet()->setCellValue('R'.($n) ,$v['reg_phone'].' ');//注册电话
//            $objectPHPExcel->getActiveSheet()->setCellValue('S'.($n) ,$v['deposit_bank']);//开户行
//            $objectPHPExcel->getActiveSheet()->setCellValue('T'.($n) ,$v['bank_account'].' ');//银行账号
//            $objectPHPExcel->getActiveSheet()->setCellValue('U'.($n) ,$v['receiver_name']);//收件人姓名
//            $objectPHPExcel->getActiveSheet()->setCellValue('V'.($n) ,$v['receiver_phone'].' ');//收件人电话
//            $objectPHPExcel->getActiveSheet()->setCellValue('W'.($n) ,$v['receiver_address']);//收件人地址
//            $n = $n +1;
//        }
//        ob_clean();
//        ob_start();
//        header('Content-Type:application/vnd.ms-excel');
//
//        //设置输出文件名及格式
//        header('Content-Disposition:attachment;filename="发票单'.date("YmdHis").'.xls"');
//
//        //导出.xls格式的话使用Excel5,若是想导出.xlsx需要使用Excel2007
//        $objWriter= PHPExcel_IOFactory::createWriter($objectPHPExcel,'Excel5');
//        $objWriter->save('php://output');
//        exit;
//        ob_end_flush();
//
//        //清空数据缓存
//        unset($data);
//    }

    /**
     * 导出发票excel表
     *
     * @param $requestData
     * @throws UserException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function outPutInvoiceExcel($requestData){
        $data = self::_getData($requestData);
        if(empty($data)){
            throw new UserException('data empty');
        }
        $head = self::getValues();
        $list = [$head];
        if (!empty($data)){
            foreach ($data as $key=>$value){
                $list[] = self::getValues($value);
            }
        }
        // 生成数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($list);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . "发票单" . date('YmdHi') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        // 保存excel
        $writer->save('php://output');
        //删除清空：
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        exit;
    }

    /**
     * 组装发票excel数据
     *
     * @param array $value
     * @return array
     */
    private static function getValues($value=[]){
        $item = [];
        $item['id'] = isset($value['id']) ? $value['id'] : "序号";
        $item['passenger_name'] = isset($value['passenger_name']) ? $value['passenger_name'] : "用户姓名";
        $item['phone_number'] = isset($value['phone_number']) ? $value['phone_number'] : "用户手机号";
        $item['create_time'] = isset($value['create_time']) ? $value['create_time'] : "申请时间";
        $item['invoice_body'] = isset($value['invoice_body']) ? ($value['invoice_body'] == 1 ? '个人' : '企业') : "发票主体";
        $item['invoice_price'] = isset($value['invoice_price']) ? $value['invoice_price'] : "发票金额";
        $item['invoice_type'] = isset($value['invoice_type']) ? ($value['invoice_type'] == 1 ? '普票' : '专票') : "发票类型";
        $item['invoice_status'] = isset($value['invoice_status']) ? ($value['invoice_status']==1 ? '待开票' :  ($value['invoice_status']==2 ? '待邮寄' : ($value['invoice_status']==3 ? '已邮寄' : ($value['invoice_status']==4 ? '已撤销' : '状态错误')))) : "发票状态";
        $item['invoice_number'] = isset($value['invoice_number']) ? $value['invoice_number'] : "发票号";
        $item['express_company_name'] = isset($value['express_company_name']) ? $value['express_company_name'] : "快递公司";
        $item['express_time'] = isset($value['express_time']) ? $value['express_time'] : "邮寄时间";
        $item['express_num'] = isset($value['express_num']) ? $value['express_num'] : "快递单号";
        $item['invoice_title'] = isset($value['invoice_title']) ? $value['invoice_title'] : "发票抬头";
        $item['taxpayer_id'] = isset($value['taxpayer_id']) ? $value['taxpayer_id'] : "纳税人识别号";
        $item['invoice_content'] = isset($value['invoice_content']) ? $value['invoice_content'] : "发票内容";
        $item['reg_address'] = isset($value['reg_address']) ? $value['reg_address'] : "注册地址";
        $item['reg_phone'] = isset($value['reg_phone']) ? $value['reg_phone'] : "注册电话";
        $item['deposit_bank'] = isset($value['deposit_bank']) ? $value['deposit_bank'] : "开户行";
        $item['bank_account'] = isset($value['bank_account']) ? $value['bank_account'] : "银行帐号";
        $item['receiver_name'] = isset($value['receiver_name']) ? $value['receiver_name'] : "收件人";
        $item['receiver_phone'] = isset($value['receiver_phone']) ? $value['receiver_phone'] : "收件人电话";
        $item['receiver_address'] = isset($value['receiver_address']) ? $value['receiver_address'] : "收件人地址";
        return $item;
    }


    /**
     * 获取发票数据
     * 
     * @return array
     */
    private static function _getData($requestData){
        $query = self::find();
        $query->select('*');
        if(!empty($requestData['searchId'])){
            if (Common::checkPhoneNum($requestData['searchId'])){
                $secretPhone = Common::phoneNumEncrypt([$requestData['searchId']]);
                $requestData['searchId'] = $secretPhone['0']['encrypt'];
                $passengerIds = PassengerInfo::find()->select(['id'])->where(['phone'=>$requestData['searchId']])->asArray()->all();
            }else{
                $query->andFilterWhere(['OR', ['LIKE', 'express_num', $requestData['searchId']], ['LIKE', 'invoice_number', $requestData['searchId']]]);
            }
        }
        if (!empty($passengerIds)){
            $searchIds = array_unique(array_column($passengerIds, 'id'));
            $query->andFilterWhere(['IN','passenger_info_id', $searchIds]);
        }
        if(isset($requestData['invoiceStatus'])){
            $query->andFilterWhere(['invoice_status'=>$requestData['invoiceStatus']]);
        }
        if(!empty($requestData['createTimeStart'])){
            $query->andFilterWhere(['>','create_time',$requestData['createTimeStart']]);
        }
        if(!empty($requestData['createTimeEnd'])){
            $query->andFilterWhere(['<','create_time',$requestData['createTimeEnd']]);
        }
        if(!empty($requestData['passengerId'])){
            $query->andFilterWhere(['passenger_info_id'=>intval($requestData['passengerId'])]);
        }
        
        $invoiceList = $query->asArray()->orderBy('create_time desc')->all();
        $passengerIdsArr = self::fetchArray('',['passenger_info_id as id'],'id ASC');
        if (!empty($invoiceList)){
            $passengerIds = array_unique(array_column($invoiceList, 'passenger_info_id'));
            //获取用户手机号
            $phones = Common::getPhoneNumber($passengerIdsArr, 1);
            $passengerPhones = array_unique(array_column($phones, 'phone', 'id'));
            $passengerMsg = PassengerInfo::find()->select(['id','passenger_name'])->where(['id'=>$passengerIds])->indexBy('id')->asArray()->all();
            $orderList = '';
            $invoiceId = '';
            foreach ($invoiceList as $key=>$value){
                $invoiceList[$key]['passenger_name'] = !empty($passengerMsg[$value['passenger_info_id']]['passenger_name']) ? $passengerMsg[$value['passenger_info_id']]['passenger_name'] : '';
                $invoiceList[$key]['phone_number'] = !empty($passengerPhones[$value['passenger_info_id']]) ? $passengerPhones[$value['passenger_info_id']] : '';
                $orderList .= $value['order_id_list'].",";
                $invoiceId .= $value['id'].",";
            }
            $invoiceIds = explode(",", substr($invoiceId, 0, strlen($invoiceId)-1));
            $res = self::updateAll(['invoice_status'=>2],['id'=>$invoiceIds,'invoice_status'=>1]);
            if ($res){
                $invoiceData = InvoiceRecord::find()->where(['id'=>$invoiceIds])->indexBy('id')->asArray()->all();
                Cache::set('invoice_record', $invoiceData, 0);
            }
            $orderList = substr($orderList, 0, strlen($orderList)-1);
            Common::updateOrder($orderList, 3);
        }
        return $invoiceList;
    }
    
    /**
     * 检查发票是否存在
     * 
     * @param int $invoiceId
     * @return boolean
     */
    public static function checkInvoice($invoiceId,$status=0){
        $query = self::find()->where(['id'=>$invoiceId]);
        if ($status > 0){
            $query->andWhere(['invoice_status'=>2]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return false;
        }
        return true;
    }

    /**
     * 验证订单有效性
     * @param $passengerId 乘客ID
     * @param $order_id_list 订单ID数组
     * @return array
     */
    public static function checkUserOrder($passengerId, $order_id_list){
        $rs = order::find()->select(["id","passenger_info_id"])->filterWhere(['in', 'id', $order_id_list])->asArray()->all();
        if(empty($rs)){
            return ['code'=>-7, 'message'=>'不存在的订单'];
        }
        foreach ($rs as $k => $v){
            if($passengerId != $v['passenger_info_id']){
                return ['code'=>-8, 'message'=>'非拥有的订单'];
            }
        }
        return ['code'=>0, 'message'=>''];
    }

    /**
     * 乘客提交申请开发票
     */
    public static function add($requestData){
        $order_id_list = $requestData['order_id_list'];
        $checkUserOrder = self::checkUserOrder($requestData['passenger_info_id'], $order_id_list);
        if($checkUserOrder['code']<0){
            return $checkUserOrder;
        }
        /**
        $OrderPayment = OrderPayment::find()->select(["sum(paid_price) as total_price"])->FilterWhere(['in', 'order_id', $order_id_list])->asArray()->one();
        if(!isset($OrderPayment['total_price'])){
            return ['code'=>-6, 'message'=>'订单可开票总额为0'];
        }
        */
        $requestData['invoice_price'] = 0;
        foreach ($order_id_list as $k => $v){
            $requestData['invoice_price'] += Order::getPayCapital($v);
        }
        if($requestData['invoice_price']<=0){
            return ['code'=>-6, 'message'=>'订单可开票总额为0'];
        }

        $requestData['order_id_list'] = implode(",", $requestData['order_id_list']);
        $model = new InvoiceRecord();
        $model->load($requestData, '');
        if (!$model->validate()){
            //echo $model->getFirstError();exit;
            \Yii::info($model->getFirstError(), "add Invoice 1");
            return ['code'=>-1, 'message'=>$model->getFirstError()];
            //return false;
        }else{
            //验证正确以后，判断orderId是否可开发票
            $model_order = new order();
            $test_ids = $model_order->find()->select(['id'])
                ->andFilterWhere(['in', 'id', $order_id_list])
                ->andFilterWhere(['in', 'invoice_type', [2,3,4]])
                ->asArray()->all();
            if(!empty($test_ids)){
                return ['code'=>-2, 'message'=>'订单ID中有已开票状态'];
            }
            if($model->save()){
                //将订单状态更改为开票中
                $model_order = new order();
                $rs = $model_order->updateAll(["invoice_type"=>2], ["in", "id", $order_id_list]);
                if($rs===false){
                    \Yii::info($rs, "add Invoice 4");
                    return ['code'=>-3, 'message'=>'订单开票状态更新失败'];
                }else{
                    return ['code'=>0, 'data'=>$model->attributes['id']];
                }
            }else{
                \Yii::info($model->getFirstError(), "add Invoice 2");
                return ['code'=>-4, 'message'=>'订单保存失败'];
            }
        }
    }

}
