<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\models\City;

/**
 * This is the model class for table "{{%airport_terminal_manage}}".
 *
 * @property int $id
 * @property string $city_code 城市编码
 * @property string $airport_name 机场名称
 * @property string $terminal_name 航站楼名称
 * @property string $terminal_longitude_latitude 航站楼经纬度
 * @property int $airport_terminal_status 状态 1开启 0禁用
 * @property int $operator_id 操纵人id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class AirportTerminalManage extends \yii\db\ActiveRecord
{
    const AIRPORT_TERMINAL_YES = 1;//airport_terminal_status机场航站楼开启状态 1开启
    const AIRPORT_TERMINAL_NO = 0;//airport_terminal_status机场航站楼开启 0禁用

    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%airport_terminal_manage}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['city_code', 'airport_name', 'terminal_name', 'terminal_longitude_latitude', 'operator_id'], 'required'],
            [['city_code', 'airport_name', 'terminal_name', 'terminal_longitude_latitude', 'operator_id'], 'trim'],
            [['airport_terminal_status', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['city_code'], 'string', 'max' => 32],
            [['airport_name', 'terminal_name', 'terminal_longitude_latitude'], 'string', 'max' => 64],
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
            'airport_name' => 'Airport Name',
            'terminal_name' => 'Terminal Name',
            'terminal_longitude_latitude' => 'Terminal Longitude Latitude',
            'airport_terminal_status' => 'Airport Terminal Status',
            'operator_id' => 'Operator ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * 机场航站楼列表
     * @return array
     */
    public static function getAirportTerminalList($condition=null, $field=['*'], $sort=null, $returnPageInfo=true){
        $query = self::find()->select($field);
        if(!empty($condition['airport_terminal_status'])){
            $query = $query->andFilterWhere(['airport_terminal_status'=>intval($condition['airport_terminal_status'])]);
        }
        $airportTerminalList = static::getPagingData($query, $sort, $returnPageInfo);

        $cityQuery=City::getCityList(null, ['id','city_name','city_code'], null, true);
        $cityData = array_column($cityQuery['list'],'city_name','city_code');

        foreach($airportTerminalList['data']['list'] as $key=>$value){
            $airportTerminalList['data']['list'][$key]['city_name']=$cityData[$value['city_code']];
        }
        return $airportTerminalList['data'];
    }


    /**
     * 机场航站楼详情
     * @return array
     */
    public static function getAirportTerminalInfo($id){
        $airportTerminalInfo = self::find()->where(['id'=>$id])->asArray()->all();
        $cityData = city::getCityInfo($airportTerminalInfo[0]['city_code']);
        $airportTerminalInfo[0]['city_name'] = $cityData[$airportTerminalInfo[0]['city_code']]['city_name'];
        return $airportTerminalInfo;
    }


    /** 机场航站楼添加
     * @return string
     */
    public static function getAirportTerminalAdd($requestData){
        if(City::getCityInfo($requestData['city_code']))
            return static::add($requestData);
        else
            return false;
    }

    /**
     * 机场航站楼修改
     * @param array $requestData
     * @param int $id
     * @return boolean
     */
    public static function getAirportTerminalUpdate($requestData,$id){
        if(empty($requestData['city_code'])){
            return self::edit($id,$requestData,true);
        }
        if(City::getCityInfo($requestData['city_code']))
            return self::edit($id,$requestData,true);
        else
            return false;
    }

    /**
     * 通过cityCode获取机场航站楼
     * @param array $requestData
     * @param int $id
     * @return array|boolean
     */
    public static function getCityAirportTerminal($cityCode){
        if(!$cityCode) return false;
        if(City::getCityInfo($cityCode)){
            $result = self::find()->select("airport_name,terminal_name,terminal_longitude_latitude")
                ->where("airport_terminal_status=1")
                ->andWhere("city_code=$cityCode")
                ->asArray()->all();
            $AirportTerminal = [];
            foreach ($result as $key =>$value){
                $AirportTerminal[$key]['terminal_name'] = $value['airport_name'].$value['terminal_name'];
                $AirportTerminal[$key]['terminal_longitude_latitude'] = $value['terminal_longitude_latitude'];
            }
            if(!$AirportTerminal){
                return false;
            }
            return $AirportTerminal;
        }else{
            return false;
        }
    }
}
