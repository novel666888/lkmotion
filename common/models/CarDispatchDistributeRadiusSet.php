<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_distribute_radius_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property int $min_radius 最小派单半径（公里）
 * @property int $min_radius_first_push_driver_count 最小派单半径首次推送司机数量
 * @property int $max_radius 最大派单半径（公里）
 * @property int $max_radius_first_push_driver_count 最大派单半径首次推送司机数量
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class CarDispatchDistributeRadiusSet extends BaseModel
{

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_distribute_radius_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id', 'min_radius', 'min_radius_first_push_driver_count', 'max_radius', 'max_radius_first_push_driver_count', 'operator_id'], 'integer'],
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
            'min_radius' => 'Min Radius',
            'min_radius_first_push_driver_count' => 'Min Radius First Push Driver Count',
            'max_radius' => 'Max Radius',
            'max_radius_first_push_driver_count' => 'Max Radius First Push Driver Count',
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
