<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/14
 * Time: 11:02
 */

namespace application\modules\order\controllers;

use application\modules\order\components\traits\ChangeOrderRecordTrait;
use application\modules\order\models\OrderAdjustRecord;
use application\modules\order\models\OrderBoss;
use application\modules\order\models\OrderCancelRecord;
use application\modules\order\models\OrderDoubt;
use application\modules\order\models\OrderGiftCouponRecord;
use application\modules\order\models\OrderReassignmentRecord;
use application\modules\order\models\TagInfo;
use common\logic\order\OrderOutputServiceTrait;
use common\logic\order\OrderTrajectoryTrait;
use common\models\City;
use common\models\ServiceType;
use common\models\TagRuleInfo;
use common\services\traits\PublicMethodTrait;
use common\util\Common;
use common\util\Json;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
class OrderManageController extends BossBaseController
{
    use PublicMethodTrait;
    use ChangeOrderRecordTrait;
    use OrderOutputServiceTrait;
    use OrderTrajectoryTrait;

    public $mapping;

    public function beforeAction($action)
    {
        $this->mapping['reason_type'] = ['1'=>'重复下单','2'=>'出行计划有变', '3'=>'司机态度差', '4'=>'司机要求取消', '5'=>'其他原因'];

        return parent::beforeAction($action);
    }

    /**
     * index
     *
     * @return array
     */
    public function actionIndex()
    {
        return Json::success();
    }

    /**
     * @return array|mixed
     */

    private function _getOderId()
    {
        $request = $this->getRequest();
        $orderId = $request->post('orderId');
        return $orderId;
    }

    /**
     * @return \yii\web\Response
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetOrderList()
    {
        $request = $this->getRequest();
        $where   = $request->post('queryInfo');
        $result  = OrderBoss::getOrderList($where, ['create_time' => SORT_DESC]);

        return $this->asJson($result);
    }

    /**
     * @return array|mixed
     */

    public function actionGetOrderDetail()
    {
        //var_dump($this->getPassengerTrail(1));exit;
        try {
            $orderDetail = OrderBoss::getOrderDetail($this->_getOderId());
        } catch (UserException $ex) {
            return $this->renderErrorJson($ex);
        }

        return Json::success($orderDetail);
    }

    /**
     * @return \yii\web\Response
     * @throws UserException
     */
    public function actionAdjustAccountList()
    {
        $orderId    = intval($this->_getOderId());
        $returnData = OrderAdjustRecord::getAdjustList($orderId);

        return $this->asJson($returnData);
    }

    /**
     * @return \yii\web\Response
     * @throws UserException
     */

    public function actionCancelOrderRecordList()
    {
        $orderId    = intval($this->_getOderId());
        $returnData = OrderCancelRecord::getOrderCancelOrderList($orderId);

        return $this->asJson($returnData);
    }

    /**
     * order assignment record lists
     *
     * @return \yii\web\Response
     * @throws UserException
     */

    public function actionOrderReassignmentRecordList()
    {
        $orderId = (int)$this->_getOderId();
        //$returnData = OrderReassignmentRecord::getReassignmentList($orderId);
        $returnData = $this->getChangeOrderRecordList($orderId, OrderReassignmentRecord::className());

        return $this->asJson($returnData);

    }

    /**
     * 疑义定单列表
     *
     * @return \yii\web\Response
     */

    public function actionGetDoubtOrderList()
    {
        $request    = $this->getRequest();
        $where      = $request->post('queryInfo');
        $returnData = OrderDoubt::getDoubtOrders($where);

        return $this->asJson($returnData);
    }

    /**
     * 取消订单列表
     */

    public function actionGetCancelOrderList()
    {
        $request    = $this->getRequest();
        $whereData  = $request->post('queryInfo');
        $cabRunnerPhone = (string)Common::phoneEncrypt(trim(ArrayHelper::getValue($whereData,'cabRunnerPhone','')));
        $driverPhone = (string)Common::phoneEncrypt(trim(ArrayHelper::getValue($whereData,'driverPhone','')));
        $orderNum = ArrayHelper::getValue($whereData,'orderNum','');
        $reasonType = ArrayHelper::getValue($whereData,'reasonType');
        if($reasonType == 0){
            $reasonType = "";
        }
        $where  = compact('cabRunnerPhone','driverPhone','orderNum','reasonType');
        $returnData = OrderBoss::getCancelOrders($where);

        foreach ($returnData['data']['list'] as &$v){
            if (isset($v)) {
                if ($v['reasonType']) {
                    $v['reasonText'] = $this->mapping['reason_type'][$v['reasonType']] . ($v['reasonType'] == 4 ? '：' . $v['reasonText'] : '');
                } else {
                    $v['reasonText'] = "";
                }
            }
        }

        return $this->asJson($returnData);
    }

