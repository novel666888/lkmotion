<?php
namespace Common\util;

class DateTime
{
    /**
     * 计算时间差
     *
     * @param [type] $begin_time
     * @param [type] $end_time
     * @param [type] $type
     * @return void
     */
    public static function dateDiff($begin_time, $end_time, $type)
    {
        if ($end_time <= $begin_time) {
            return false;
        }
        $begin_time = new \DateTime($begin_time);
        $end_time = new \DateTime($end_time);
        $diff = $begin_time->diff($end_time);

        switch ($type) {
            case 1: // 年
                $diff_date = intval($diff->format('%y'));
                break;
            case 2: // 月
                $diff_date = intval($diff->format('%y')) * 12 + intval($diff->format('%m'));
                break;
            case 3: // 日
                $diff_date = intval($diff->days);
                break;
        }

        return $diff_date;
    }
}