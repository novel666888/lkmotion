<?php
namespace common\logic;

use common\models\City;
use common\models\InvoiceRecord;
use common\models\Order;
use common\models\OrderRulePrice;
use common\models\PassengerInfo;
use common\util\Cache;
use common\util\Common;
use common\models\PassengerWalletRecord;
use common\services\traits\ModelTrait;

class InvoiceLogic
{
    use ModelTrait;

    /**
     * 获取充值、退款记录列表
     *
     * @param $trade_type
     * @param $requestData
     * @return mixed
     * @throws \yii\base\UserException
     */
    public static function getRechargeOrRefundList($trade_type, $requestData){
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
        $new_query = PassengerWalletRecord::find()->select(['id','passenger_info_id','transaction_id','pay_time','pay_capital','pay_give_fee','refund_capital',
            'refund_give_fee','recharge_discount','pay_type','trade_type','trade_reason','order_id','pay_status'])->where(['trade_type'=>$trade_type]);
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
            $new_query->andFilterWhere(['>=','pay_time',$requestData['start_time']]);
        }
        if(!empty($requestData['end_time'])){
            $requestData['end_time'] = date("Y-m-d 23:59:59", strtotime($requestData['end_time']));
            $new_query->andFilterWhere(['<=','pay_time',$requestData['end_time']]);
        }
        $rechargeList = static::getPagingData($new_query, ['type'=>'desc', 'field'=>'create_time']);
        //取乘客明文手机号
        $passengerIds = array_unique(array_column($rechargeList['data']['list'], 'passenger_info_id'));
        $passengerIdArr = PassengerInfo::find()->select(['id'])->where(['id'=>$passengerIds])->asArray()->all();
        $Phones = Common::getPhoneNumber($passengerIdArr, 1);
        $passengerPhones = array_column($Phones, 'phone','id');
        if (!empty($rechargeList['data']['list'])){
            foreach ($rechargeList['data']['list'] as $key=>$value){
                $rechargeList['data']['list'][$key]['passenger_name'] = !empty($passengerInfo[$value['passenger_info_id']]['passenger_name']) ? $passengerInfo[$value['passenger_info_id']]['passenger_name'] : '';
                $rechargeList['data']['list'][$key]['passenger_type'] = !empty($passengerInfo[$value['passenger_info_id']]['passenger_type']) ? $passengerInfo[$value['passenger_info_id']]['passenger_type'] : '';
                $rechargeList['data']['list'][$key]['phone_number'] = !empty($passengerPhones[$value['passenger_info_id']]) ? $passengerPhones[$value['passenger_info_id']] : '';
                if ($value['trade_type'] == 1 && $value['pay_type'] == 3){
                    $rechargeList['data']['list'][$key]['recharge_discount'] = '0.00';
                }
            }
        }
        return $rechargeList['data'];
    }


    /**
     * 获取发票列表
     *
     * @param $requestData
     * @param bool $returnPageInfo
     * @param array $select
     * @return mixed
     * @throws \yii\base\UserException
     */
    public static function getInvoiceList($requestData, $returnPageInfo=true, $select=['*']){
        $query = InvoiceRecord::find();
        $query->select($select);
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
            $query->andFilterWhere(['>=','create_time',$requestData['createTimeStart']]);
        }
        if(!empty($requestData['createTimeEnd'])){
            $requestData['createTimeEnd'] = date("Y-m-d 23:59:59", strtotime($requestData['createTimeEnd']));
            $query->andFilterWhere(['<=','create_time',$requestData['createTimeEnd']]);
        }
        if(!empty($requestData['passengerId'])){
            $query->andFilterWhere(['passenger_info_id'=>intval($requestData['passengerId'])]);
        }
        $invoiceList = self::getPagingData($query, ['type'=>'desc', 'field'=>'create_time'], $returnPageInfo);
        //不带分页
        if($returnPageInfo==false){
            if(empty($invoiceList)){
                return '无发票';
            }else{
                //保证输出格式一致
                return array("list"=>$invoiceList['data']);
            }
        }
        //带分页
        $list = $invoiceList['data']['list'];
        if (!empty($list)){
            //获取明文手机号
            $passengerIds = array_unique(array_column($list, 'passenger_info_id'));
            $passengerIdArr = PassengerInfo::find()->select(['id'])->where(['id'=>$passengerIds])->asArray()->all();
            $Phones = Common::getPhoneNumber($passengerIdArr, 1);
            $passengerPhones = array_column($Phones, 'phone','id');

            $passengerIds = array_column($list, 'passenger_info_id');
            $passengerMsg = PassengerInfo::find()->select(['id','passenger_name','phone'])->where(['id'=>$passengerIds])->indexBy('id')->asArray()->all();
            foreach ($list as $key=>$value){
                $list[$key]['passenger_name'] = !empty($passengerMsg[$value['passenger_info_id']]['passenger_name']) ? $passengerMsg[$value['passenger_info_id']]['passenger_name'] : '';
                $list[$key]['phone_number'] = !empty($passengerPhones[$value['passenger_info_id']]) ? $passengerPhones[$value['passenger_info_id']] : '';
            }
            $invoiceList['data']['list'] = $list;
        }
        return $invoiceList['data'];
    }


    /**
     * 发票详情
     *
     * @param $invoiceId
     * @param null $passengerId
     * @return array|bool|\yii\db\ActiveQuery|\yii\db\ActiveRecord[]
     */
    public static function invoiceDetail($invoiceId, $passengerId=null){
        if(!self::checkInvoice($invoiceId)){
            return false;
        }
        $invoiceDetail = Cache::get('invoice_record', $invoiceId);
        if (empty($invoiceDetail['invoice_record_'.$invoiceId])){//若缓存无数据，重新取数据并添加缓存
            $invoiceDetail = InvoiceRecord::find();
            if(!empty($invoiceId)){
                $invoiceDetail->andFilterWhere(['id'=>$invoiceId]);
            }
            if(!empty($passengerId)){
                $invoiceDetail->andFilterWhere(['passenger_info_id'=>$passengerId]);
            }
            $invoiceDetail = $invoiceDetail->indexBy('id')->asArray()->all();
            Cache::set('invoice_record', $invoiceDetail, 0);
        }
        if (!empty($invoiceDetail)){
            foreach ($invoiceDetail as $k=>$v){
                $orderListArr = explode(",", $v['order_id_list']);
                $orderInfo = Order::find()->select(['id','service_type','receive_passenger_time','start_address','end_address','memo','order_start_time','passenger_getoff_time'])->where(['id'=>$orderListArr])->asArray()->all();
                $orderIds = array_unique(array_column($orderInfo, 'id'));
                $orderDetail = OrderRulePrice::find()->select(['order_id','total_distance','total_price','city_code'])->where(['order_id'=>$orderIds,'category'=>1])->indexBy('order_id')->asArray()->all();
                $cityCode = array_unique(array_column($orderDetail, 'city_code'));
                $cityCodeArr = City::find()->select(['city_code','city_name'])->where(['city_code'=>$cityCode])->asArray()->all();
                $cityList = array_column($cityCodeArr, 'city_name','city_code');
                if (!empty($orderInfo)){
                    foreach ($orderInfo as $key=>$value){
                        $orderInfo[$key]['total_distance'] = !empty($orderDetail[$value['id']]['total_distance']) ? $orderDetail[$value['id']]['total_distance'] : '';
                        $orderInfo[$key]['total_price'] = !empty($orderDetail[$value['id']]['total_price']) ? $orderDetail[$value['id']]['total_price'] : '';
                        $orderInfo[$key]['city'] = !empty($cityList[$orderDetail[$value['id']]['city_code']]) ? $cityList[$orderDetail[$value['id']]['city_code']] : '';
                    }
                }
                $passengerInfo = PassengerInfo::fetchOne(['id'=>$v['passenger_info_id']],['passenger_name','phone']);
                $invoiceDetail[$k]['passenger_name'] = $passengerInfo['passenger_name'];
                $phoneNUmber = Common::getPhoneNumber([['id'=>$v['passenger_info_id']]], 1);
                $invoiceDetail[$k]['phone_number'] = $phoneNUmber[0]['phone'];
                $invoiceDetail[$k]['orderCount'] = count($orderListArr);
                $invoiceDetail[$k]['orderList'] = $orderInfo;
            }
        }
        return $invoiceDetail;
    }

    /**
     * 检查发票是否存在
     *
     * @param int $invoiceId
     * @return boolean
     */
    public static function checkInvoice($invoiceId, $status=0){
        $query = InvoiceRecord::find()->where(['id'=>$invoiceId]);
        if ($status > 0){
            $query->andWhere(['invoice_status'=>2]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return false;
        }
        return true;
    }
}