    /**
     * 待派单
     *
     * @return array|mixed
     */

    /**
     * @return array|mixed
     * @throws UserException
     */

    public function actionGetPendingOrderList()
    {
        $page           = (int)$this->getPostParam('page', 1);
        $pageSize       = (int)$this->getPostParam('pageSize');
        $orderNum       = $this->getPostParam('orderNum');
        $cabRunnerPhone = (string)Common::phoneEncrypt(trim($this->getPostParam('cabRunnerPhone')));
        $carManPhone    = (string)Common::phoneEncrypt(trim($this->getPostParam('carManPhone')));
        $whereData      = compact('orderNum', 'cabRunnerPhone', 'carManPhone');
        try {
            $returnData = OrderBoss::getPendingOrders($whereData);
            $returnData = array_values($returnData);
            $totalCount = sizeof($returnData);

            $finalData = [
                'list'     => array_slice($returnData, ($page - 1) * $pageSize, $pageSize),
                'pageInfo' => [
                    'page'      => $page,
                    'pageCount' => ceil($totalCount / $pageSize),
                    'pageSize'  => $pageSize,
                    'total'     => $totalCount,
                ]
            ];
        } catch (UserException $ex) {
            return $this->renderErrorJson($ex);
        }

        return Json::success($finalData);
    }

    /**
     * @throws UserException
     * @throws \yii\base\InvalidConfigException
     */

    public function actionGetDrivingTrack()
    {
        try{
            $request = $this->getRequest();
            $orderId = $request->post('orderId');
            $data = $this->getAllTrajectoryByOrderId($orderId);

            $this->renderJson($data);
        }catch (UserException $e){
            $this->renderJson($e);
        }
    }

    /**
     * @return \yii\web\Response
     * @throws UserException
     */

    public function actionGetGiftCouponRecord()
    {
        $request = $this->getRequest();
        $orderId = $request->post('orderId');
        $data = OrderGiftCouponRecord::getGiftCouponList($orderId);
        return $this->asJson($data);
    }

    /**
     * 订单场景列表
     *
     * @return array
     */
    public function actionGetOrderScenario()
    {

        $extraScenario = [
            [
                'value' => '1002',
                'optionName' => '二个场景'
            ],
            [
                'value' => '1003',
                'optionName' => '三个场景'
            ],
            [
                'value' => '1004',
                'optionName' => '四个场景'
            ],
            [
                'value' => '1005',
                'optionName' => '五个场景'
            ],
            [
                'value' => '1006',
                'optionName' => '六个场景'
            ],
            [
                'value' => '1007',
                'optionName' => '七个场景'
            ],
            [
                'value' => '1008',
                'optionName' => '八个场景'
            ],
        ];
        $tagsArr = TagInfo::find()
            ->select(['value'=>'id','optionName'=>'tag_name'])
            ->distinct()
            ->asArray()
            ->all();
        $tagsCount = sizeof($tagsArr);
        $extraScenario = array_slice($extraScenario,0,$tagsCount-1);
        $scenarioData = ArrayHelper::merge($tagsArr,$extraScenario);

        return Json::success(['list'=>$scenarioData]);
    }

    /**
     * 获取城市列表
     *
     * @return array
     */

    public function actionGetCityList()
    {
        $cityList = City::getCityList($condition=null, $field=['city_name as cityName','city_code as cityCode'], $sort=null, $returnPageInfo=false);
        return Json::success($cityList);
    }

    /**
     * @return array
     */

    public function actionGetServiceType()
    {
        $serviceType = ServiceType::find()
            ->select(['serviceTypeId'=>'id','serverTypeName'=>'service_type_name'])
            ->orderBy('id')
            ->asArray()
            ->all();

        return Json::success($serviceType);
    }

}