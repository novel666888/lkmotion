<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
/**
 * This is the model class for table "{{%service_type}}".
 *
 * @property int $id
 * @property string $service_type_name 服务类型名称
 * @property int $service_type_status 服务类型状态 1开启 0暂停
 * @property string $operator_id 操作人id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property string $is_use 使用状态
 */
class ServiceType extends BaseModel
{
    const SERVICE_TYPE_STATUS_YES = 1;//服务类型状态 1开启
    const SERVICE_TYPE_STATUS_NO = 0;//服务类型状态 0暂停

    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%service_type}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['service_type_name', 'service_type_status'], 'required'],
            [['service_type_status', 'operator_id'], 'integer'],
            [['create_time', 'update_time','is_use'], 'safe'],
            [['service_type_name'], 'string', 'max' => 64],
            [['service_type_name', 'service_type_status', 'operator_id'], 'trim'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'service_type_name' => 'Service Type Name',
            'service_type_status' => 'Service Type Status',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_use' => 'Is Use',
        ];
    }

    /**
     * 服务类型列表
     * @return array
     */
    public static function getServiceTypeList(){
        $query = self::find();
        $serviceTypeList = static::getPagingData($query, null, true);
        LogicTrait::fillUserInfo($serviceTypeList['data']['list']);
        return $serviceTypeList['data'];
    }


    /**
     * 服务类型添加
     * @param array $requestData
     * @return int
     */
    public static function getServiceTypeAdd($requestData){
        return static::add($requestData);
    }

    /**
     * 服务类型详情
     * @param int $cityId
     * @return Mixed
     */
    public static function getServiceTypeInfo($serviceTypeId){
        return static::showBatch($serviceTypeId,2);
    }



    /**
     * 服务类型修改
     * @param array $requestData
     * @param int $serviceTypeId
     * @return boolean
     */
    public static function ServicetypeUpdate($requestData,$serviceTypeId){
        $info = self::find()->where(['id'=>$serviceTypeId])->asArray()->all();
        if($info){
            return static::edit($serviceTypeId,$requestData,true);
        }else{
            return false;
        }

    }


    /**
     * 检测字段是否存已经在
     * @param string $checkName
     * @param string $fieldName
     * @return boolean
     */
    public static function getServiceTypeCheck($checkName,$fieldName,$removeId = null){
        $query = self::find()
            ->select("id")
            ->where([$fieldName=>$checkName]);
        if (!empty($removeId))
            $query->andWhere(['!=', 'id', $removeId]);
        $result = $query->asArray()->all();
        if($result)
            return false;
        else
            return true;
    }

    /**
     * 更改服务类型使用状态
     * @param string $serviceTypeId
     * @return boolean
     */
    public static function getServiceTypeUse($serviceTypeId){
        $serviceData = self::find()->where(['id'=>$serviceTypeId])->one();
        if($serviceData){
            return true;
        }else{
            return false;
        }
    }


    /*
     * 获取开启开启或全部得服务类型信息
     *  @param string
     * @return array
     * */
    public static function getServiceTypeName($serviceType = null){
        $query = self::find()
            ->select("id,service_type_name");
        if (!empty($serviceType)){
            $query->where("service_type_status = 1");
        }
        $result = $query->asArray()->all();
        return $result;
    }

    /*
     * 获取开启开启或全部得服务类型信息
     *  @param string
     * @return array
     * */
    public static function getServiceTypeData($serviceTypeData){
        $query = self::find()->select("id");
        $query->where([ 'service_type_status'=>1]);
        $query->andWhere(['id'=>$serviceTypeData]);
        $result = $query->asArray()->column();
        return $result;
    }


}
