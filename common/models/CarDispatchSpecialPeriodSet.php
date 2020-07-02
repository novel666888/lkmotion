<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_special_period_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id
 * @property string $time_period 时段设置
 * @property int $is_delete 是否删除 0未删除 1已删除
 * @property string $create_time 创建时间
 * @property string $update_time
 */
class CarDispatchSpecialPeriodSet extends BaseModel
{

    const IS_DELETE_YES = 1;//是否删除-已删除
    const IS_DELETE_NO = 0;//是否删除-未删除

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_special_period_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['service_type_id'], 'required'],
            [['service_type_id', 'is_delete', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['city_code'], 'string', 'max' => 70],
            [['time_period'], 'string', 'max' => 255],
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
            'time_period' => 'Time Period',
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
