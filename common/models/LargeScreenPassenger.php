<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "tbl_large_screen_passenger".
 *
 * @property int $id
 * @property string $passenger_info_id 乘客id
 * @property string $device_code 设备号
 * @property string $login_time
 * @property string $logout_time
 * @property int $login_status 登录状态：1：登录，2：退出登录
 * @property string $repair_time
 */
class LargeScreenPassenger extends \common\models\BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_large_screen_passenger';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['passenger_info_id', 'login_status'], 'integer'],
            [['device_code'], 'required'],
            [['login_time', 'logout_time', 'repair_time'], 'safe'],
            [['device_code'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'passenger_info_id' => 'Passenger Info ID',
            'device_code' => 'Device Code',
            'login_time' => 'Login Time',
            'logout_time' => 'Logout Time',
            'login_status' => 'Login Status',
            'repair_time' => 'Repair Time',
        ];
    }


    /**
     * 乘客查询是否已经登录
     * @param  array $condition 条件
     * @return array            
     */
    public static function loginState($condition){
        if(empty($condition['passengerId'])){
            //格式错误
            return ["code"=>2, "message"=>"Parameter error"];
        }
        $model = self::find()->select(['*']);
        if(!empty($condition['passengerId'])){
            $model = $model->andFilterWhere(['passenger_info_id'=>intval($condition['passengerId'])]);
        }
        if(!empty($condition['deviceCode'])){
            $model = $model->andFilterWhere(['device_code'=>intval($condition['deviceCode'])]);
        }
        //查找登录状态的记录
        $model = $model->andFilterWhere(['login_status'=>1]);

        $data = $model->asArray()->one();
        if(!empty($data)){
            //登录状态
            return ["code"=>0, "message"=>"Already log in",  "data"=>$data];
        }else{
            //未登录状态
            return ["code"=>1, "message"=>"Already log out"];
        }

    }


}
