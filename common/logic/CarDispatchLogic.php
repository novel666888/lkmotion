<?php
/**
 * CarDispatchLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;


use common\models\CarDispatchCapacitySet;
use common\models\CarDispatchDirectRouteOrderRadiusSet;
use common\models\CarDispatchDistributeIntervalSet;
use common\models\CarDispatchDistributeRadiusSet;
use common\models\CarDispatchDistributeSet;
use common\models\CarDispatchSpecialPeriodSet;
use common\models\CarDispatchTimeThresholdSet;
use yii\base\Exception;


class CarDispatchLogic
{

    const I18N_CATEGORY = 'car_dispatch';

    use LogicTrait;

    /**
     * checkSettingByCityCode --根据城市编码校验车辆调度配置信息
     * @author JerryZhang
     * @param $city_code
     * @return array
     * @cache No
     */
    public static function checkSettingByCityCode($city_code){
        $result = [
            'code' => 0,
            'error_message' => [],
            'detail' => [],
        ];
        try{
            $params = [];
            $params['city_code'] = $city_code;
            $rules = CarDispatchCapacitySet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.capacity_not_setting'), 100002);
            }
            $result['detail']['capacity_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $rules = CarDispatchDistributeSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.distribute_not_setting'), 100003);
            }
            $result['detail']['distribute_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $params['is_delete'] = CarDispatchSpecialPeriodSet::IS_DELETE_NO;
            $rules = CarDispatchSpecialPeriodSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.special_period_not_setting'), 100004);
            }
            $result['detail']['special_period_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $rules = CarDispatchDistributeRadiusSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.distribute_radius_not_setting'), 100005);
            }
            $result['detail']['distribute_radius_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $params['is_delete'] = CarDispatchDirectRouteOrderRadiusSet::IS_DELETE_NO;
            $rules = CarDispatchDirectRouteOrderRadiusSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.direct_route_order_radius_not_setting'), 100006);
            }
            $result['detail']['direct_route_order_radius_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $rules = CarDispatchDistributeIntervalSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.distribute_interval_not_setting'), 100007);
            }
            $result['detail']['distribute_interval_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        try{
            $params = [];
            $params['city_code'] = $city_code;
            $rules = CarDispatchTimeThresholdSet::lists($params);
            if(empty($rules)){
                throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.rule.time_threshold_not_setting'), 100008);
            }
            $result['detail']['time_threshold_set'] = $rules;
        }catch (Exception $e){
            $result['code'] = $e->getCode();
            $result['error_message'][] = $e->getMessage();//.'['.$e->getCode().']';
        }

        return $result;
    }

}