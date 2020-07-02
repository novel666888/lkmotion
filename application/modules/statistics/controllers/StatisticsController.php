<?php

namespace application\modules\statistics\controllers;

use application\controllers\BossBaseController;
use common\util\Request;
use common\util\Json;
use common\logic\statistics\UserStatisticsLogic;
use common\logic\statistics\CouponStatisticsLogic;
use common\logic\statistics\CarStatisticsLogic;
use common\logic\statistics\OrderStatisticsLogic;
use common\util\Validate;
use common\util\DateTime;
use common\util\Excel;

class StatisticsController extends BossBaseController
{
    private $_messages = [
        10001 => '请选择开始时间',
        10002 => '请选择结束时间',
        10003 => '开始时间不能大于结束时间',
        10004 => '时间范围不能大于31天',
        10005 => '时间范围不能大于12个月',
        10000 => '请求出错',
        11000 => '数据为空',
    ];

    /**
     * 用户统计
     *
     * @return void
     */
    public function actionUserStatistics()
    {
        $request_data = $this->key2lower(Request::input());

        $result = $this->_getUserStatistics($request_data);

        $data = $result['data'];

        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }

        return Json::success($this->keyMod(['list' => $data]));
    }

    public function actionUserStatisticsExport()
    {
        $request_data = $this->key2lower(Request::input());

        $result = $this->_getUserStatistics($request_data);

        if (is_numeric($result['data'])) {
            return Json::message($this->_messages[$result['data']]);
        }

        if (empty($result['data'])) {
            return Json::message(['list' => ''], 0);
        }
        $request = $result['request'];
        $data = $result['data'];


        switch ($request->user_type) {
            case 1:
                $row_title = ['day' => '时间', 'user_count' => '注册用户数', 'android_count' => 'Android', 'ios_count' => 'ios'];
                break;
            case 2:
                $row_title = ['day' => '时间', 'order_count' => '下单用户数', 'order_1' => '实时单', 'order_2' => '预约单', 'order_3' => '接机单', 'order_4' => '送机单', 'order_5' => '包车', 'order_9' => '取消订单', 'ios_count' => 'IOS', 'android_count' => 'Android'];
                break;
            case 3:
                $row_title = ['day' => '时间', 'user_count' => '活跃用户数', 'android_count' => 'Android', 'ios_count' => 'ios'];
                break;
        }
        $keys = array_keys($data[0]);
        $row_title = array_filter($row_title, function ($_key) use ($keys) {
            return in_array($_key, $keys);
        }, ARRAY_FILTER_USE_KEY);

        $_total = ['day' => '总计'];
        foreach ($keys as $_k => $_v) {
            if ($_k == 'day') {
                continue;
            }
            $_total[$_v] = array_sum(array_column($data, $_v));
        }
        $data[] = $_total;

        Excel::init('用户统计数据')->setRowTitle($row_title)->setRowData($data)->export();
    }

    private function _getUserStatistics($request_data)
    {
        $model = new Validate($request_data);
        $rules = [
            ['user_type', 'default', 'value' => 1],
            ['device_type', 'default', 'value' => 0],
            ['order_type', 'default', 'value' => 0],
            ['period', 'default', 'value' => 1],
            ['begin_time', 'required', 'message' => 10001],
            ['end_time', 'required', 'message' => 10002],
            ['end_time', function ($attribute, $params) use ($model) {
                if ($model->begin_time > $model->end_time) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10003]],
            ['begin_time', function ($attribute, $params) use ($model) {
                if ($model->period == 1) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 3);

                    if ($diff_date > 31) {
                        $model->addError($attribute, $params['message']['period1']);
                    }
                } elseif ($model->period == 2) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 2);
                    if ($diff_date > 12) {
                        $model->addError($attribute, $params['message']['period2']);
                    }
                }
            }, 'params' => ['message' => ['period1' => 10004, 'period2' => 10005]]],
        ];
        $model->addRules($rules);

        $model->validate();

        if ($model->hasErrors()) {
            return ['request' => $model, 'data' => $model->getFirstError()];
        }

        $user_statistics_logic = new UserStatisticsLogic();
        $data = $user_statistics_logic->lists(
            $model->begin_time,
            $model->end_time,
            $model->user_type,
            $model->device_type,
            $model->order_type,
            $model->period
        );
        return ['request' => $model, 'data' => $data];
    }

    /**
     * 订单统计
     *
     * @return void
     */
    public function actionOrderStatistics()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getOrderStatistics($request_data);

        $data = $result['data'];

        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }

        return Json::success($this->keyMod(['list' => $data]));
    }

    public function actionOrderStatisticsExport()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getOrderStatistics($request_data);

        $data = $result['data'];
        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }
        if (empty($data)) {
            return Json::success(['list' => []]);
        }

        $request = $result['request'];
        $data = $result['data'];
        switch ($request->user_type) {
            case 1:
                $row_title = ['day' => '时间', 'order_1' => '预约单', 'order_2' => '实时单', 'order_3' => '接机单', 'order_4' => '送机单', 'order_5' => '包车', 'order_9'=>'取消订单'];
                break;
            case 2:
                $row_title = ['day' => '时间', 'order_count' => '实时订单数', 'order_amount' => '订单流水(元)'];
                break;
        }
        $keys = array_keys($data[0]);
        $row_title = array_filter($row_title, function ($_key) use ($keys) {
            return in_array($_key, $keys);
        }, ARRAY_FILTER_USE_KEY);
        $_total = ['day' => '总计'];
        foreach ($keys as $_k => $_v) {
            if ($_k == 'day') {
                continue;
            }
            $_total[$_v] = array_sum(array_column($data, $_v));
        }
        $data[] = $_total;
        Excel::init('订单统计数据')->setRowTitle($row_title)->setRowData($data)->export();
    }

    private function _getOrderStatistics($request_data)
    {
        $model = new Validate($request_data);
        $rules = [
            ['user_type', 'default', 'value' => 1],
            ['order_type', 'default', 'value' => 0],
            ['period', 'default', 'value' => 1],
            ['begin_time', 'required', 'message' => 10001],
            ['end_time', 'required', 'message' => 10002],
            ['end_time', function ($attribute, $params) use ($model) {
                if ($model->begin_time > $model->end_time) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10003]],
            ['begin_time', function ($attribute, $params) use ($model) {
                if ($model->period == 1) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 3);
                    if ($diff_date > 31) {
                        $model->addError($attribute, $params['message']['period1']);
                    }
                } elseif ($model->period == 2) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 2);
                    if ($diff_date > 12) {
                        $model->addError($attribute, $params['message']['period2']);
                    }
                }
            }, 'params' => ['message' => ['period1' => 10004, 'period2' => 10005]]],
        ];
        $model->addRules($rules);
        $model->validate();
        if ($model->hasErrors()) {
            return ['request' => $model, 'data' => $model->getFirstError()];
        }

        $order_statistics_logic = new OrderStatisticsLogic();

        $data = $order_statistics_logic->lists(
            $model->begin_time,
            $model->end_time,
            $model->user_type,
            $model->order_type,
            $model->period
        );
        return ['request' => $model, 'data' => $data];
    }

    /**
     * 优惠券统计
     *
     * @return void
     */
    public function actionCouponStatistics()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getCouponStatistics($request_data);
        $data = $result['data'];
        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }

        return Json::success($this->keyMod(['list' => $data]));
    }

    public function actionCouponStatisticsExport()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getCouponStatistics($request_data);
        $data = $result['data'];
        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }
        if (empty($data)) {
            return Json::success(['list' => []]);
        }

        $request = $result['request'];
        $row_title = ['day' => '时间', 'has_count' => '领取优惠券数量', 'used_count' => '使用优惠券数量'];

        $keys = array_keys($data[0]);
        $row_title = array_filter($row_title, function ($_key) use ($keys) {
            return in_array($_key, $keys);
        }, ARRAY_FILTER_USE_KEY);

        $_total = ['day' => '总计'];
        foreach ($keys as $_k => $_v) {
            if ($_k == 'day') {
                continue;
            }
            $_total[$_v] = array_sum(array_column($data, $_v));
        }
        $data[] = $_total;
        Excel::init('优惠券数据统计')->setRowTitle($row_title)->setRowData($data)->export();
    }

    private function _getCouponStatistics($request_data)
    {
        $model = new Validate($request_data);
        $rules = [
            ['type', 'default', 'value'=>0],
            ['period', 'default', 'value'=>1],
            ['begin_time', 'required', 'message' => 10001],
            ['end_time', 'required', 'message' => 10002],
            ['end_time', function ($attribute, $params) use ($model) {
                if ($model->begin_time > $model->end_time) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10003]],
            ['begin_time', function ($attribute, $params) use ($model) {
                if ($model->period == 1) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 3);
                    if ($diff_date > 31) {
                        $model->addError($attribute, $params['message']['period1']);
                    }
                } elseif ($model->period == 2) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 2);
                    if ($diff_date > 12) {
                        $model->addError($attribute, $params['message']['period2']);
                    }
                }
            }, 'params' => ['message' => ['period1' => 10004, 'period2' => 10005]]],
        ];
        $model->addRules($rules);
        $model->validate();
        if ($model->hasErrors()) {
            return ['request' => $model, 'data' => $model->getFirstError()];
        }
        $coupon_statistics_logic = new CouponStatisticsLogic();
        $data = $coupon_statistics_logic->lists(
            $model->begin_time,
            $model->end_time,
            $model->type,
            $model->period
        );

        if (is_numeric($data)) {
            return ['request' => $model, 'data' => $data];
        }
        return ['request' => $model, 'data' => $data];
    }

    /**
     * 车辆统计
     *
     * @return void
     */
    public function actionCarStatistics()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getCarStatistics($request_data);
        if (is_numeric($result['data'])) {
            return Json::message($this->_messages[$result['data']]);
        }

        return Json::success($this->keyMod(['list' => $result['data']]));
    }

    public function actionCarStatisticsExport()
    {
        $request_data = $this->key2lower(Request::input());
        $result = $this->_getCarStatistics($request_data);
        $data = $result['data'];
        if (is_numeric($data)) {
            return Json::message($this->_messages[$data]);
        }

        if (empty($data)) {
            return Json::success(['list' => []]);
        }

        $request = $result['request'];
        $row_title = ['day' => '时间', 'car_count' => '运营车辆数', 'time_long' => '平均运营时长'];
        $keys = array_keys($data[0]);
        $row_title = array_filter($row_title, function ($_key) use ($keys) {
            return in_array($_key, $keys);
        }, ARRAY_FILTER_USE_KEY);

        $_total = ['day' => '总计'];
        foreach ($keys as $_k => $_v) {
            if ($_k == 'day') {
                continue;
            }
            $_total[$_v] = array_sum(array_column($data, $_v));
        }
        $data[] = $_total;

        Excel::init('优惠券数据统计')->setRowTitle($row_title)->setRowData($data)->export();
    }

    private function _getCarStatistics($request_data)
    {
        $model = new Validate($request_data);
        $rules = [
            ['type', 'default', 'value'=>0],
            ['period', 'default', 'value'=>1],
            ['begin_time', 'required', 'message' => 10001],
            ['end_time', 'required', 'message' => 10002],
            ['end_time', function ($attribute, $params) use ($model) {
                if ($model->begin_time > $model->end_time) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10003]],
            ['begin_time', function ($attribute, $params) use ($model) {
                if ($model->period == 1) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 3);
                    if ($diff_date > 31) {
                        $model->addError($attribute, $params['message']['period1']);
                    }
                } elseif ($model->period == 2) {
                    $diff_date = DateTime::dateDiff($model->begin_time, $model->end_time, 2);
                    if ($diff_date > 12) {
                        $model->addError($attribute, $params['message']['period2']);
                    }
                }
            }, 'params' => ['message' => ['period1' => 10004, 'period2' => 10005]]],
        ];
        $model->addRules($rules);
        $model->validate();
        if ($model->hasErrors()) {
            return ['request' => $model, 'data' => $model->getFirstError()];
        }
        
        $car_statistics_logic = new CarStatisticsLogic();

        $data = $car_statistics_logic->lists(
            $model->begin_time,
            $model->end_time,
            $model->type,
            $model->period
        );
        return ['request' => $model, 'data' => $data];
    }
}
