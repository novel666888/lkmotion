<?php
/**
 *
 */
namespace common\logic\order;

use common\models\Order;
use common\models\OrderRulePrice;
use common\models\OrderUseCoupon;
use common\services\CConstant;
use yii\base\Component;

/**
 * Class CommonOrder
 * @package common\logic\order
 * @property $cityCode
 * @property $forecastCost
 */

class CommonOrder extends Component
{
    public $id;
    private $_orderActiveRecord = null;
    private $_orderRulePriceForecastActiveRecord = null;

    /**
     * CommonOrder constructor.
     * @param $id
     * @param array $config
     */

    public function __construct($id,array $config = [])
    {
        $this->id = $id;
        $this->_orderActiveRecord = Order::findOne($id);
        $orderRulePriceTable = OrderRulePrice::findOne([
            'order_id'=>$id,
            'category'=>CConstant::TYPE_FORECAST_ORDER
        ]);
        if($orderRulePriceTable){
            $this->_orderRulePriceForecastActiveRecord = $orderRulePriceTable;
        }

        parent::__construct($config);
    }

    /**
     * @return Order|null
     */

    public function getOrderRecord()
    {
        return $this->_orderActiveRecord;
    }

    /**
     * 订单预估价格
     *
     * @return string
     */

    public function getForecastCost()
    {
        $cost = OrderUseCoupon::fetchFieldBy(['order_id'=>$this->id],'after_use_coupon_moeny');
        if(NULL === $cost){
            if($this->_orderRulePriceForecastActiveRecord){
                return (string)$this->_orderRulePriceForecastActiveRecord->total_price;
            }
            return null;
        }
        return (string)$cost;
    }

    /**
     * 订单城市码
     *
     * @return string
     */

    public function getCityCode()
    {
        if($this->_orderRulePriceForecastActiveRecord){
            return $this->_orderRulePriceForecastActiveRecord->city_code;
        }
        return null;
    }

    /**
     * 下订单所用设备码
     *
     * @return string
     */

    public function getDeviceCode()
    {
        return $this->_orderActiveRecord->device_code;
    }

    /**
     * 设备类型
     * @return int
     */
    public function getDeviceSource()
    {
        return $this->_orderActiveRecord->source;
    }



}