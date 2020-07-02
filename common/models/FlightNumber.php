<?php

namespace common\models;

use Yii;
use common\api\FlightApi;

/**
 * This is the model class for table "tbl_flight_number".
 *
 * @property int $id
 * @property string $flight_number 航班号
 * @property string $flight_date 航班日期
 * @property int $passenger_info_id 乘客id
 * @order_id int $order_id 订单id
 * @property string $start_code 起点code
 * @property string $end_code 终点code
 * @property string $create_time 创建记录日期
 */
class FlightNumber extends \common\models\BaseModel
{
    const IS_SUBSCRIBE_YES= 1;//是否订阅：1订阅
    const IS_SUBSCRIBE_NO= 0;//是否订阅：0取消订阅
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_flight_number';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['passenger_info_id','order_id'], 'integer'],
            [['flight_date', 'create_time'], 'safe'],
            [['flight_number'], 'string', 'max' => 30],
            [['start_code', 'end_code'], 'string', 'max' => 3],
            [['is_subscribe'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flight_number' => 'Flight Number',
            'flight_date' => 'Flight Date',
            'passenger_info_id' => 'Passenger Info ID',
            'start_code' => 'Start Code',
            'order_id' => 'Order Id',
            'end_code' => 'End Code',
            'create_time' => 'Create Time',
            'is_subscribe' => 'Is Subscribe',
        ];
    }

    /**
     * 返回所有订阅的用户ID
     */
    public static function findSubscribeUser($flightNo, $flightDate){
        if(empty($flightNo) || empty($flightDate)){
            return false;
        }
        $model = self::find()->select(['passenger_info_id']);
        $model = $model->andFilterWhere(['flight_number'=>$flightNo])
            ->andFilterWhere(['flight_date'=>$flightDate])
            ->andFilterWhere(['is_subscribe'=>1])->asArray()->all();
        if($model){
            return array_unique($model, SORT_REGULAR);
        }else{
            return false;
        }
    }


    /**
     * 批量更新
     * @param array $updata 要更新的数据
     * @param array $condition 查询条件
     * 
     */
    public static function updateFlight($updata, $condition){
        if(empty($updata) || empty($condition)){
            return false;
        }
        //$condition['passenger_info_id']=163;
        //$updata['start_code']="VVV";
        $model = new FlightNumber();
        $rs = $model->updateAll($updata, $condition);
        if($rs===false){
            return false;
        }else{
            return true;
        }
    }


    /**
     * 查询已订阅航班信息
     * @param array $passengerId 乘客ID
     */
    public static function flightInfo($passengerId, $flightNo, $flightDate){
        if(!$passengerId || !$flightNo || !$flightDate){
            return false;
        }
        $model = self::find()->select(["*"])->andFilterWhere(["passenger_info_id"=>intval($passengerId)])
            ->andFilterWhere(["is_subscribe"=>1,"flight_number"=>$flightNo,"flight_date"=>$flightDate])//1已订阅
            ->asArray()->all();
        if(!$model)
            return false;
        else
            return $model;
    }


    /**
     * 查询乘客是否存在已订阅的航班信息920
     * @param array $passengerId 乘客ID
     */
    public static function checkFlight($passengerId,$flightNo,$flightDate){
        if(empty($passengerId)){
            return false;
        }
        $model = self::find()->select(["*"])->andFilterWhere(["passenger_info_id"=>intval($passengerId)])
            ->andFilterWhere(["is_subscribe"=>1])//1已订阅
            ->asArray()->all();
        if(!empty($model)){
            $flag=false;
            foreach ($model as &$val)
            {
                if(in_array($flightNo,$val) && in_array($flightDate,$val)){
                    $flag=true;
                }
            }
            if($flag){
                return true;
            }else{
                return $model;
            }
        }else{
            return false;
        }
    }




    /**
     * 查询乘客是否存在已订阅的航班信息1201
     * @param array $passengerId 乘客ID
     */
    public static function checkFlightSubscribe($passengerId,$flightNo,$flightDate,$orderId){
        if(empty($passengerId)){
            return false;
        }
        $model = self::find()->select(["*"])
            ->andWhere(["passenger_info_id"=>intval($passengerId)])
            ->andWhere(["is_subscribe"=>1])//1已订阅
            ->asArray()->all();
        if(!empty($model)){
            return $model;
        }else{
            return false;
        }
    }



    /**
     * 订阅（添加）航班号
     * @param array $upData 要更新的数据
     * @return bool
     */
    public static function add($upData){
        $model = new FlightNumber();
        $model->load($upData, '');
        if (!$model->validate()){
            //echo $model->getFirstError();
            \Yii::info($model->getFirstError(), "add flight DB 1");
            return false;
        }else{
            if($model->save()){
                return true;
            }else{
                \Yii::info($model->getErrors(), "add flight DB 2");
                return false;
            }
        }
    }

    /**
     * 获取一个用户最新一条得订阅
     * @param array $orderId
     * @return bool|array
     */
    public static function userFlightInfo($orderId){
        if(empty($orderId)) return array();

        $flightInfo = self::find()->select(["*"])->Where(["order_id"=>intval($orderId)])
            ->orderBy('create_time desc')
            ->asArray()->one();

        if (!$flightInfo){
            return array();
        }else{
            return $flightInfo;
        }
    }




    /**
     * 【判断插入订阅航班号】
     * 1.判断是否已经订阅，如果未订阅，进入2
     *   如果已经订阅，则终止。
     * 2.插入表数据，订阅API
     */
    public static function checkAddFlight($condition){
        $rs = self::checkFlight($condition['passenger_info_id'], $condition['flight_number'], $condition['flight_date']);
        if($rs===true){
            return true;//已经订阅过
        }
        //订阅航班API
        $flightres = new FlightApi();
        $data=[];
        $data['flightNo']           = $condition['flight_number'];
        $data['flightDate']         = $condition['flight_date'];
        $data['deptAirportCode']    = $condition['start_code'];
        $data['destAirportCode']    = $condition['end_code'];
        $data['mobile']             = $condition['phone'];
        $data['userId']             = $condition['passenger_info_id'];
        $result = $flightres->subscribeFlight($data);
        \Yii::info([$data, $result], "flight api return");
        if($result){
            //插入一条订阅信息
            $rs = self::add($condition);
            if($rs){
                return true;
            }else{
                return false;
            }
        }else{
            \Yii::info('api return false', "flight api return");
            return false;
        }
    }



}
