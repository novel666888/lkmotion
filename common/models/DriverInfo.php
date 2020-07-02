<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "{{%driver_info}}".
 *
 * @property int $id
 * @property string $phone_number 司机手机号
 * @property int $driver_leader 司机主管
 * @property string $driver_name 司机姓名
 * @property string $register_time 注册时间
 * @property string $balance 余额
 * @property int $gender 性别
 * @property int $car_id 车辆id
 * @property int $is_following 是否是顺风单0否 1是
 * @property int $work_status 司机工作状态
 0：非出车状态，
 1：出车，未接订单，
 2：出车，接到订单,
 3：暂停接单
 * @property string $head_img 司机头像
 * @property string $city_code 城市代码
 * @property string $bind_time 绑定时间
 * @property int $use_status 启用停用状态，0：停用，1：启用
 * @property int $cs_work_status 车机工作状态
 0：车机未登录登录
 1：车机登录
 2：车机听单
 3：车机暂停听单
 4：车机收车
 
 
 * @property int $sign_status 1：已签约，0：已解约
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class DriverInfo extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_leader', 'gender', 'car_id', 'is_following', 'work_status', 'use_status', 'cs_work_status', 'sign_status'], 'integer'],
            [['register_time', 'bind_time', 'create_time', 'update_time'], 'safe'],
            [['balance'], 'number'],
            [['phone_number'], 'string', 'max' => 32],
            [['driver_name'], 'string', 'max' => 16],
            [['head_img'], 'string', 'max' => 256],
            [['city_code'], 'string', 'max' => 8],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone_number' => 'Phone Number',
            'driver_leader' => 'Driver Leader',
            'driver_name' => 'Driver Name',
            'register_time' => 'Register Time',
            'balance' => 'Balance',
            'gender' => 'Gender',
            'car_id' => 'Car ID',
            'is_following' => 'Is Following',
            'work_status' => 'Work Status',
            'head_img' => 'Head Img',
            'city_code' => 'City Code',
            'bind_time' => 'Bind Time',
            'use_status' => 'Use Status',
            'cs_work_status' => 'Cs Work Status',
            'sign_status' => 'Sign Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 获取司机详情
     *
     * @param int $driverId
     * @return array
     */
    public static function getDriverDetail($driverId){
        $query = self::find();
        $driverDetail = $query->select(['*'])->where(['id'=>$driverId])->asArray()->one();
        return $driverDetail;
    }

    /**
     * 检查司机是否注册
     *
     * @param int $driverId
     * @return boolean
     */
    public static function checkDriver($driverId){
        if (empty($driverId)){
            return false;
        }
        $isHave = self::fetchOne(['id'=>$driverId]);
        if(empty($isHave)){
            return false;
        }
        return true;
    }
}
