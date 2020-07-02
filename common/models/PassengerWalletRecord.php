<?php

namespace common\models;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Yii;
use PHPExcel;
use PHPExcel_IOFactory;
use common\services\YesinCarHttpClient;
use common\util\Common;
use common\services\traits\ModelTrait;
use common\logic\InvoiceLogic;
/**
 * This is the model class for table "{{%passenger_wallet_record}}".
 *
 * @property int $id
 * @property int $passenger_info_id 用户ID
 * @property string $transaction_id 第三方支付ID
 * @property string $pay_time 支付时间
 * @property double $pay_capital 本金
 * @property double $pay_give_fee 赠费
 * @property double $refund_capital 退款本金
 * @property double $refund_give_fee 退款赠费
 * @property double $recharge_discount
 * @property int $pay_type 1：微信 ，2：账户余额，3：平台账户，4：支付宝
 * @property int $pay_status 1：已支付 ，0：未支付
 * @property int $trade_type 交易类型：1充值， 2消费,3退款
 * @property string $trade_reason 交易原因
 * @property string $description 描述
 * @property string $create_user 创建用户
 * @property int $order_id
 * @property string $create_time 创建时间
 * @property string $update_time
 */
class PassengerWalletRecord extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%passenger_wallet_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'pay_time', 'pay_capital', 'pay_give_fee', 'recharge_discount', 'pay_type', 'description', 'create_time'], 'required'],
            [['passenger_info_id', 'pay_type', 'pay_status', 'trade_type', 'order_id'], 'integer'],
            [['pay_time', 'create_time', 'update_time'], 'safe'],
            [['pay_capital', 'pay_give_fee', 'refund_capital', 'refund_give_fee', 'recharge_discount'], 'number'],
            [['transaction_id'], 'string', 'max' => 32],
            [['trade_reason'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 200],
            [['create_user'], 'string', 'max' => 20],
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
            'transaction_id' => 'Transaction ID',
            'pay_time' => 'Pay Time',
            'pay_capital' => 'Pay Capital',
            'pay_give_fee' => 'Pay Give Fee',
            'refund_capital' => 'Refund Capital',
            'refund_give_fee' => 'Refund Give Fee',
            'recharge_discount' => 'Recharge Discount',
            'pay_type' => 'Pay Type',
            'pay_status' => 'Pay Status',
            'trade_type' => 'Trade Type',
            'trade_reason' => 'Trade Reason',
            'description' => 'Description',
            'create_user' => 'Create User',
            'order_id' => 'Order ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 判断指定的冻结流水是否已解冻
     * @param int $recordId 冻结流水ID
     * @return bool true已解冻/false冻结
     */
    public static function checkThaw($passengerId, $orderId){
        if(empty($passengerId) || empty($orderId)){
            return false;
        }
        $model = self::find()->andFilterWhere(['passenger_info_id'=>$passengerId])
            ->andFilterWhere(['order_id'=>$orderId])
            ->andFilterWhere(['pay_status'=>1])
            ->andFilterWhere(['trade_type'=>7])->asArray()->one();
        if($model){
            return true;
        }else{
            return false;
        }
    }

	 /**
     * 根据查询条件，返回流水列表，带分页
     * @param  array $condition 筛选条件
     * @param  array $field     查询字段
     * @return array
     */
    public static function getFlowRecord($condition, $field=['*']){
        $model = self::find()->select($field);
        if(!empty($condition['tradeType'])){
            $model->andFilterWhere(['trade_type'=>$condition['tradeType']]);
        }
        if(!empty($condition['orderId'])){
            $model->andFilterWhere(['order_id'=>$condition['orderId']]);
        }
        if(!empty($condition['recordId'])){
            $model->andFilterWhere(['id'=>$condition['recordId']]);
        }
        if(!empty($condition['passengerId'])){
            $model->andFilterWhere(['passenger_info_id'=>intval($condition['passengerId'])]);
        }
        if(!empty($condition['payStatus'])){
            $model->andFilterWhere(['pay_status'=>intval($condition['payStatus'])]);
        }

        //添加判断条件
        //...
        $adPosition = self::getPagingData($model, ['type'=>'desc','field'=>'pay_time']);
        return $adPosition['data'];
    }

	
    /**
     * 导出excel
     * 
     * @param int $trade_type
     * @param array $requestData
     */
//    public static function outPutChargeExcel($trade_type, $requestData){
//        //设置内存
//        ini_set("memory_limit", "2048M");
//        set_time_limit(0);
//        //获取传过来的信息（时间，公司ID之类的，根据需要查询资料生成表格）
//        $objectPHPExcel = new PHPExcel();
//        //设置表格头的输出
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('A1', '逸品充值流水号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('B1', '用户姓名');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('C1', '用户手机号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('D1', '用户类型');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('E1', '充值时间');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('F1', '充值金额-本金');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('G1', '赠送金额-增费');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('H1', '充值折扣');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('I1', '充值渠道');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('J1', '是否支付');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('K1', '第三方流水号');
//        $objectPHPExcel->setActiveSheetIndex()->setCellValue('L1', '交易类型');
//        if ($trade_type == 3){
//            $objectPHPExcel->setActiveSheetIndex()->setCellValue('M1', '行程订单号');
//            $objectPHPExcel->setActiveSheetIndex()->setCellValue('N1', '退款原因');
//        }
//
//        //跳转到recharge这个model文件的statistics方法去处理数据
//        $data = self::_getData($trade_type, $requestData);
//        if (!empty($data)){
//            foreach ($data as $key=>$value){
//                $data[$key]['pay_type'] = ($value['pay_type'] == 1 ? '微信' : ($value['pay_type'] == 2 ? '账户余额' : ($value['pay_type']==3 ? '平台账户' : '支付宝')));
//                $data[$key]['pay_status'] = $value['pay_status'] == 1 ? '已支付' : '未支付';
//                $data[$key]['trade_type'] = ($value['trade_type'] == 1 ? '充值' : ($value['trade_type'] == 3 ? '退款' : '其他'));
//                $data[$key]['passenger_type'] = $value['passenger_type'] == 1 ? '个人用户' : '企业用户';
//            }
//        }
//        //指定开始输出数据的行数
//        $n = 2;
//        foreach ($data as $v){
//            $objectPHPExcel->getActiveSheet()->setCellValue('A'.($n) ,$v['id']);//序号
//            $objectPHPExcel->getActiveSheet()->setCellValue('B'.($n) ,$v['passenger_name']);//用户姓名
//            $objectPHPExcel->getActiveSheet()->setCellValue('C'.($n) ,$v['phone_number'].' ');//用户电话
//            $objectPHPExcel->getActiveSheet()->setCellValue('D'.($n) ,$v['passenger_type']);//用户类型
//            $objectPHPExcel->getActiveSheet()->setCellValue('E'.($n) ,$v['pay_time']);//充值时间
//            if ($trade_type == 1){
//                $objectPHPExcel->getActiveSheet()->setCellValue('F'.($n) ,$v['pay_capital']);//本金
//                $objectPHPExcel->getActiveSheet()->setCellValue('G'.($n) ,$v['pay_give_fee']);//赠费
//            }elseif ($trade_type == 3){
//                $objectPHPExcel->getActiveSheet()->setCellValue('F'.($n) ,$v['refund_capital']);//退款本金
//                $objectPHPExcel->getActiveSheet()->setCellValue('G'.($n) ,$v['refund_give_fee']);//退款赠费
//            }
//            $objectPHPExcel->getActiveSheet()->setCellValue('H'.($n) ,$v['recharge_discount']);//折扣
//            $objectPHPExcel->getActiveSheet()->setCellValue('I'.($n) ,$v['pay_type']);//支付方式
//            $objectPHPExcel->getActiveSheet()->setCellValue('J'.($n) ,$v['pay_status']);//是否支付
//            $objectPHPExcel->getActiveSheet()->setCellValue('K'.($n) ,$v['transaction_id'].' ');//第三方交易流水号
//            $objectPHPExcel->getActiveSheet()->setCellValue('L'.($n) ,$v['trade_type'].' ');//交易类型
//            if ($trade_type == 3){
//                $objectPHPExcel->getActiveSheet()->setCellValue('M'.($n) ,$v['order_id'].' ');//行程订单号
//                $objectPHPExcel->getActiveSheet()->setCellValue('N'.($n) ,$v['trade_reason'].' ');//退款原因
//            }
//            $n = $n +1;
//        }
//        ob_clean();
//        ob_start();
//        header('Content-Type:application/vnd.ms-excel');
//
//        //设置输出文件名及格式
//        if ($trade_type == 1){
//            $fileName = '充值记录';
//        }elseif ($trade_type == 3){
//            $fileName = '退款记录';
//        }
//        header("Content-Disposition:attachment;filename=".$fileName.date("YmdHis").".xls");
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
     * 导出充值、退款记录excel
     *
     * @param $trade_type
     * @param $requestData
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\base\UserException
     */
    public static function outPutChargeExcel($trade_type, $requestData){
        $data = self::_getData($trade_type, $requestData);
        if(empty($data)){
            throw new UserException('data empty');
        }
        $head = self::getValues($trade_type);
        $list = [$head];
        if (!empty($data)){
            foreach ($data as $key=>$value){
                $list[] = self::getValues($trade_type, $value);
            }
        }
        // 生成数据
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($list);

        if ($trade_type == 1){
            $fileName = '充值记录';
        }elseif ($trade_type == 3){
            $fileName = '退款记录';
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' .$fileName. date('YmdHi') . '.xlsx"');
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
     * 组装充值退款excel数据
     *
     * @param $trade_type
     * @param $value
     * @return array
     */
    private static function getValues($trade_type, $value=[]){
        $item = [];
        $item['id'] = isset($value['id']) ? $value['id'] : "逸品充值流水号";
        $item['passenger_name'] = isset($value['passenger_name']) ? $value['passenger_name'] : "用户姓名";
        $item['phone_number'] = isset($value['phone_number']) ? $value['phone_number'] : "用户手机号";
        $item['passenger_type'] = isset($value['passenger_type']) ? ($value['passenger_type'] == 1 ? '个人用户' : '企业用户') : "用户类型";
        $item['pay_time'] = isset($value['pay_time']) ? $value['pay_time'] : "充值时间";
        $item['pay_capital'] = isset($value['pay_capital']) ? $value['pay_capital'] : "充值金额-本金";
        $item['pay_give_fee'] = isset($value['pay_give_fee']) ? $value['pay_give_fee'] : "赠送金额-增费";
        if ($trade_type == 1 && isset($value['pay_type']) && $value['pay_type'] == 3){
            $item['recharge_discount'] = isset($value['recharge_discount']) ? '0' : "充值折扣";
        }else{
            $item['recharge_discount'] = isset($value['recharge_discount']) ? $value['recharge_discount'] : "充值折扣";
        }
        $item['pay_type'] = isset($value['pay_type']) ? ($value['pay_type'] == 1 ? '微信' : ($value['pay_type']==2 ? '账户余额' : ($value['pay_type']==3 ? '平台账户' : '支付宝'))) : "充值渠道";
        $item['pay_status'] = isset($value['pay_status']) ? ($value['pay_status'] == 1 ? '已支付' : '未支付') : "是否支付";
        $item['transaction_id'] = isset($value['transaction_id']) ? $value['transaction_id'] : "第三方流水号";
        $item['trade_type'] = isset($value['trade_type']) ? ($value['trade_type'] == 1 ? '充值' : ($value['trade_type']==2 ? '消费' : '退款')) : "交易类型";
        if ($trade_type == 3) {
            $item['order_id'] = isset($value['order_id']) ? $value['order_id'] : "行程订单号";
            $item['trade_reason'] = isset($value['trade_reason']) ? $value['trade_reason'] : "退款原因";
        }
        return $item;
    }

    /**
     * 获取充值、退款数据
     *
     * @param $trade_type
     * @param $requestData
     * @return array|\yii\db\ActiveRecord[]
     * @throws \yii\base\UserException
     */
    private static function _getData($trade_type, $requestData){
        if (!empty($requestData['phone_number']) || !empty($requestData['passenger_name'])){
            $query = PassengerInfo::find()->select(['id']);
            if (!empty($requestData['phone_number'])){
                $phoneNumber = Common::phoneNumEncrypt([$requestData['phone_number']]);//取密文手机号
                $query->andFilterWhere(['phone'=>$phoneNumber[0]['encrypt']]);
            }
            if (!empty($requestData['passenger_name'])){
                $query->andFilterWhere(['LIKE', 'passenger_name', $requestData['passenger_name']]);
            }
            $searchPassengerIds = $query->asArray()->all();
        }

        $passengerInfo = PassengerInfo::find()->select(['id','passenger_name','passenger_type'])->indexBy('id')->asArray()->all();
        $new_query = PassengerWalletRecord::find()->where(['trade_type'=>$trade_type]);
        if ($trade_type == 1){//充值记录选择已付款的
            $new_query->andWhere(['pay_status'=>1]);
        }
        if (!empty($searchPassengerIds)){
            $searchIds = array_unique(array_column($searchPassengerIds, 'id'));
            $new_query->andWhere(['IN','passenger_info_id',$searchIds]);
        }elseif (!empty($requestData['phone_number']) || !empty($requestData['passenger_name'])){
            return '暂无数据';
        }
        if (!empty($requestData['id'])){
            $new_query->andFilterWhere(['id'=>$requestData['id']]);
        }
        if (!empty($requestData['pay_type'])){
            $new_query->andFilterWhere(['pay_type'=>$requestData['pay_type']]);
        }
        if(!empty($requestData['start_time'])){
            $new_query->andFilterWhere(['>','pay_time',$requestData['start_time']]);
        }
        if(!empty($requestData['end_time'])){
            $new_query->andFilterWhere(['<','pay_time',$requestData['end_time']]);
        }
        $rechargeList = $new_query->asArray()->orderBy('create_time desc')->all();
        //取乘客明文手机号
        $passengerIds = array_unique(array_column($rechargeList, 'passenger_info_id'));
        $passengerIdArr = PassengerInfo::find()->select(['id'])->where(['id'=>$passengerIds])->asArray()->all();
        $Phones = Common::getPhoneNumber($passengerIdArr, 1);
        $passengerPhones = array_column($Phones, 'phone','id');
        if (!empty($rechargeList)){
            foreach ($rechargeList as $key=>$value){
                $rechargeList[$key]['passenger_name'] = !empty($passengerInfo[$value['passenger_info_id']]['passenger_name']) ? $passengerInfo[$value['passenger_info_id']]['passenger_name'] : '';
                $rechargeList[$key]['passenger_type'] = !empty($passengerInfo[$value['passenger_info_id']]['passenger_type']) ? $passengerInfo[$value['passenger_info_id']]['passenger_type'] : '';
                $rechargeList[$key]['phone_number'] = !empty($passengerPhones[$value['passenger_info_id']]) ? $passengerPhones[$value['passenger_info_id']] : '';
            }
        }
        return $rechargeList;
    }
}
