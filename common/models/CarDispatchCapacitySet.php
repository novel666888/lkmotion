<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_capacity_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $car_service_period 车辆服务时段 1白天2夜晚
 * @property int $spare_driver_count 空闲司机数量
 * @property string $create_time 创建时间
 * @property string $update_time
 */
class CarDispatchCapacitySet extends BaseModel
{

//    const CAR_SERVICE_PERIOD_DAY = 1;//车辆服务时段-白天
//    const CAR_SERVICE_PERIOD_NIGHT = 2;//车辆服务时段-晚上

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_capacity_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['spare_driver_count', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['car_service_period', 'city_code'], 'string', 'max' => 70],
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
            'car_service_period' => 'Car Service Period',
            'spare_driver_count' => 'Spare Driver Count',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = self::find();
        if (isset($params['is_delete'])) {
            $query->andWhere(['is_delete' => $params['is_delete']]);
        }
        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }

        $query->orderBy('`city_code` + 0 ASC, `id` DESC');

        return $query;
    }

    /**
     * checkData --
     * @author JerryZhang
     * @param $city_code
     * @param $car_service_period
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $car_service_period, $id)
    {

        if(!is_array($car_service_period)){
            $car_service_period = json_decode($car_service_period, true);
        }

        $query = self::find();
        $query->select('car_service_period');
        $query->andWhere(['city_code' => $city_code]);
        if ($id) {
            $query->andWhere(['<>', 'id', $id]);
        }
        $list = $query->asArray()->all();

        foreach($list as $v){
            if(isset($v)){
                $car_service_period_exist = json_decode($v['car_service_period'], true);
                if(!($car_service_period['end'] <= $car_service_period_exist['start'] || $car_service_period['start'] >= $car_service_period_exist['end'])){
                    return true;
                }
            }
        }

        return false;
    }

}
