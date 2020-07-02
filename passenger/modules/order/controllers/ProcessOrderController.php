<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/22
 * Time: 21:04
 */
namespace passenger\modules\order\controllers;

use common\controllers\BaseController;
use common\controllers\ClientBaseController;
use common\models\CarInfo;
use common\models\CarLevel;
use common\models\CarType;
use common\models\DriverInfo;
use common\models\OrderPayment;
use common\services\CConstant;
use passenger\models\OrderCancelRecord;
use passenger\models\OrderRulePrice;
use common\util\Common;
use passenger\models\EvaluateDriver;
use passenger\models\OrderDoubt;
use passenger\models\PassengerHistoryAddress;
use passenger\models\PassengerHistoryCarPerson;
use common\services\traits\PublicMethodTrait;
use common\util\Json;
use passenger\models\Order;
use passenger\services\CheckParamsAuthTrait;
use passenger\services\OrderDetailStatusTrait;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\base\UserException;
use yii\db\Expression;
use common\logic\order\OrderTrajectoryTrait;
use common\models\EvaluateCarscreen;
use yii\helpers\ArrayHelper;

class ProcessOrderController extends ClientBaseController
{
    use PublicMethodTrait,OrderDetailStatusTrait,OrderTrajectoryTrait,CheckParamsAuthTrait;

    //返回评价标签
    public function actionCommentLabel(){
        $bq = \Yii::$app->params['PassengerCommentDriver'];
        foreach ($bq as $k => $v){
            $return[] = [
                'id'=>$k,
                'name'=>$v,
            ];
        }
        return Json::success($return);
    }

    /**
     * @return array|mixed
     * @throws \yii\db\Exception
     */

    public function actionEvalDriver()
    {
        $request = $this->getRequest();
        $passenger_id = $this->userInfo['id'];
        $orderId = intval($request->post('orderId'));
        $grade = intval($request->post('grade'));
        $label = trim($request->post('label'));
        $content = trim($request->post('content',''));
        $largescreen_grade = trim($request->post('largescreenGrade'));
        $largescreen_content = trim($request->post('largescreenContent',''));
        $trans = $this->beginTransaction();
        try{

            if(empty($passenger_id) || empty($grade) || empty($orderId) || empty($label) || empty($largescreen_grade)){
                throw new InvalidConfigException('Params error!',1001);
            }
            $orderTable = Order::getOne(['id'=>$orderId,'passenger_info_id'=>$passenger_id]);
            if(!$orderTable){
                throw new \RuntimeException('Data error!',999);
            }
            if($orderTable->is_evaluate == 1){
                throw new UserException('评论已提交!',1003);
            }
            $driver_id = $orderTable->driver_id;
            //乘客评价司机
            $evalDriverModel = new EvaluateDriver;
            $insertSets = [
                'order_id'=>$orderId,
                'passenger_id'=>$passenger_id,
                'grade'=>$grade,
                'label'=>$label,
                'content'=>$content,
                'driver_id'=>$driver_id,
            ];
            $insertSets = Common::filterNull($insertSets);
            $evalDriverModel->setAttributes($insertSets);
            if(!$evalDriverModel->save()){
                throw new UserException($evalDriverModel->getFirstError(),1002);
            }

            //乘客评价大屏
            $EvaluateCarscreen = new EvaluateCarscreen;
            $_insertSets = [
                'order_id'=>$orderId,
                'passenger_id'=>$passenger_id,
                'grade'=>$largescreen_grade,
                'content'=>$largescreen_content,
                'car_id'=>$orderTable->car_id,
            ];
            $_insertSets = Common::filterNull($_insertSets);
            $EvaluateCarscreen->setAttributes($_insertSets);
            if(!$EvaluateCarscreen->save()){
                throw new UserException($EvaluateCarscreen->getFirstError(),1002);
            }

            /************将订单改为已经评价状态****************/
            $orderTable->is_evaluate = 1;
            if(!$orderTable->save(false)){
                throw new UserException($orderTable->getFirstError(),1003);
            }
            /**************************end eval*************************/
            $doReportData = \Yii::$app->params['doReportData'];
            if($doReportData){
                if($grade<=1){
                    Common::ratedPassengerComplaint($orderId);
                }else{
                    Common::ratedPassenger($orderId);
                }
            }
            $trans->commit();

            return Json::success();
        }catch (UserException $exception){
            $trans->rollBack();

            return $this->renderErrorJson($exception);
        }catch (\Throwable $ex){
            $trans->rollBack();
            \Yii::error($ex->getMessage(),__METHOD__);

            return Json::error([],1,CConstant::SERVER_EXCEPTION_TEXT);
        }


    }

    /**
     * @return array|mixed
     * @throws InvalidConfigException
     */

