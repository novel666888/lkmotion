<?php

namespace application\modules\charge\controllers;

use common\logic\ValuationLogic;
use common\models\ChargeRule;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class ValuationController extends BossBaseController
{

    private $i18nCategory = ValuationLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
        $this->mapping['is_unuse'] = [ChargeRule::IS_UNUSE_NO => '正常', ChargeRule::IS_UNUSE_YES => '已冻结'];

        return parent::beforeAction($action);
    }

    /**
     * actionList --规则主列表
     * @author JerryZhang
     * @cache Yes
     */
    public function actionList()
    {
        try {
            $page = Request::input('page', 1);
            $page_size = Request::input('pageSize', 10);
            $city_code = Request::input('city_code', '');
            $service_type_id = Request::input('service_type_id', 0);
            $car_level_id = Request::input('car_level_id', 0);
            $channel_id = Request::input('channel_id', 0);
            $is_unuse = Request::input('is_unuse', -1);

            $attributes = ['page', 'pageSize', 'service_type_id', 'car_level_id', 'channel_id', 'is_unuse'];
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
                    'service_type_id',
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'car_level_id',
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'channel_id',
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'is_unuse',
                    'in',
                    'range' => [-1, ChargeRule::IS_UNUSE_NO, ChargeRule::IS_UNUSE_YES],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params['city_code'] = $city_code;
            $params['service_type_id'] = $service_type_id;
            $params['car_level_id'] = $car_level_id;
            $params['channel_id'] = $channel_id;
            $params['is_unuse'] = $is_unuse;
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = ValuationLogic::main_lists($params, $pager);
            $count = ValuationLogic::main_get_total_count($params);

            ValuationLogic::fillBaseData($list, ['city_code', 'service_type_id', 'car_level_id', 'channel_id']);
            ValuationLogic::fillUserInfo($list);
            Common::int_to_string($list, $this->mapping);

            $i = 1;
            foreach ($list as &$v) {
                $v['index'] = ($page - 1) * $page_size + $i;
                $i++;
            }

            $data['list'] = $list;
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
     * actionItem --
     * @author JerryZhang
     * @cache No
     */
    public function actionItem(){
        try {
//            $page = Request::input('page', 1);
//            $page_size = Request::input('pageSize', 10);
            $city_code = Request::input('city_code');
            $service_type_id = Request::input('service_type_id');
            $car_level_id = Request::input('car_level_id');
            $channel_id = Request::input('channel_id');

            $attributes = ['page', 'pageSize', 'city_code', 'service_type_id', 'car_level_id', 'channel_id'];
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
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'car_level_id',
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'car_level_id',
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'channel_id',
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'channel_id',
                    'integer',
                    'min' => 0,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params['city_code'] = $city_code;
            $params['service_type_id'] = $service_type_id;
            $params['car_level_id'] = $car_level_id;
            $params['channel_id'] = $channel_id;

            $pager = [];
            $list = ValuationLogic::lists($params, $pager);

            $now_time = time();
            foreach ($list as &$v){
                if($v['is_unuse'] == ChargeRule::IS_UNUSE_YES){
                    $v['active_status_text'] = '已禁用';
                }else{
                    if($v['active_status'] == ChargeRule::ACTIVE_STATUS_INVALID){
                        $v['active_status_text'] = '已失效';
                    }elseif(strtotime($v['effective_time']) > $now_time){
                        $v['active_status_text'] = '未生效';
                    }else{
                        $v['active_status_text'] = '生效中';
                    }
                }
            }

            ValuationLogic::fillUserInfo($list);
            ValuationLogic::fillUserInfo($list, 'creator_id', 'creator_info');
            Common::int_to_string($list, $this->mapping);

            $data['list'] = array_values($list);
            $data['pageInfo'] = [
//                'page' => $page,
//                'pageCount' => ceil($count / $page_size),
//                'pageSize' => $page_size,
//                'total' => $count
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

            list($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $lowest_price, $base_price, $base_kilo, $base_minutes, $per_kilo_price, $per_minute_price, $beyond_start_kilo, $beyond_per_kilo_price, $night_start, $night_end, $night_per_kilo_price, $night_per_minute_price, $period_rule) = $this->checkParams(false);

            $type = Request::input('type');
            $attributes = ['type'];
            $rules = [
                [
                    'type',
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'type',
                    'in',
                    'range' => [1,2],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];

            $this->verifyParam($attributes, Request::input(), $rules);

            if($type == 1){
                ValuationLogic::checkRepeat($city_code, $service_type_id, $car_level_id, $channel_id);
            }
            ValuationLogic::checkConflict($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time);

            //查询大类启用禁用状态同步状态到新添加的规则
            $params['city_code'] = $city_code;
            $params['service_type_id'] = $service_type_id;
            $params['car_level_id'] = $car_level_id;
            $params['channel_id'] = $channel_id;
            $list = ValuationLogic::main_lists($params);
            $info = array_shift($list);
            $data['is_unuse'] = ChargeRule::IS_UNUSE_NO;
            if(!empty($info) && $info['is_unuse'] == ChargeRule::IS_UNUSE_YES){
                $data['is_unuse'] = ChargeRule::IS_UNUSE_YES;
            }

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['car_level_id'] = $car_level_id;
            $data['channel_id'] = $channel_id;
            $data['effective_time'] = $effective_time;
            $data['lowest_price'] = $lowest_price;
            $data['base_price'] = $base_price;
            $data['base_kilo'] = $base_kilo;
            $data['base_minutes'] = $base_minutes;
            $data['per_kilo_price'] = $per_kilo_price;
            $data['per_minute_price'] = $per_minute_price;
            $data['beyond_start_kilo'] = $beyond_start_kilo;
            $data['beyond_per_kilo_price'] = $beyond_per_kilo_price;
            $data['night_start'] = $night_start;
            $data['night_end'] = $night_end;
            $data['night_per_kilo_price'] = $night_per_kilo_price;
            $data['night_per_minute_price'] = $night_per_minute_price;
            $data['creator_id'] = $this->userInfo['id'];
            $data['operator_id'] = $this->userInfo['id'];
            $data['active_status'] = ChargeRule::ACTIVE_STATUS_VALID;

            $res = ValuationLogic::add($data, $period_rule);

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

            list($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $lowest_price, $base_price, $base_kilo, $base_minutes, $per_kilo_price, $per_minute_price, $beyond_start_kilo, $beyond_per_kilo_price, $night_start, $night_end, $night_per_kilo_price, $night_per_minute_price, $period_rule, $id) = $this->checkParams(true);

            ValuationLogic::checkConflict($city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['car_level_id'] = $car_level_id;
            $data['channel_id'] = $channel_id;
            $data['effective_time'] = $effective_time;
            $data['lowest_price'] = $lowest_price;
            $data['base_price'] = $base_price;
            $data['base_kilo'] = $base_kilo;
            $data['base_minutes'] = $base_minutes;
            $data['per_kilo_price'] = $per_kilo_price;
            $data['per_minute_price'] = $per_minute_price;
            $data['beyond_start_kilo'] = $beyond_start_kilo;
            $data['beyond_per_kilo_price'] = $beyond_per_kilo_price;
            $data['night_start'] = $night_start;
            $data['night_end'] = $night_end;
            $data['night_per_kilo_price'] = $night_per_kilo_price;
            $data['night_per_minute_price'] = $night_per_minute_price;
            $data['operator_id'] = $this->userInfo['id'];

            $res = ValuationLogic::edit($id, $data, $period_rule);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionDetail --
     * @author JerryZhang
     * @cache No
     */
    public function actionDetail(){
        try{

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

            $data = ChargeRule::showBatch($id);
            ValuationLogic::fillBaseData($data, ['city_code','service_type_id','car_level_id','channel_id']);
            $data = array_shift($data);
            if(!empty($data)){
                $data['period_rule'] = array_values(ValuationLogic::getDetailByRuleId($id));
            }

            $this->renderJson($data);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionFreeze --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionSwitch()
    {
        try {
            $city_code = Request::input('city_code');
            $service_type_id = Request::input('service_type_id');
            $car_level_id = Request::input('car_level_id');
            $channel_id = Request::input('channel_id');
            $operation = Request::input('operation');

            $attributes = ['city_code', 'service_type_id', 'car_level_id', 'channel_id' ,'operation'];
            $rules = [
                [
                    ['city_code', 'service_type_id', 'car_level_id', 'channel_id' ,'operation'],
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    ['service_type_id', 'car_level_id', 'channel_id'],
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'operation',
                    'in',
                    'range'=>[ChargeRule::IS_UNUSE_NO, ChargeRule::IS_UNUSE_YES],
                ]
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $res = ValuationLogic::switchRule($operation, $city_code, $service_type_id, $channel_id, $car_level_id, $this->userInfo['id']);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionGetBaseInfo --
     * @author JerryZhang
     * @cache No
     */
    public function actionGetBaseInfo(){
        try{

            $type = Request::input('type');
            $attributes = ['type'];
            $rules = [
                [
                    'type',
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'type',
                    'in',
                    'range' => [1,2],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];

            $this->verifyParam($attributes, Request::input(), $rules);

            $filter_city = false;
            $filter_service_type = false;
            $filter_channel = false;
            $filter_car_level = false;
            if($type == 2){
                $filter_service_type = true;
                $filter_channel = true;
                $filter_car_level = true;
            }

            $data = ValuationLogic::getBaseInfo($filter_city, $filter_service_type, $filter_channel, $filter_car_level);

            $this->renderJson($data);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionCheck --规则选项校验
     * @author JerryZhang
     * @cache No
     */
    public function actionCheck(){
        try{
            $city_code = Request::input('city_code');
            $service_type_id = Request::input('service_type_id');
            $car_level_id = Request::input('car_level_id');
            $channel_id = Request::input('channel_id');

            $attributes = ['city_code', 'service_type_id', 'car_level_id', 'channel_id'];
            $rules = [
                [
                    ['city_code', 'service_type_id', 'car_level_id', 'channel_id'],
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    ['service_type_id', 'car_level_id', 'channel_id'],
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ]
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            ValuationLogic::checkRepeat($city_code, $service_type_id, $car_level_id, $channel_id);

            $this->renderJson([]);
        }catch (Exception $e){
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
        $car_level_id = Request::input('car_level_id');
        $channel_id = Request::input('channel_id');
        $effective_time = Request::input('effective_time');
        $lowest_price = Request::input('lowest_price');
        $base_price = Request::input('base_price');
        $base_kilo = Request::input('base_kilo', 0);
        $base_minutes = Request::input('base_minutes', 0);
        $per_kilo_price = Request::input('per_kilo_price');
        $per_minute_price = Request::input('per_minute_price');
        $beyond_start_kilo = Request::input('beyond_start_kilo');
        $beyond_per_kilo_price = Request::input('beyond_per_kilo_price');
        $night_start = Request::input('night_start');
        $night_end = Request::input('night_end');
        $night_per_kilo_price = Request::input('night_per_kilo_price', 0);
        $night_per_minute_price = Request::input('night_per_minute_price', 0);
        $period_rule = Request::input('period_rule');

        $attributes = ['city_code', 'service_type_id', 'car_level_id', 'channel_id', 'effective_time', 'lowest_price', 'base_price', 'base_kilo', 'base_minutes', 'per_kilo_price', 'per_minute_price', 'beyond_start_kilo', 'beyond_per_kilo_price', 'night_start', 'night_end', 'night_per_kilo_price', 'night_per_minute_price', 'period_rule'];
        $rules = [
            [
                ['city_code', 'service_type_id', 'car_level_id', 'channel_id', 'effective_time', 'lowest_price', 'base_price', 'per_kilo_price', 'per_minute_price', 'beyond_start_kilo', 'beyond_per_kilo_price'],
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                ['service_type_id','car_level_id', 'channel_id'],
                'integer',
                'min'=>1,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                ['lowest_price', 'base_price', 'base_kilo', 'base_minutes', 'per_kilo_price', 'per_minute_price', 'beyond_start_kilo', 'beyond_per_kilo_price', 'night_per_kilo_price', 'night_per_minute_price'],
                'number',
                'min' => 0,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'effective_time',
                'datetime',
                'format' => 'php:Y-m-d H:i:s',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                ['night_start', 'night_end'],
                'time',
                'format' => 'php:H:i:s',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
        ];

        if ($check_id) {
            array_push($attributes, 'id');
            array_push($rules, ['id', 'required', 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
            array_push($rules,
                ['id', 'integer', 'min' => 1, 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
        }

        empty($base_kilo) && $base_kilo = 0;
        empty($base_minutes) && $base_minutes = 0;

        $this->verifyParam($attributes, Request::input(), $rules);

        $res = [$city_code, $service_type_id, $car_level_id, $channel_id, $effective_time, $lowest_price, $base_price, $base_kilo, $base_minutes, $per_kilo_price, $per_minute_price, $beyond_start_kilo, $beyond_per_kilo_price, $night_start, $night_end, $night_per_kilo_price, $night_per_minute_price, $period_rule];
        $check_id && array_push($res, $id);

        return $res;
    }

}
