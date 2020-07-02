<?php

namespace application\modules\dispatch\controllers;

use common\logic\CarDispatchLogic;
use common\models\CarDispatchDistributeSet;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class DistributeSetController extends BossBaseController
{

    private $i18nCategory = CarDispatchLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
        $this->mapping['is_force_distribute'] = [CarDispatchDistributeSet::IS_FORCE_DISTRIBUTE_YES => '是', CarDispatchDistributeSet::IS_FORCE_DISTRIBUTE_NO => '否'];

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
            $list = CarDispatchDistributeSet::lists($params, $pager);
            $count = CarDispatchDistributeSet::get_total_count($params);

            Common::int_to_string($list, $this->mapping);
            CarDispatchLogic::fillBaseData($list, ['city_code']);
            CarDispatchLogic::fillUserInfo($list);

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

            list($city_code, $is_force_distribute) = $this->checkParams(false);

            $this->checkRepeat($city_code);

            $data['city_code'] = $city_code;
            $data['is_force_distribute'] = $is_force_distribute;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDistributeSet::add($data);

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

            list($city_code, $is_force_distribute, $id) = $this->checkParams(true);

            $this->checkRepeat($city_code, $id);

            $data['city_code'] = $city_code;
            $data['is_force_distribute'] = $is_force_distribute;
            $data['operator_id'] = $this->userInfo['id'];

            $res = CarDispatchDistributeSet::edit($id, $data);

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
        $is_force_distribute = Request::input('is_force_distribute');

        $attributes = ['city_code', 'is_force_distribute'];
        $rules = [
            [
                'city_code',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'is_force_distribute',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'is_force_distribute',
                'in',
                'range' => [CarDispatchDistributeSet::IS_FORCE_DISTRIBUTE_NO, CarDispatchDistributeSet::IS_FORCE_DISTRIBUTE_YES],
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

        $res = [$city_code, $is_force_distribute];
        $check_id && array_push($res, $id);

        return $res;
    }

    /**
     * checkRepeat --
     * @author JerryZhang
     * @param $city_code
     * @param bool $id
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkRepeat($city_code, $id = false)
    {

        if (CarDispatchDistributeSet::checkData($city_code, $id)) {
            throw new Exception(\Yii::t($this->i18nCategory, 'error.set.repeat'), 1);
        }

        return true;
    }

}
