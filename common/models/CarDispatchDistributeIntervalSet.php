<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_distribute_interval_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property int $car_service_before_interval 用车服务前间隔（分钟）
 * @property int $car_service_after_interval 用车服务后间隔（分钟）
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class CarDispatchDistributeIntervalSet extends BaseModel
{

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_distribute_interval_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id', 'car_service_before_interval', 'car_service_after_interval', 'operator_id'], 'integer'],
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
            'car_service_before_interval' => 'Car Service Before Interval',
            'car_service_after_interval' => 'Car Service After Interval',
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
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $service_type_id, $id)
    {
        $query = self::find();
        $query->select('id');
        $query->andWhere(['city_code' => $city_code, 'service_type_id' => $service_type_id]);
        if ($id) {
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->count();
    }

}
