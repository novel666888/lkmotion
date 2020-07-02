<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchSpecialPeriodSet;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class SpecialPeriodSetController extends BossBaseController
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

            $params = ['is_delete' => CarDispatchSpecialPeriodSet::IS_DELETE_NO];
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = CarDispatchSpecialPeriodSet::lists($params, $pager);
            $count = CarDispatchSpecialPeriodSet::get_total_count($params);

            foreach ($list as &$v){
                if(isset($v)){
                    $v['time_period'] = json_decode($v['time_period'], true);
                }
            }

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

            list($city_code, $service_type_id, $time_period) = $this->checkParams(false);

            $this->checkRepeat($city_code, $service_type_id);

            if(count($time_period) > 10){
                throw new Exception(\Yii::t($this->i18nCategory, 'error.set.over_ten'), 1);
            }

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['time_period'] = json_encode($time_period);
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchSpecialPeriodSet::add($data);

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

            list($city_code, $service_type_id, $time_period, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $service_type_id, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['time_period'] = json_encode($time_period);
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchSpecialPeriodSet::edit($id, $data);

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

            $data['is_delete'] = CarDispatchSpecialPeriodSet::IS_DELETE_YES;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchSpecialPeriodSet::edit($id, $data);

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
        $time_period = Request::input('time_period');

        $attributes = ['city_code', 'is_force_distribute'];
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
                'time_period',
                'required',
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

        $res = [$city_code, $service_type_id, $time_period];
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

        if (CarDispatchSpecialPeriodSet::checkData($city_code, $service_type_id, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
