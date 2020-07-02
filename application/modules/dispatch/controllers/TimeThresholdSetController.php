<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchTimeThresholdSet;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class TimeThresholdSetController extends BossBaseController
{

    private $i18nCategory = CarDispatchLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {

        $this->mapping['time_threshold_type'] = [
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_COMMON => '开启立即用车派单逻辑',
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_SPECIAL_FORCE => '预约用车待派订单开启强派模式',
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_PICKUP_SPECIAL_FORCE => '接机用车待派订单开启强派模式',
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_DROP_OFF_SPECIAL_FORCE => '送机用车待派订单开启强派模式',
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_HALF_DAY_SPECIAL_FORCE => '包车4小时待派订单开启强派模式',
            CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_ONE_DAY_SPECIAL_FORCE => '包车8小时待派订单开启强派模式'
        ];

        return parent::beforeAction($action);
    }

    /**
     * actionList --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionList()
    {
        try {
            $page = Request::input('page', 1);
            $page_size = Request::input('pageSize', 10);

            $attributes = ['page', 'pageSize'];
            $rules = [
                [
                    'page',
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.page.invalid'),
                    'tooSmall' => \Yii::t($this->i18nCategory, 'error.page.small', 1),
                ],
                [
                    'pageSize',
                    'integer',
                    'min' => 7,
                    'max' => 1000,
                    'message' => \Yii::t($this->i18nCategory, 'error.page_size.invalid'),
                    'tooSmall' => \Yii::t($this->i18nCategory, 'error.page_size.small', 10),
                    'tooBig' => \Yii::t($this->i18nCategory, 'error.page_size.big', 1000),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params = [];
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = CarDispatchTimeThresholdSet::lists($params, $pager);
            $count = CarDispatchTimeThresholdSet::get_total_count($params);

            Common::int_to_string($list, $this->mapping);
            CarDispatchLogic::fillUserInfo($list);
            CarDispatchLogic::fillBaseData($list, ['city_code', 'service_type_id']);

            $data['list'] = array_values($list);
            $data['pageInfo'] = [
                'page' => $page,
                'pageCount' => ceil($count / $page_size),
                'pageSize' => $page_size,
                'total' => $count
            ];

            $this->renderJson($data);
        } catch (Exception $e) {
            $this->renderJson($e);
        }

    }

    /**
     * actionAdd --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionAdd()
    {
        try {

            list($city_code, $service_type_id, $time_threshold_type, $time_threshold) = $this->checkParams(false);

            $this->checkRepeat($city_code, $service_type_id, $time_threshold_type);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['time_threshold_type'] = $time_threshold_type;
            $data['time_threshold'] = $time_threshold;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchTimeThresholdSet::add($data);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionEdit --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionEdit()
    {
        try {

            list($city_code, $service_type_id, $time_threshold_type, $time_threshold, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $service_type_id, $time_threshold_type, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['time_threshold_type'] = $time_threshold_type;
            $data['time_threshold'] = $time_threshold;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchTimeThresholdSet::edit($id, $data);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * checkParams --
     * @author JerryZhang
     * @param bool $check_id
     * @return array
     * @cache No
     */
    private function checkParams($check_id = true)
    {

        $id = Request::input('id');
        $city_code = Request::input('city_code');
//        $service_type_id = Request::input('service_type_id');
        $time_threshold_type = Request::input('time_threshold_type');
        $time_threshold = Request::input('time_threshold');
        $service_type_id = 0;

        $attributes = ['city_code', 'service_type_id', 'time_threshold_type', 'time_threshold'];
        $rules = [
            [
                'city_code',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
//            [
//                'service_type_id',
//                'required',
//                'message' => \Yii::t($this->i18nCategory, 'error.params'),
//            ],
//            [
//                'service_type_id',
//                'integer',
//                'min' => 1,
//                'message' => \Yii::t($this->i18nCategory, 'error.params'),
//            ],
            [
                'time_threshold_type',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'time_threshold_type',
                'in',
                'range' => [CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_COMMON, CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_SPECIAL_FORCE, CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_PICKUP_SPECIAL_FORCE, CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_DROP_OFF_SPECIAL_FORCE,CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_HALF_DAY_SPECIAL_FORCE,CarDispatchTimeThresholdSet::TIME_THRESHOLD_TYPE_ONE_DAY_SPECIAL_FORCE],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'time_threshold',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'time_threshold',
                'integer',
                'min' => 0,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],

        ];

        if ($check_id) {
            array_push($attributes, 'id');
            array_push($rules, ['id', 'required', 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
            array_push($rules,
                ['id', 'integer', 'min' => 1, 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
        }

        $this->verifyParam($attributes, Request::input(), $rules);

        $res = [$city_code, $service_type_id, $time_threshold_type, $time_threshold];
        $check_id && array_push($res, $id);

        return $res;
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $time_threshold_type
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkRepeat($city_code, $service_type_id, $time_threshold_type, $id = false)
    {

        if (CarDispatchTimeThresholdSet::checkData($city_code, $service_type_id, $time_threshold_type, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
