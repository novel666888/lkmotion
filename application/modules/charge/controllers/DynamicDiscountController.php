<?php

namespace application\modules\charge\controllers;

use common\logic\DynamicDiscountLogic;
use common\models\DynamicDiscountRule;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class DynamicDiscountController extends BossBaseController
{

    private $i18nCategory = DynamicDiscountLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
        $this->mapping['now_status'] = ['1' => '未开始', '2' => '已开始', '3' => '已结束'];
        $this->mapping['is_unuse'] = [DynamicDiscountRule::IS_UNUSE_NO => '正常', DynamicDiscountRule::IS_UNUSE_YES => '已冻结'];

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
            $city_code = Request::input('city_code', '');
            $service_type = Request::input('service_type_id', 0);
            $car_level = Request::input('car_level_id', 0);
            $now_status = Request::input('now_status', 0);
            $is_unuse = Request::input('is_unuse', '-1');

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
                [
                    ['service_type_id', 'car_level_id'],
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'now_status',
                    'in',
                    'range' => [0, 1, 2, 3],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'is_unuse',
                    'in',
                    'range' => [-1, DynamicDiscountRule::IS_UNUSE_NO, DynamicDiscountRule::IS_UNUSE_YES],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params['city_code'] = $city_code;
            $params['service_type'] = $service_type;
            $params['car_level'] = $car_level;
            $params['now_status'] = $now_status;
            $params['is_unuse'] = $is_unuse;
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = DynamicDiscountLogic::lists($params, $pager);
            $count = DynamicDiscountLogic::get_total_count($params);

            $now = time();
            foreach ($list as &$v) {
                $v['service_type'] = explode(',', $v['service_type']);
                $v['car_level'] = explode(',', $v['car_level']);
                $v['time_set'] = explode(',', $v['time_set']);
                $v['week_set'] = explode(',', $v['week_set']);
                $v['special_date_set'] = explode(',', $v['special_date_set']);
                if (strtotime($v['valid_start_time']) > $now) {
                    $v['now_status_text'] = $this->mapping['now_status'][1];
                } elseif (strtotime($v['valid_end_time']) < $now) {
                    $v['now_status_text'] = $this->mapping['now_status'][3];
                } else {
                    $v['now_status_text'] = $this->mapping['now_status'][2];
                }
                $v['is_unuse_text'] = $this->mapping['is_unuse'][$v['is_unuse']];
                DynamicDiscountLogic::fillBaseData($v['city'], ['city_code']);
            }

            DynamicDiscountLogic::fillData($list);

            $data['list'] = array_values($list);
            $data['pageInfo'] = [
                'page' => $page,
                'pageCount' => ceil($count / $page_size),
                'pageSize' => $page_size,
                'total' => intval($count)
            ];

            $this->renderJson($data);
        } catch (Exception $e) {
            $this->renderJson($e);
        }

    }

    /**
     * actionDetail --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionDetail()
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

            $data = DynamicDiscountLogic::showBatch($id);
//            DynamicDiscountLogic::fillData($data);
            $data = array_shift($data);
            if(!empty($data)){
                $data['service_type'] = explode(',', $data['service_type']);
                $data['car_level'] = explode(',', $data['car_level']);
                $data['time_set'] = explode(',', $data['time_set']);
                $data['week_set'] = explode(',', $data['week_set']);
                $data['special_date_set'] = explode(',', $data['special_date_set']);
            }

            if (!$data) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

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

            list($city, $service_type, $car_level, $time_set, $type_select, $type_value, $discount_max_price, $discount_rule, $valid_start_time, $valid_end_time) = $this->checkParams(false);

            DynamicDiscountLogic::checkRepeat($city, $service_type, $car_level, $type_select, $type_value, $valid_start_time, $valid_end_time);

            $data_rule['service_type'] = implode(',', $service_type);
            $data_rule['car_level'] = implode(',', $car_level);
            $data_rule['time_set'] = implode(',', $time_set);
            $data_rule['date_type'] = $type_select;
            if ($type_select == DynamicDiscountRule::DATE_TYPE_WEEK_SET) {
                $data_rule['week_set'] = implode(',', $type_value);
            } else {
                $data_rule['special_date_set'] = implode(',', $type_value);
            }
            $data_rule['discount_max_price'] = $discount_max_price;
            $data_rule['valid_start_time'] = $valid_start_time;
            $data_rule['valid_end_time'] = $valid_end_time;

            $res = DynamicDiscountLogic::add($data_rule, $discount_rule, $city);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

//    /**
//     * actionEdit --
//     * @author JerryZhang
//     * @cache Yes
//     */
//    public function actionEdit()
//    {
//        try {
//
//            list($city, $service_type, $car_level, $time_set, $type_select, $type_value, $discount_max_price, $discount_rule, $valid_start_time, $valid_end_time, $id) = $this->checkParams(true);
//
//            DynamicDiscountLogic::checkRepeat($city, $service_type, $car_level, $type_select, $type_value, $valid_start_time, $valid_end_time, $id);
//
//            $data_rule['service_type'] = implode(',', $service_type);
//            $data_rule['car_level'] = implode(',', $car_level);
//            $data_rule['time_set'] = implode(',', $time_set);
//            $data_rule['date_type'] = $type_select;
//            if ($type_select == DynamicDiscountRule::DATE_TYPE_WEEK_SET) {
//                $data_rule['week_set'] = implode(',', $type_value);
//            } else {
//                $data_rule['special_date_set'] = implode(',', $type_value);
//            }
//            $data_rule['discount_max_price'] = $discount_max_price;
//            $data_rule['valid_start_time'] = $valid_start_time;
//            $data_rule['valid_end_time'] = $valid_end_time;
//
//            $res = DynamicDiscountLogic::edit($id, $data_rule, $discount_rule, $city);
//
//            if (!$res) {
//                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
//            }
//
//            $this->renderJson([]);
//        } catch (Exception $e) {
//            $this->renderJson($e);
//        }
//    }

    /**
     * actionFreeze --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionFreeze()
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

            $res = DynamicDiscountRule::edit($id, ['is_unuse' => DynamicDiscountRule::IS_UNUSE_YES]);

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
        $city = Request::input('city');
        $service_type = Request::input('service_type');
        $car_level = Request::input('car_level');
        $time_set = Request::input('time_set');
        $type_select = Request::input('type_select');
        $type_value = Request::input('type_value');
        $discount_max_price = Request::input('discount_max_price');
        $discount_rule = Request::input('discount_rule');
        $valid_start_time = Request::input('valid_start_time');
        $valid_end_time = Request::input('valid_end_time');

        $attributes = ['city', 'service_type', 'car_level', 'time_set', 'type_select', 'type_value', 'discount_max_price', 'discount_rule', 'valid_start_time', 'valid_end_time'];
        $rules = [
            [
                'city',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'city',
                'each',
                'rule' => ['integer', 'min' => 1],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'service_type',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'service_type',
                'each',
                'rule' => ['integer', 'min' => 1],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_level',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'car_level',
                'each',
                'rule' => ['integer', 'min' => 1],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'time_set',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'time_set',
                'each',
                'rule' => ['integer', 'min' => 0, 'max' => 23],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'type_select',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'type_select',
                'in',
                'range' => [DynamicDiscountRule::DATE_TYPE_WEEK_SET, DynamicDiscountRule::DATE_TYPE_SPECIAL_DATE_SET],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'type_value',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'discount_max_price',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'discount_max_price',
                'number',
                'min' => 0,
                'max' => 99999,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'discount_rule',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'valid_start_time',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'valid_start_time',
                'datetime',
                'format' => 'php:Y-m-d H:i:s',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'valid_end_time',
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'valid_end_time',
                'datetime',
                'format' => 'php:Y-m-d H:i:s',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
        ];

        if ($check_id) {
            array_push($attributes, 'id');
            array_push($rules, ['id', 'required', 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
            array_push($rules,
                ['id', 'integer', 'min' => 1, 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
        }

        if ($type_select == DynamicDiscountRule::DATE_TYPE_WEEK_SET) {
            $type_value_rule = [
                'type_value',
                'each',
                'rule' => ['integer', 'min' => 1, 'max' => 7],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ];
        } else {
            $type_value_rule = [
                'type_value',
                'each',
                'rule' => ['date', 'format' => 'php:Y-m-d',],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ];
        }
        array_push($rules, $type_value_rule);
        $this->verifyParam($attributes, Request::input(), $rules);

        $res = [$city, $service_type, $car_level, $time_set, $type_select, $type_value, $discount_max_price, $discount_rule, $valid_start_time, $valid_end_time];
        $check_id && array_push($res, $id);

        return $res;
    }

}
