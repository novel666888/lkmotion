<?php

/**
 * ChargeRuleTrait Class
 *
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/28
 * Time: 12:54
 */
namespace common\logic;

use common\models\ChargeRule;
use common\models\ChargeRuleDetail;
use common\util\Common;
use yii\base\UserException;

/**
 * Trait ChargeRuleTrait
 * @package common\logic
 * @throws \Exception
 */
trait ChargeRuleTrait
{
    public function getChargeRule($cityCode, $serviceType, $carLevel, $channel_id)
    {
        $chargeRuleData = ChargeRule::fetchOne([
            'city_code' => $cityCode,
            'service_type_id' => $serviceType,
            'car_level_id' => $carLevel,
        ], [
            'ruleId' => 'id',
            'lowestPrice' => 'lowest_price',//基础费用,
            'basePrice' => 'base_price',//起步费
            'perMinutePrice' => 'per_minute_price',//时长费
            'perKiloPrice' => 'per_kilo_price',//里程费
            'beyondStartKilo' => 'beyond_start_kilo',//远途起算公里
            'beyondPerKiloPrice' => 'beyond_per_kilo_price',//远途费(超公里单价)
            'nightStart' => 'night_start', //夜间服务费开始时间
            'nightEnd' => 'night_end',//夜间服务费结束时间
            'nightPerKiloPrice' => 'night_per_kilo_price',//夜间服务费超公里单价,
            'nightPerMinutePrice' => 'night_per_minute_price'//夜间服务费超时间单价,
        ]);
        if (empty($chargeRuleData)) {
            throw new UserException('NO pricing rules', 1001);
        }
        $sectionDetail = ChargeRuleDetail::fetchArray([
            'rule_id' => $chargeRuleData['ruleId'],
            'is_delete' => ChargeRuleDetail::IS_DELETE_NO
        ], [
            'start', 'end', 'per_kilo_price', 'per_minute_price'
        ]);
        $sectionDetail = Common::key2lowerCamel($sectionDetail);
        $chargeRuleData['sectionDetail'] = empty($sectionDetail) ? [] : $sectionDetail;

        return $chargeRuleData;
    }

    public function getChargeRuleList($cityCode, $serviceType, $carLevel)
    {
        $chargeRuleData = ChargeRule::find()->where([
            'city_code' => $cityCode,
            'service_type_id' => $serviceType,
            'car_level_id' => $carLevel,
            'active_status' => 1,
            'is_unuse' => 0,
        ])->select([
            'id',
            'city_code',
            'service_type_id',
            'lowest_price' => 'lowest_price',//基础费用,
            'base_price' => 'base_price',//起步费
            'base_kilo' => 'base_kilo', // 基础价格包含公里数
            'base_minutes' => 'base_minutes', // 基础价格包含时长数(分钟)
            'per_minute_price' => 'per_minute_price',//时长费
            'per_kilo_price' => 'per_kilo_price',//里程费
            'beyond_start_kilo' => 'beyond_start_kilo',//远途起算公里
            'beyond_per_kilo_price' => 'beyond_per_kilo_price',//远途费(超公里单价)
            'night_start' => 'night_start', //夜间服务费开始时间
            'night_end' => 'night_end',//夜间服务费结束时间
            'night_per_kilo_price' => 'night_per_kilo_price',//夜间服务费超公里单价,
            'night_per_minute_price' => 'night_per_minute_price'//夜间服务费超时间单价,
        ])->asArray()->all();
        if (empty($chargeRuleData)) {
            throw new UserException('NO pricing rules', 1001);
        }

        return $chargeRuleData;
    }

    public function getChargeRuleCarLevel($city_code, $service_type_id)
    {
        $list = ChargeRule::find()
            ->where([
                'city_code' => $city_code,
                'service_type_id' => $service_type_id,
                'active_status' => 1,
                'is_unuse' => 0,
            ])->select(['car_level_id'])->asArray()->all();
        if (!empty($list)) {
            return array_column($list, 'car_level_id');
        }
        return [];
    }
}