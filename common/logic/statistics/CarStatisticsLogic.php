<?php
namespace common\logic\statistics;

use common\api\StatisticsApi;

class CarStatisticsLogic
{
    public function lists($begin_time, $end_time, $type=1,$period = 1)
    {
        $statistics_api = new StatisticsApi();
        $data = $statistics_api->carStatistics($begin_time, $end_time, $period);

        if ($data === false) {
            return 10000;
        }
        if (empty($data)) {
            return [];
        }
        switch ($type) {
            case 1:
                $output = ['day'=>'', 'car_count'=>0];
                break;
            case 2:
                $output = ['day'=>'', 'time_long'=>0];
                break;
            default:
                $output = ['day'=>'', 'car_count'=>0,'time_long'=>0];
                break;
        }

        foreach ($data as $_k => $_v) {
            if (!isset($data[$_v['day']])) {
                $data[$_v['day']] = $output;
                $data[$_v['day']]['day'] = $_v['day'];
            }
            switch ($type) {
                case 1:
                    if ($_v['type'] == 1) {
                        $data[$_v['day']]['car_count'] = (int)$_v['count'];
                    }
                    break;
                case 2:
                    if ($_v['type'] == 2) {
                        $data[$_v['day']]['time_long'] = $_v['count'];
                    }
                    break;
                default:
                    if ($_v['type'] == 1) {
                        $data[$_v['day']]['car_count'] = (int)$_v['count'];
                    }
                    if ($_v['type'] == 2) {
                        $data[$_v['day']]['time_long'] = $_v['count'];
                    }
                    break;
            }
            unset($data[$_k]);
        }
        $days = array_column($data, 'day');
        array_multisort($days, SORT_DESC, $data);
        return array_values($data);
    }
}