    public function actionDoubtOrder()
    {
        $request = $this->getRequest();
        try{
            $orderId = $request->post('orderId');
            $doubtType = $request->post('doubtType');
            if(empty($orderId) || empty($doubtType)){
                throw new InvalidConfigException('Params error!',1001);
            }
            if(!$this->checkOrderIdBelongToUser($this->userInfo['id'],$orderId)){
                throw new \RuntimeException('Data error',1000);
            }
            $orderDoubtTable = OrderDoubt::find()
                ->where(['and', ['=','order_id',$orderId], ['<','status',OrderDoubt::STATUS_FINISH]])
                ->one();
            if($orderDoubtTable){
                throw new UserException('已经提交过疑义订单',1002);
            }
            $appealNum = date('YmdHis').mt_rand(1000,9999);
            $orderDoubtModel = new OrderDoubt;
            $orderDoubtModel->setAttributes([
                'order_id' => $orderId,
                'appeal_number'=>$appealNum,
                'reason_type'=> $doubtType,
                'operate_time'=> date('Y-m-d H:i:s'),
            ]);
            if(!$orderDoubtModel->save()){
                throw new UserException($orderDoubtModel->getFirstError());
            }

            return Json::success('');
        }catch (UserException $exception){

            return $this->renderErrorJson($exception);
        }
    }

    /**
     * @return array
     */

    public function actionGetDoubtType()
    {
        $doubtType = $this->module->params['doubtType'];
        $data = array();
        foreach ($doubtType as $k=>$v){
            $data[]=['id'=>$k,'doubtText'=>$v];
        }
        return Json::success(['list'=>$data]);
    }

    /**
     * @return array|mixed
     * @throws InvalidConfigException
     */

    public function actionDeleteHistoryPlace()
    {
        $request = $this->getRequest();
        //$placeType = (int)$request->post('placeType');
        $placeId = (int)$request->post('placeId');
        try{

            if(empty($placeId)){
                throw new InvalidConfigException('Params error!',1001);
            }
            $passengerHistoryAddressTable = PassengerHistoryAddress::findOne(['id'=>$placeId,'passenger_info_id'=>$this->userInfo['id']]);
            if(!$passengerHistoryAddressTable){
                throw new \RuntimeException('Access denied!',1003);
            }
            if(!is_array($placeId)){
                $placeId = [$placeId];
            }
            $affect = PassengerHistoryAddress::updateAll(['is_del'=>PassengerHistoryAddress::DEL_YES,],['id'=>$placeId]);
            if(!$affect){
                throw new \RuntimeException('Failed to delete',1002);
            }

            return Json::success('');
        }catch (\Exception $exception) {
            \Yii::error($exception->getMessage(),__METHOD__);
            return Json::error([],1,CConstant::SERVER_EXCEPTION_TEXT);
        }
    }

    /**
     * @return array|mixed
     * @throws InvalidConfigException
     */

    public function actionDeleteHistoryRider()
    {
        $riderId = $this->getPostParam('riderId');
        try{
            if(empty($riderId)){
                throw new InvalidConfigException('Params error!',1001);
            }
            $passengerHistoryCarPersonTable = PassengerHistoryCarPerson::findOne(['id'=>$riderId,'passenger_info_id'=>$this->userInfo['id']]);
            if(!$passengerHistoryCarPersonTable){
                throw new \RuntimeException('Access denied!',1003);
            }
            if(!is_array($riderId)){
                $riderId = [$riderId];
            }
            $affect = PassengerHistoryCarPerson::updateAll(['is_del'=>PassengerHistoryCarPerson::DEL_YES], ['id'=>$riderId]);
            if(!$affect){
                throw new \RuntimeException('Failed to delete!',1002);
            }

            return Json::success('');

        }catch (\Exception $exception){
            \Yii::error($exception->getMessage(),__METHOD__);
            return Json::error([],1,CConstant::SERVER_EXCEPTION_TEXT);
        }
    }

    /**
     * @return array|\yii\console\Response|\yii\web\Response
     * @throws \Exception
     */

