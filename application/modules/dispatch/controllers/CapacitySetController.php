<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchCapacitySet;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class CapacitySetController extends BossBaseController
{

    private $i18nCategory = CarDispatchLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
//        $this->mapping['car_service_period'] = [CarDispatchCapacitySet::CAR_SERVICE_PERIOD_DAY => '白天', CarDispatchCapacitySet::CAR_SERVICE_PERIOD_NIGHT => '晚上'];

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
            $list = CarDispatchCapacitySet::lists($params, $pager);
            $count = CarDispatchCapacitySet::get_total_count($params);

            Common::int_to_string($list, $this->mapping);
            CarDispatchLogic::fillUserInfo($list);
            CarDispatchLogic::fillBaseData($list, ['city_code']);

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

            list($city_code, $car_service_period, $spare_driver_count) = $this->checkParams(false);

            $this->checkRepeat($city_code, $car_service_period);

            $data['city_code'] = $city_code;
            $data['car_service_period'] = $car_service_period;
            $data['spare_driver_count'] = $spare_driver_count;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchCapacitySet::add($data);

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

            list($city_code, $car_service_period, $spare_driver_count, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $car_service_period, $id);

            $data['city_code'] = $city_code;
            $data['car_service_period'] = $car_service_period;
            $data['spare_driver_count'] = $spare_driver_count;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchCapacitySet::edit($id, $data);

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
        $car_service_period = Request::input('car_service_period');
        $spare_driver_count = Request::input('spare_driver_count');

        $attributes = ['city_code', 'car_service_period', 'spare_driver_count'];
        $rules = [
            [
                'city_code',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_service_period',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
//            [
//                'car_service_period',
//                'in',
//                'range' => [CarDispatchCapacitySet::CAR_SERVICE_PERIOD_DAY, CarDispatchCapacitySet::CAR_SERVICE_PERIOD_NIGHT],
//                'message' => \Yii::t($this->i18nCategory, 'error.params'),
//            ],
            [
                'spare_driver_count',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'spare_driver_count',
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

        $res = [$city_code, $car_service_period, $spare_driver_count];
        $check_id && array_push($res, $id);

        return $res;
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param int $car_service_period
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkRepeat($city_code, $car_service_period, $id = false)
    {

        if (CarDispatchCapacitySet::checkData($city_code, $car_service_period, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
