<?php

namespace common\models;

use Yii;
use common\logic\ValuationLogic;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
use common\logic\ServiceLogic;
use common\logic\CarDispatchLogic;
/**
 * This is the model class for table "{{%city}}".
 *
 * @property int $id
 * @property string $city_name 城市名称
 * @property string $city_code 城市编码
 * @property string $city_longitude 城市中心经度
 * @property string $city_latitude 城市中心维度
 * @property int $order_risk_top 下单风险上限值
 * @property int $city_status 是否开通 0未开通 1开通
 * @property string $operator_id 操作人
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class City extends \common\models\BaseModel
{
    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%city}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['city_name','order_risk_top', 'city_status','city_code', 'city_longitude_latitude'], 'required'],
            [['city_name','order_risk_top', 'city_status','city_code', 'city_longitude_latitude'], 'trim'],
            [['city_name', 'city_longitude_latitude'], 'string', 'max' => 64],
            [['order_risk_top', 'operator_id', 'city_status'], 'integer'],
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
            'city_name' => 'City Name',
            'city_code' => 'City Code',
            'city_longitude_latitude' => 'City Longitude Latitude',
            'order_risk_top' => 'Order Risk Top',
            'city_status' => 'City Status',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 城市列表(全部城市)
     * @return array
     */
    public static function getCityList($condition=null, $field=['*'], $sort=null, $returnPageInfo=true){
        $query = self::find()->select($field);
        if(!empty($condition['cityStatus'])){
            $query = $query->andFilterWhere(['city_status'=>intval($condition['cityStatus'])]);
        }

        $cityList = static::getPagingData($query, $sort, $returnPageInfo);
        //LogicTrait::fillUserInfo($cityList['data']['list']);
        return $cityList['data'];
    }

    /**
     * 城市列表（返回开通的服务类型）
     * @return array
     */
    public static function getCityDetailList($condition=null, $field=['*'], $sort=null){
        $result = self::getCityList($condition, $field, $sort, false);
        if(!empty($result)){
            foreach($result as $k => &$v){
                $rs = ServiceLogic::serviceTypeStatus($v['city_code']);
                if(empty($rs)){
                    unset($result[$k]);
                }else{
                    $v['services'] = $rs;
                }
            }
            return ['code'=>0, 'data'=>$result];
        }else{
            return ['code'=>-1, 'message'=>'No cities open'];
        }
    }

    /**
     * 城市详情
     * @param int $cityId
     * @return Mixed
     */
    public static function getCityInfo($cityCode){
        $cityInfo = self::find()->where(['city_code'=>$cityCode])->indexBy('city_code')->asArray()->all();
        LogicTrait::fillUserInfo($cityInfo);
        return $cityInfo;
    }


    /**
     * 0.关城（关闭城市后，关闭该城市下的所有服务和记费规则）
     * 1.开城（该城市下有大于等于一个服务列表，大于等于一个计费规则，大于等于一个派单规则时即可开城）
     * @param int $cityId
     * @param int $cityStatus
     * @return Mixed
     */
    public static function  getCityStatus($cityId,$cityStatus,$operator_id){
        $city = self::find()->select('city_code,city_status')->where(['id'=>$cityId])->asArray()->one();

        if($cityStatus==0 && $city['city_status']==1){//关闭城市
            Service::serviceStatusUpdate($city['city_code']);
            ValuationLogic::switchRuleByCityCode(1,$city['city_code'],$operator_id);
            $errorInfo = array();
        }
        if($cityStatus==1 && $city['city_status']==0){//开启城市
            $valuation = ValuationLogic::checkSettingByCityCode($city['city_code']);
            $errorInfo[] = $valuation['error_message'];

            $carDispatch = CarDispatchLogic::checkSettingByCityCode($city['city_code']);
            $errorInfo[] = $carDispatch['error_message'];

            if(!empty($errorInfo))
                $errorInfo = array_reduce($errorInfo, 'array_merge', array());

            $service = Service::checkCityServiceStatus($city['city_code']);
            if(!$service){
                $errorInfo[]= "该城市下没有开启得服务";
            }
            $errorStr=implode(".|", $errorInfo);
            return $errorStr;
        }
    }


    /**
     * 检测字段是否存已经在
     * @param string $checkName
     * @param string $fieldName
     * @return boolean
     */
    public static function getCityCheck($checkName,$fieldName,$removeId = null){
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

    /*
     * 获取开启城市或全部得城市信息
     *  @param string
     * @return array
     * */
    public static function getCityName($city_status = null){
        $query = self::find()
            ->select("city_name,city_code");
        if (!empty($city_status))
            $query->where([ 'city_status'=>$city_status]);
        $result = $query->asArray()->all();
        return $result;
    }


    /*
     * 查询城市状态
     *  @param int cityCode
     * @return array
     * */
    public static function checkCityStatus($cityCode){
        $result = self::find()->select("city_status")->where([ 'city_code'=>$cityCode])->asArray()->one();
        if($result)
            return $result['city_status'];
        else
            return 0;
    }


}
