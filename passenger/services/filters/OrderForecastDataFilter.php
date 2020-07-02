<?php
namespace passenger\services\filters;
use common\models\Order;
use common\services\CConstant;
use yii\base\Model;

class OrderForecastDataFilter extends Model
{
    public $withOrderId;
    public $cityCode;
    public $cityName;
    public $serviceTypeId;
    public $channelId;
    public $carLevelId;
    public $deviceCode;
    public $orderType;
    public $startLongitude;
    public $startLatitude;
    public $startAddress;
    public $endLongitude;
    public $endLatitude;
    public $endAddress;
    public $userLongitude;
    public $userLatitude;
    public $orderStartTime;
    public $source;
    public $meId;

    /**
     * @return array
     */

    public function rules()
    {
        return [
            [
                ['cityCode', 'cityName','serviceTypeId', 'channelId', 'carLevelId', 'deviceCode','startLongitude', 'startLatitude', 'startAddress','userLongitude', 'userLatitude', 'source'],
                'required','message'=>'{attribute}不能为空',
            ],
            [['cityCode', 'serviceTypeId', 'channelId', 'orderType',], 'integer', 'message'=>'{attribute}必须是整数',],
            [['cityName'], 'string', 'length'=>[1,10]],
            [
                ['serviceTypeId',],
                'in',
                'range'=>[
                    CConstant::SERVICE_TYPE_REAL_TIME,
                    CConstant::SERVICE_TYPE_RESERVE,
                    CConstant::SERVICE_AIRPORT_PICK_UP,
                    CConstant::SERVICE_AIRPORT_DROP_OFF,
                    CConstant::SERVICE_CHARTER_CAR_HALF_DAY,
                    CConstant::SERVICE_CHARTER_CAR_FULL_DAY
                ],
                'message'=>'{attribute}超出允许范围值',
            ],
            [['startLongitude','startLatitude','userLongitude','userLatitude'], 'number', 'message'=>'{attribute}必须是数字',],
            [['source'], 'match', 'pattern'=>'/(android)|(ios)/i', 'message'=>'{attribute}超出范围',],
            [['startAddress','endAddress'], 'trim',],
            [['startAddress'], 'string', 'length' => [1, 128],],
            [['endAddress'], 'string', 'length' => [0, 128]],
            [['withOrderId','endLongitude','endLatitude','orderStartTime'], 'safe'],
            [['deviceCode'], 'string', 'min'=>1,'max'=>64],
            [['meId'], 'string', 'min'=>0,'max'=>64],

        ];
    }

    public function attributeLabels()
    {
    }

}