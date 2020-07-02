<?php
/**
 * LogicTrait.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;

use common\models\CarLevel;
use common\models\Channel;
use common\models\City;
use common\models\ListArray;
use common\models\ServiceType;
use common\models\SysUser;
use yii\helpers\ArrayHelper;


trait LogicTrait
{

    /**
     * fillUserInfo --
     * @author JerryZhang
     * @param $data
     * @param string $user_id_key 需要填充的用户id的key值
     * @param string $filled_info_key 需要填充的用户id的key值
     * @cache No
     */
    public static function fillUserInfo(&$data, $user_id_key = 'operator_id', $filled_info_key = 'operator_info')
    {
        if (empty($data)) {
            return;
        }

        $operator_ids = array_unique(ArrayHelper::getColumn($data, $user_id_key));
        $operator_info = SysUser::showBatch($operator_ids);

        foreach ($data as &$v) {
            $v[$filled_info_key] = isset($operator_info[$v[$user_id_key]]) ? $operator_info[$v[$user_id_key]] : [];
        }

    }

    /**
     * fillData --
     * @author JerryZhang
     * @param $data
     * @param array $fill_keys 要填充的key ['city_code','service_type_id','car_level_id','channel_id']
     * @cache No
     */
    public static function fillBaseData(&$data, $fill_keys)
    {
        if (empty($data)) {
            return;
        }

        $city_codes_info = [];
        if (in_array('city_code', $fill_keys)) {
            $city_codes = array_unique(ArrayHelper::getColumn($data, 'city_code'));
            $city_codes_info = City::find()->select('*')->where(['city_code' => $city_codes])->asArray()->all();
            $city_codes_info = ArrayHelper::index($city_codes_info, 'city_code');
        }

        $service_type_info = [];
        if (in_array('service_type_id', $fill_keys)) {
            $service_type_ids = array_unique(ArrayHelper::getColumn($data, 'service_type_id'));
            $service_type_info = ServiceType::showBatch($service_type_ids);
        }

        $car_level_info = [];
        if (in_array('car_level_id', $fill_keys)) {
            $car_level_ids = array_unique(ArrayHelper::getColumn($data, 'car_level_id'));
            $car_level_info = CarLevel::showBatch($car_level_ids);
        }

        $channel_info = [];
        if (in_array('channel_id', $fill_keys)) {
            $channel_ids = array_unique(ArrayHelper::getColumn($data, 'channel_id'));
            $channel_info = Channel::showBatch($channel_ids);
        }

        foreach ($data as &$v) {
            if (!empty($v['city_code'])) {
                $v['city_code_text'] = !empty($city_codes_info[$v['city_code']]) ? $city_codes_info[$v['city_code']]['city_name'] : '未知';
            }
            if (!empty($v['service_type_id'])) {
                $v['service_type_id_text'] = !empty($service_type_info[$v['service_type_id']]) ? $service_type_info[$v['service_type_id']]['service_type_name'] : '未知';
            }
            if (!empty($v['car_level_id'])) {
                $v['car_level_id_text'] = !empty($car_level_info[$v['car_level_id']]) ? $car_level_info[$v['car_level_id']]['label'] : '未知';
            }
            if (!empty($v['channel_id'])) {
                $v['channel_id_text'] = !empty($channel_info[$v['channel_id']]) ? $channel_info[$v['channel_id']]['channel_name'] : '未知';
            }
        }

    }

    /**
     * getBaseInfo --获取基础信息
     * @author JerryZhang
     * @param $filter_city
     * @param $filter_service_type
     * @param $filter_channel
     * @param $filter_car_level
     * @param array $type
     * @return array
     * @cache No
     */
    public static function getBaseInfo($filter_city, $filter_service_type, $filter_channel, $filter_car_level, $type = ['city', 'service_type', 'channel', 'car_level'])
    {
        $data = [];
        $obj_list_arr = new ListArray();
        if (in_array('city', $type)) {
            $city_list = $obj_list_arr->getCityList($filter_city);
            $data['city'] = $city_list;
        }
        if (in_array('city', $type)) {
            $service_type_list = $obj_list_arr->getServiceType($filter_service_type);
            $data['service_type'] = $service_type_list;
        }
        if (in_array('channel', $type)) {
            $channel_list = $obj_list_arr->getChannelList($filter_channel);
            $data['channel'] = $channel_list;
        }
        if (in_array('car_level', $type)) {
            $car_level_list = $obj_list_arr->getCarLevel($filter_car_level);
            $data['car_level'] = $car_level_list;
        }

        return $data;
    }

}