    public function actionGetOrderDetail()
    {
        $request = $this->getRequest();
        $orderId = (int)$request->post('orderId');
        $ossUrl = \Yii::$app->params['ossFileUrl'];
        /**@vad \common\models\BaseModel $orderActiveRecord */
        try {
            if(empty($orderId)){
                throw new InvalidValueException('Params error!',1000);
            }
            $orderData = Order::fetchOne(['id' => $orderId,'passenger_info_id'=>$this->userInfo['id']], [
                'orderId'=>'id',
                'carId' => 'car_id',
                'plateNumber' => 'plate_number',
                'driverStatus' => 'driver_status',
                'orderStatus' => 'status',
                'driverId' => 'driver_id',
                'startTime' => 'start_time',
                'orderType'=> 'order_type',
                'serviceType'=> 'service_type',
                'orderStartTime'=>'order_start_time',
                'startLongitude' => 'start_longitude',
                'startLatitude' => 'start_latitude',
                'startAddress' => 'start_address',
                'endLongitude' => 'end_longitude',
                'endLatitude' => 'end_latitude',
                'endAddress' => 'end_address',
                'isEvaluate'=>'is_evaluate',
                'pickUpPassengerLongitude' => 'pick_up_passenger_longitude',
                'pickUpPassengerLatitude' => 'pick_up_passenger_latitude',
                'pickUpPassengerAddress' => 'pick_up_passenger_address',
                'receivePassengerTime' => 'receive_passenger_time',
                'receivePassengerLongitude' => 'receive_passenger_longitude',
                'receivePassengerLatitude' => 'receive_passenger_latitude',
                'passengerGetOffTime' => 'passenger_getoff_time',
                'passengerGetOffLongitude' => 'passenger_getoff_longitude',
                'passengerGetOffLatitude' => 'passenger_getoff_latitude',
                'orderGrade'=>new Expression('space(-1)'),//@todo
                'driverMappingPhoneNum'=>'mapping_number',
            ]);
            if(!$orderData){
                throw new  \RuntimeException('Data error!',1001);
            }

            $orderData['startTime'] = intval(strtotime($orderData['startTime']))*1000;
            $orderData['orderStartTime'] = intval(strtotime($orderData['orderStartTime']))*1000;
            $orderGradeFromEvalDriver  = EvaluateDriver::findOne(['order_id'=>$orderId]);
            if(!empty($orderGradeFromEvalDriver)){
                $orderData['orderGrade'] = $orderGradeFromEvalDriver->grade;  //订单星评
            }

            /********************************司机星评************************/
            $driverGrade = 0;
            if(!empty($orderData['driverId'])){
                $evalDriver  = EvaluateDriver::find()->where('driver_id=:driverId',[':driverId'=>$orderData['driverId']])
                    ->select('grade')->column();
                $driverGrade = empty($evalDriver)?0:array_sum($evalDriver)/count($evalDriver);
            }

            /*******************************end*******************************/
            $orderData['detailStatus'] = $this->_getOrderState($orderId);
            $carInfo    = CarInfo::fetchOne(['id' => $orderData['carId']], [
                'carName' => 'full_name',
                'carColor' => 'color',
                'plateNumber'=>'plate_number',
                //'carImg' => 'car_img',
                'carTypeId'=>'car_type_id',
                'carLevelId'=>'car_level_id',
            ]);


            if($carInfo){
                $carInfo['carLevel']=CarLevel::fetchFieldBy(['id'=>$carInfo['carLevelId']],'label');
                $carTypeInfo= CarType::fetchOne(['id'=>$carInfo['carTypeId']],
                    [
                        'type'=>'type_desc',
                        'carImg'=>'img_url',
                    ]
                );
                if(!empty($carTypeInfo['carImg'])){
                    $carTypeInfo['carImg'] = $ossUrl . $carTypeInfo['carImg'];
                }else{
                    $carTypeInfo['carImg'] = '';
                }
                $carInfo = ArrayHelper::merge($carInfo,$carTypeInfo);
            }


            $driverInfo = DriverInfo::fetchOne(['id' => $orderData['driverId']], [
                'driverPhone' => 'phone_number',
                'driverName' => 'driver_name',
                'headImg' => 'head_img',
                'gender' => 'gender',
                'driverGrade'=>new Expression('space(-1)'),//@todo
            ]);

            if($driverInfo){
                if(!empty($driverInfo['headImg'])){
                    $driverInfo['headImg'] = $ossUrl . $driverInfo['headImg'];
                }
                $driverInfo['driverGrade'] = strval(round($driverGrade,1));
                if($driverInfo['driverGrade'] == '0'){
                    $driverInfo['driverGrade'] = '5';
                }
                if(empty($orderData['driverMappingPhoneNum'])){ // 如小号为空,则是司机加密的的号,需要解密
                    $driverInfo['driverPhone'] = Common::decryptCipherText($driverInfo['driverPhone'],true);
                }else{
                    $driverInfo['driverPhone']= $orderData['driverMappingPhoneNum'];
                }

            }
            $drivingTrack = $this->getOrderTrajectoryByOrderId($orderId);
            if(!is_array($drivingTrack)){
                $drivingTrack = '';
            }
            $rulePrice = OrderRulePrice::findOne(['order_id'=>$orderId,'category'=>1]);
            $rulePriceForecast = OrderRulePrice::findOne(['order_id'=>$orderId,'category'=>0]);
            $orderPrice = empty($rulePrice)?$rulePriceForecast->total_price:$rulePrice->total_price;
            $payment = OrderPayment::findOne(['order_id'=>$orderId]);
            if(empty($payment)){
                $paidPrice = $remainPrice = '0.00';
            }else{
                $orderPrice = (string)$payment->final_price; // 定单价格减去优惠券的金额
                $paidPrice = (string)$payment->paid_price;
                $remainPrice = (string)$payment->remain_price;
            }
            $allData    = compact('orderData', 'carInfo', 'driverInfo','drivingTrack','orderPrice','paidPrice','remainPrice');
            return Json::success($allData);
        } catch (UserException $exception) {
            return $this->renderJson($exception);
        } catch (\Exception $exception){
            \Yii::error($exception->getMessage(),__METHOD__);
            return Json::error([],1,CConstant::SERVER_EXCEPTION_TEXT);
        }

    }

}