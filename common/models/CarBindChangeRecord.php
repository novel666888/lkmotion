<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%car_bind_change_record}}".
 *
 * @property int $id
 * @property int $car_info_id 车辆ID
 * @property string $bind_tag 绑定类型
 * @property string $create_time 记录时间
 * @property string $bind_value 绑定内容
 * @property int $bind_type 0绑定,1解绑
 * @property int $operator_id 操作人ID
 */
class CarBindChangeRecord extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%car_bind_change_record}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['car_info_id', 'bind_tag', 'bind_value', 'operator_id'], 'required'],
            [['car_info_id', 'bind_type', 'operator_id'], 'integer'],
            [['create_time'], 'safe'],
            [['bind_tag'], 'string', 'max' => 30],
            [['bind_value'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'car_info_id' => 'Car Info ID',
            'bind_tag' => 'Bind Tag',
            'create_time' => 'Create Time',
            'bind_value' => 'Bind Value',
            'bind_type' => 'Bind Type',
            'operator_id' => 'Operator ID',
        ];
    }

    /**
     * getBindRecord
     * @param array $carInfoId
     * @return Mixed
     * @author liurn
     */
    public static function getBindRecord($carInfoId){
        $carInfo = self::find()->select("bind_value")->where(['car_info_id' => $carInfoId])
            ->andWhere(['=','bind_type',0])
            ->andWhere(['<>','bind_tag','driver'])->asArray()->all();
        return array_column($carInfo,'bind_value');
    }

    /**
     * addBindRecord
     * @param array $carDataRes
     * @param int $carInfoId
     * @param int $operatorId
     * @author liurn
     */
    public static function addBindRecord($carDataRes,$carInfoId,$operatorId){
        foreach ($carDataRes as $key => $val ){
            $data['car_info_id'] = $carInfoId;
            $data['bind_tag'] = array_keys(json_decode($val,true))[0];
            $data['bind_value'] = $carDataRes[$key];
            $data['bind_type'] = 0;
            $data['operator_id'] = $operatorId;

            $user = new CarBindChangeRecord();
            $user->attributes = $data;
            $user->save();
        }
    }



    /**
     * updateBindRecord
     * @param array $carDataRes
     * @param int $carInfoId
     * @param int $operatorId
     * @author liurn
     */
    public static function updateBindRecord($carDataRes,$carInfoId,$operatorId){
        $carInfo = self::find()->where(['car_info_id' => $carInfoId])
            ->andWhere(['=','bind_type',0])
            ->andWhere(['bind_tag' => array_keys(json_decode($carDataRes,true))[0]])->asArray()->one();
        \Yii::info($carInfo, 'carInfo');
        if($carInfo){
            $updateData['car_info_id'] = $carInfoId;
            $updateData['bind_tag'] = $carInfo['bind_tag'];
            $updateData['bind_value'] = $carInfo['bind_value'];
            $updateData['bind_type'] = 1;
            $updateData['operator_id'] = $operatorId;
            $carInfoUp = new CarBindChangeRecord();
            $carInfoUp->setAttributes($updateData);
            $carInfoUp->save();
        }

        $data['car_info_id'] = $carInfoId;
        $data['bind_tag'] = array_keys(json_decode($carDataRes,true))[0];
        $data['bind_value'] = $carDataRes;
        $data['bind_type'] = 0;
        $data['operator_id'] = $operatorId;

        $carBindRecorda = new CarBindChangeRecord();
        $carBindRecorda->attributes = $data;
        $carBindRecorda->save();
    }



}
