<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchDistributeIntervalSet;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class DistributeIntervalSetController extends BossBaseController
{

    private $i18nCategory = CarDispatchLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
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
            $list = CarDispatchDistributeIntervalSet::lists($params, $pager);
            $count = CarDispatchDistributeIntervalSet::get_total_count($params);

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

            list($city_code, $service_type_id, $car_service_before_interval, $car_service_after_interval) = $this->checkParams(false);

            $this->checkRepeat($city_code, $service_type_id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['car_service_before_interval'] = $car_service_before_interval;
            $data['car_service_after_interval'] = $car_service_after_interval;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDistributeIntervalSet::add($data);

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

            list($city_code, $service_type_id, $car_service_before_interval, $car_service_after_interval, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $service_type_id, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['car_service_before_interval'] = $car_service_before_interval;
            $data['car_service_after_interval'] = $car_service_after_interval;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDistributeIntervalSet::edit($id, $data);

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
        $service_type_id = Request::input('service_type_id');
        $car_service_before_interval = Request::input('car_service_before_interval');
        $car_service_after_interval = Request::input('car_service_after_interval');

        $attributes = ['city_code', 'service_type_id', 'car_service_before_interval', 'car_service_after_interval'];
        $rules = [
            [
                'city_code',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'service_type_id',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'service_type_id',
                'integer',
                'min' => 1,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_service_before_interval',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_service_before_interval',
                'integer',
                'min' => 0,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_service_after_interval',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_service_after_interval',
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

        $res = [$city_code, $service_type_id, $car_service_before_interval, $car_service_after_interval];
        $check_id && array_push($res, $id);

        return $res;
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkRepeat($city_code, $service_type_id, $id = false)
    {

        if (CarDispatchDistributeIntervalSet::checkData($city_code, $service_type_id, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
