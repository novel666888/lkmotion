<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_car_dispatch_distribute_set".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $is_force_distribute 是否开启强派 0不开启 1开启
 * @property string $create_time 创建时间
 * @property string $update_time
 */
class CarDispatchDistributeSet extends BaseModel
{

    const IS_FORCE_DISTRIBUTE_NO = 0;//是否开启强派-不开启
    const IS_FORCE_DISTRIBUTE_YES = 1;//是否开启强派-开启

    use ModelTrait;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_car_dispatch_distribute_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_force_distribute', 'operator_id'], 'integer'],
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
            'is_force_distribute' => 'Is Force Distribute',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * checkData --
     * @author JerryZhang
     * @param $city_code
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $id){
        $query = self::find();
        $query->select('id');
        $query->andWhere(['city_code'=>$city_code]);
        if($id){
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->count();
    }

}
