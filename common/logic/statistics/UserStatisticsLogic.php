<?php
namespace common\logic\statistics;

use common\api\StatisticsApi;


class UserStatisticsLogic
{
    const USER_TYPE = [
        1, // 注册用户数
        2, // 下单用户数
        3, // 活跃用户数
    ];
    const DEVICE_TYPE = [
        0, // 全部
        1, // IOS
        2, // 安卓
    ];
    const ORDER_TYPE = [
        0, // 全部
        1, // 实时单
        2, // 预约单
        3, // 接机单
        4, // 送机单
        5, // 包车单
        9, // 取消订单
    ];
    const PERIOD = [
        1, // 按天
        2, // 按月
    ];
    private $device_format = [
        0 => [
            'ios_count' => 0,
            'android_count' => 0
        ],
        1 => ['ios_count' => 0],
        2 => ['android_count' => 0]
    ];
    private $order_format = [];
    private $user_type_format = [
        1 => ['day' => '', 'user_count' => 0],
        2 => ['day' => '', 'order_count' => 0],
        3 => ['day' => '', 'user_count' => 0]
    ];
    private $output_format = [];

    /**
     * 用户统计列表
     *
     * @param date $begin_time
     * @param date $end_time
     * @param integer $user_type 查看类目 1: 注册用户数 2:下单用户数 3:活跃用户数
     * @param integer $device_type 设备类目 0: 全部 1: ios, 2: 安卓
     * @param integer $order_type 订单类型 0: 全部 1: 实时单 2: 预约单 user_type == 2 时必须有此参数
     * @param integer $period 查询周期 1: 天 2: 月
     * @return void
     */
    public function lists($begin_time, $end_time, $user_type = 1, $device_type = 0, $order_type = 0, $period = 1)
    {
        $statistics_api = new StatisticsApi();

        $data = $statistics_api->userStatistics($begin_time, $end_time, $user_type, $device_type, $period);

        if ($data === false) {
            return 10000;
        }

        if (empty($data)) {
            return [];
        }

        $order_type = array_filter(explode(',', $order_type));

        $this->init_order_format($order_type);
        $this->init_output_format($user_type, $device_type, $order_type);

        $data = $this->filter_data($data, $user_type, $device_type, $order_type);
        
        if (empty($data)) {
            return [];
        }
        
        switch ($user_type) {
            case 1:
                $data = $this->lists_register($data, $device_type);
                break;
            case 2:
                $data = $this->lists_order($data, $device_type, $order_type);
                break;
            case 3:
                $data = $this->lists_activity($data, $device_type);
                break;
        }
        $days = array_column($data, 'day');
        array_multisort($days, SORT_DESC, $data);
        return array_values($data);
    }

    private function init_order_format($order_type)
    {
        if (empty($order_type)) {
            $order_type = self::ORDER_TYPE;
        }

        foreach ($order_type as $_v) {
            $this->order_format['order_' . $_v] = 0;
        }
    }

    private function init_output_format($user_type, $device_type, $order_type)
    {
        switch ($user_type) {
            case 1:
                $this->output_format = array_merge($this->user_type_format[$user_type], $this->device_format[$device_type]);
                break;
            case 2:
                $this->output_format = array_merge($this->user_type_format[$user_type], $this->order_format, $this->device_format[$device_type]);
                break;
            case 3:
                $this->output_format = $this->output = array_merge($this->user_type_format[$user_type], $this->device_format[$device_type]);
                break;
        }
    }

    private function filter_data($data, $user_type, $device_type, $order_type)
    {
        foreach ($data as $_k => $_v) {
            if ($user_type == 2) {
                if (!in_array($_v['serviceType'], $order_type) && !empty($order_type)) {
                    unset($data[$_k]);
                }
                // 不包含取消订单
                if ($_v['serviceType'] == 9) {
                    unset($data[$_k]);
                }
            }

            if (!empty($device_type) && $device_type != $_v['type']) {
                unset($data[$_k]);
            }
        }

        return $data;
    }

    private function build_device_data($result, $day, $type, $count)
    {
        if ($type == 1) {
            $result[$day]['ios_count'] += $count;
        } elseif ($type == 2) {
            $result[$day]['android_count'] += $count;
        }
        return $result;
    }

    private function build_order_data($result, $day, $service_type, $count)
    {
        // 将包车订单, serviceType = 5,6 合并到serviceType为5的结构中
        if ($service_type == 6) {
            $result[$day]['order_5'] += $count;
        } else {
            if (isset($result[$day]['order_' . $service_type])) {
                $result[$day]['order_' . $service_type] += $count;
            }
        }
        return $result;
    }

    /**
     * 注册用户数汇总
     *
     * @return void
     */
    private function lists_register($data, $device_type = 0)
    {
        $result = [];
        foreach ($data as $_k => $_v) {
            $_count = (int)$_v['count'];
            if (!isset($result[$_v['day']])) {
                $result[$_v['day']] = $this->output_format;
                $result[$_v['day']]['day'] = $_v['day'];
            }
            $result[$_v['day']]['user_count'] += $_count;
            $result = $this->build_device_data($result, $_v['day'], $_v['type'], $_count);
        }

        return $result;
    }

    /**
     * 下单用户数
     *
     * @param [type] $data
     * @param integer $device_type
     * @param array $order_type
     * @return void
     */
    private function lists_order($data, $device_type = 0, $order_type = [])
    {
        
        $result = [];
        foreach ($data as $_k => $_v) {
            $_count = (int)$_v['count'];
            if (!isset($result[$_v['day']])) {
                $result[$_v['day']] = $this->output_format;
                $result[$_v['day']]['day'] = $_v['day'];
            }
            $result[$_v['day']]['order_count'] += $_count;
            $result = $this->build_order_data($result, $_v['day'], $_v['serviceType'], $_count);
            $result = $this->build_device_data($result, $_v['day'], $_v['type'], $_count);
        }
        return $result;
    }

    /**
     * 活跃用户数
     *
     * @param [type] $data
     * @param integer $device_type
     * @return void
     */
    private function lists_activity($data, $device_type = 0)
    {
        $result = [];

        foreach ($data as $_k => $_v) {
            $_count = (int)$_v['count'];
            if (!isset($result[$_v['day']])) {
                $result[$_v['day']] = $this->output_format;
                $result[$_v['day']]['day'] = $_v['day'];
            }
            $result[$_v['day']]['user_count'] += $_v['count'];
            $result = $this->build_device_data($result, $_v['day'], $_v['type'], $_count);
        }
        return $result;
    }
}