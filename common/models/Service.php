<?php

namespace common\models;

use common\logic\ValuationLogic;
use Yii;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
use common\models\ServiceType;

/**
 * This is the model class for table "{{%service}}".
 *
 * @property int $id
 * @property int $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property int $together_order_number 同时可下单数量
 * @property int $service_status 服务开启状态 0暂停 1开启
 * @property int $operator_id 操作人id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class Service extends BaseModel
{
    const SERVICE_STATUS_YES = 1;//服务状态 1开启
    const SERVICE_STATUS_NO = 0;//服务状态 0暂停

    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%service}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['city_code', 'service_type_id', 'service_status', 'operator_id'], 'required'],
            [['city_code', 'service_type_id', 'service_status', 'operator_id'], 'trim'],
            [['service_type_id', 'together_order_number', 'service_status', 'operator_id'], 'integer'],
            [['city_code'], 'string', 'max' => 32],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city_code' => 'City Code',
            'service_type_id' => 'Service Type ID',
            'together_order_number' => 'Together Order Number',
            'service_status' => 'Service Status',
            'operator_id' => 'Operator ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * 服务列表
     * @return array
     */
    public static function getServiceList($requestData){

        $query = self::find();
        if ($requestData['city_code'])
            $query->Where(['city_code'=>$requestData['city_code']]);
        if ($requestData['service_type_id'])
            $query->andWhere(['service_type_id'=>$requestData['service_type_id']]);
        if ($requestData['service_status'] === '0' || $requestData['service_status'] == 1)
            $query->andWhere(['service_status'=>intval($requestData['service_status'])]);

        $serviceData = self::getPagingData($query, null, true);
        \Yii::info($serviceData, 'serviceData');
        LogicTrait::fillUserInfo($serviceData['data']['list']);
        //城市名称
        $cityQuery=City::find();
        $cityArr=$cityQuery->select("id,city_name,city_code")->asArray()->all();
        $cityData=array_column($cityArr,'city_name','city_code');

        //服务类型名称
        $serviceTypeQuery=ServiceType::find();
        $serviceTypeArr=$serviceTypeQuery->select("id,service_type_name")->asArray()->all();
        $serviceTypeData=array_column($serviceTypeArr,'service_type_name','id');
        foreach($serviceData['data']['list'] as $key=>$value){
            $serviceData['data']['list'][$key]['city_name']=$cityData[$value['city_code']];
            $serviceData['data']['list'][$key]['service_type_name']=$serviceTypeData[$value['service_type_id']];
        }
        return $serviceData['data'];
    }



    /** 服务添加
     * @return string
     */
    public static function getServiceAdd($requestData){

        $typeCheck = ServiceType::getServiceTypeUse($requestData['service_type_id']);
        $cityCheck = City::getCityInfo($requestData['city_code']);
        $service = Service::find()->select("id")
            ->where(['city_code'=>$requestData['city_code']])
            ->andWhere(['service_type_id'=>$requestData['service_type_id']])->asArray()->all();
        if($service) return -1;

        if($typeCheck && $cityCheck){
            return static::add($requestData);
        }else{
            return 0;
        }
    }

    /** 服务修改
     * @return string
     */
    public static function ServiceUpdate($requestData,$serviceId){
        return static::edit($serviceId,$requestData,true);
    }


    /**
     * 一键关城（关闭服务后 关闭该服务计费规则）
     * @param int $serviceId
     * @param int $serviceStatus
     * @return Mixed
     */
    public static function  ServiceStatus($serviceStatus,$cityCode,$operator_id){
        if($serviceStatus==0)
            ValuationLogic::switchRuleByCityCode(1,$cityCode,$operator_id);
    }

    /** 一键关城（关闭城市后，关闭该城市下的所有服务和记费规则）
     * @return string
     */
    public static function serviceStatusUpdate($cityCode){
        $params['city_code'] = $cityCode ;
        $data = self::lists($params);
        foreach ($data as $v){
            if(isset($v)){
                $res = self::edit($v['id'], ['service_status' => 0]);
                if(!$res){
                    throw new Exception(\Yii::t(self::I18N_CATEGORY, 'error.operation.fail'), 1);
                }
            }
        }
    }





    /**
     * 服务列表
     * @return array
     */
    public static function getCityServiceType($operatingType){
       if($operatingType==1){
           $data['cityData'] = City::getCityName(1);
           $data['serviceTypeData'] = ServiceType::getServiceTypeName(1);
       }
       if($operatingType==2){
           $data['cityData'] = City::getCityName(null);
           $data['serviceTypeData'] = ServiceType::getServiceTypeName(null);
       }
        return $data;
    }


    /**
     * 参数处理
     * @param $params
     * @return \yii\db\ActiveQuery
     */
    public static function get_query($params)
    {

        $query = self::find();

        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        $query->orderBy(['id' => SORT_DESC]);

        return $query;
    }

    /**
     * 按条件查询服务类型
     * @param $cityCode
     * @param $serviceTypeId
     * @return int
     */
    public static function checkServiceStatus($cityCode,$serviceTypeId)
    {
        $query = self::find();
        $query->select("service_status");
        if (!empty($cityCode))
            $query->where([ 'city_code'=>$cityCode]);
        if(!empty($serviceTypeId))
            $query->andWhere([ 'service_type_id'=>$serviceTypeId]);

        $result = $query->asArray()->one();
        if($result)
            return $result['service_status'];
        else
            return 0;
    }

    /**
     * 查询城市下开启的服务
     * @param $cityCode
     * @param $serviceTypeId
     * @return array
     */
    public static function checkCityServiceStatus($cityCode)
    {
        $result = self::find()->select("service_status")
            ->where([ 'city_code'=>$cityCode])
            ->andWhere(['service_status'=>self::SERVICE_STATUS_YES])
            ->asArray()->all();
        if($result)
            return $result;
        else
            return array();
    }

    /**
     * 返回同时可下单数量
     * @param $cityCode
     * @param $serviceTypeId
     * @return int
     */
    public static function serviceOrderNumber ($cityCode,$serviceTypeId)
    {
        $query = self::find();
        $query->select("together_order_number");
        if (!empty($cityCode)){
            $query->where([ 'city_code'=>$cityCode]);
        }
        if(!empty($serviceTypeId)){
            $query->andWhere([ 'service_type_id'=>$serviceTypeId]);
        }
        $result = $query->asArray()->one();
        if($result)
            return $result['together_order_number'];
        else
            return false;
    }

    /**
     * 返回指定城市下开启得服务类型
     * @param $cityCode
     * @return array
     */
    public static function getServiceTypeStatus ($cityCode)
    {
        $query = self::find()->select("service_type_id")->where(['city_code'=>$cityCode, 'service_status'=>1]);
        $serviceTypeIdResult = $query->asArray()->column();
        if(!$serviceTypeIdResult) return array();
        return ServiceType::getServiceTypeData($serviceTypeIdResult);
    }

    /** 开启城市时判断是否有一个开启的服务
     * @return string
     */
    public static function serviceStatusInfo($cityCode){
        $cityService = self::find()->select("service_type_id")->where(['city_code'=>$cityCode, 'service_status'=>1]);
        if($cityService){
            return array('');
        }else{
            return array('');
        }

    }

}
