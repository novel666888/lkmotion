<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_time_threshold_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property int $time_threshold_type 时间阈值类型 1开启立即用车派单逻辑 2预约用车待派订单开启强派模式
 * @property int $time_threshold 时间阈值（分钟）
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class CarDispatchTimeThresholdSet extends BaseModel
{

    const TIME_THRESHOLD_TYPE_COMMON = 1;//时间阈值类型-开启立即用车派单逻辑
    const TIME_THRESHOLD_TYPE_SPECIAL_FORCE = 2;//时间阈值类型-预约用车待派订单开启强派模式
    const TIME_THRESHOLD_TYPE_PICKUP_SPECIAL_FORCE = 3;//时间阈值类型-接机用车待派订单开启强派模式
    const TIME_THRESHOLD_TYPE_DROP_OFF_SPECIAL_FORCE = 4;//时间阈值类型-送机用车待派订单开启强派模式
    const TIME_THRESHOLD_TYPE_HALF_DAY_SPECIAL_FORCE = 5;//时间阈值类型-包车4小时待派订单开启强派模式
    const TIME_THRESHOLD_TYPE_ONE_DAY_SPECIAL_FORCE = 6;//时间阈值类型-包车8小时待派订单开启强派模式

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_time_threshold_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id', 'time_threshold_type', 'time_threshold', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['city_code'], 'string', 'max' => 70],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city_code' => 'City Code',
            'service_type_id' => 'Service Type ID',
            'time_threshold_type' => 'Time Threshold Type',
            'time_threshold' => 'Time Threshold',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * checkData --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $time_threshold_type
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $service_type_id, $time_threshold_type, $id)
    {
        $query = self::find();
        $query->select('id');
        $query->andWhere(['city_code' => $city_code, 'service_type_id' => $service_type_id, 'time_threshold_type' => $time_threshold_type]);
        if ($id) {
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->count();
    }

}
