<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_direct_route_order_radius_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property int $direct_route_order_type 顺风单类型 1回家单 2接力单 3特殊时段预约单
 * @property int $direct_route_order_radius 顺风单半径（公里）
 * @property int $is_delete 是否删除 0未删除 1已删除
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class CarDispatchDirectRouteOrderRadiusSet extends BaseModel
{

    const DIRECT_ROUTE_ORDER_TYPE_GO_HOME = 1;//顺风单类型-回家单
    const DIRECT_ROUTE_ORDER_TYPE_RELAY = 2;//顺风单类型-接力单
    const DIRECT_ROUTE_ORDER_TYPE_SPECIAL_PERIOD_SUBSCRIBE = 3;//顺风单类型-特殊时段预约单
    const IS_DELETE_YES = 1;//是否删除-已删除
    const IS_DELETE_NO = 0;//是否删除-未删除

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_direct_route_order_radius_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id', 'direct_route_order_type', 'direct_route_order_radius', 'is_delete', 'operator_id'], 'integer'],
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
            'direct_route_order_type' => 'Direct Route Order Type',
            'direct_route_order_radius' => 'Direct Route Order Radius',
            'is_delete' => 'Is Delete',
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
     * @param $direct_route_order_type
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $service_type_id, $direct_route_order_type, $id)
    {
        $query = self::find();
        $query->select('id');
        $query->andWhere(['city_code' => $city_code, 'service_type_id' => $service_type_id, 'direct_route_order_type' => $direct_route_order_type]);
        if ($id) {
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->count();
    }

}
