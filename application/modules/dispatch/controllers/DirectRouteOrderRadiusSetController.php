<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchDirectRouteOrderRadiusSet;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class DirectRouteOrderRadiusSetController extends BossBaseController
{

    private $i18nCategory = CarDispatchLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {

        $this->mapping['direct_route_order_type'] = [CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_GO_HOME => '回家单', CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_RELAY => '接力单', CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_SPECIAL_PERIOD_SUBSCRIBE => '特殊时段预约单'];

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

            $params = ['is_delete' => CarDispatchDirectRouteOrderRadiusSet::IS_DELETE_NO];
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = CarDispatchDirectRouteOrderRadiusSet::lists($params, $pager);
            $count = CarDispatchDirectRouteOrderRadiusSet::get_total_count($params);

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

            list($city_code, $service_type_id, $direct_route_order_type, $direct_route_order_radius) = $this->checkParams(false);

            $this->checkRepeat($city_code, $service_type_id, $direct_route_order_type);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['direct_route_order_type'] = $direct_route_order_type;
            $data['direct_route_order_radius'] = $direct_route_order_radius;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDirectRouteOrderRadiusSet::add($data);

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

            list($city_code, $service_type_id, $direct_route_order_type, $direct_route_order_radius, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $service_type_id, $direct_route_order_type, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['direct_route_order_type'] = $direct_route_order_type;
            $data['direct_route_order_radius'] = $direct_route_order_radius;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDirectRouteOrderRadiusSet::edit($id, $data);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionDelete --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionDelete()
    {
        try {

            $id = Request::input('id');

            $attributes = ['id'];
            $rules = [
                [
                    'id',
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'id',
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $data['is_delete'] = CarDispatchDirectRouteOrderRadiusSet::IS_DELETE_YES;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDirectRouteOrderRadiusSet::edit($id, $data);

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
        $direct_route_order_type = Request::input('direct_route_order_type');
        $direct_route_order_radius = Request::input('direct_route_order_radius');

        $attributes = ['city_code', 'service_type_id', 'direct_route_order_type', 'direct_route_order_radius'];
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
                'direct_route_order_type',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'direct_route_order_type',
                'in',
                'range' => [CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_GO_HOME, CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_RELAY, CarDispatchDirectRouteOrderRadiusSet::DIRECT_ROUTE_ORDER_TYPE_SPECIAL_PERIOD_SUBSCRIBE],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'direct_route_order_radius',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'direct_route_order_radius',
                'integer',
                'min' => 0,
                'max' => 100,
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

        $res = [$city_code, $service_type_id, $direct_route_order_type, $direct_route_order_radius];
        $check_id && array_push($res, $id);

        return $res;
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $direct_route_order_type
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkRepeat($city_code, $service_type_id, $direct_route_order_type, $id = false)
    {

        if (CarDispatchDirectRouteOrderRadiusSet::checkData($city_code, $service_type_id, $direct_route_order_type, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
