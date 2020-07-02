<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/11/17
 * Time: 17:17
 */

namespace common\logic\order;


use common\logic\ChargeRuleTrait;
use common\services\CConstant;
use yii\base\BaseObject;
use yii\base\UserException;

class CharterCarInfo extends BaseObject
{
    use ChargeRuleTrait;
    private $_cityCode = null;
    private $_serviceType = null;

    /**
     * CharterCarInfo constructor.
     * @param $cityCode
     * @param $serviceType
     * @throws UserException
     */

    public function __construct($cityCode,$serviceType)
    {
        $this->_cityCode = $cityCode;
        if(!in_array($serviceType,[CConstant::SERVICE_CHARTER_CAR_HALF_DAY,CConstant::SERVICE_CHARTER_CAR_FULL_DAY])){
            throw new UserException('service type error!',1000);
        }
        $this->_serviceType = $serviceType;
    }

    /**
     * @return string
     */

    public function getCharterCarTimePhase()
    {
        try{
            $car_list = $this->getChargeRuleCarLevel($this->_cityCode, $this->_serviceType);
            $data = [
                CConstant::SERVICE_CHARTER_CAR_HALF_DAY => '%d小时(含%d公里)',
                CConstant::SERVICE_CHARTER_CAR_FULL_DAY => '%d小时(含%d公里)'
            ];
            if (empty($car_list)) {
                $rule_list = [
                    ['service_type_id' => CConstant::SERVICE_CHARTER_CAR_HALF_DAY, 'base_minutes' => 240, 'base_kilo' => 0],
                    ['service_type_id' => CConstant::SERVICE_CHARTER_CAR_FULL_DAY, 'base_minutes' => 480, 'base_kilo' => 0],
                ];
            } else {
                $rule_list = $this->getChargeRuleList($this->_cityCode,$this->_serviceType, $car_list[0]);
            }
            $rule_list = array_column($rule_list, null, 'service_type_id');
            $finalData = $data[$this->_serviceType];
            $hour = $kilo = 0;
            if (isset($rule_list[$this->_serviceType])) {
                $hour = (int)($rule_list[$this->_serviceType]['base_minutes'] / 60);
                $kilo = (int)$rule_list[$this->_serviceType]['base_kilo'];
            } else {
                if ($this->_serviceType ==  CConstant::SERVICE_CHARTER_CAR_HALF_DAY) {
                    $hour = 4;
                    $kilo = 0;
                } elseif ($this->_serviceType == CConstant::SERVICE_CHARTER_CAR_FULL_DAY) {
                    $hour = 8;
                    $kilo = 0;
                }
            }
            return vsprintf($finalData, [$hour, $kilo]);
        }catch (UserException $exception){
            \Yii::info($exception->getMessage());
            return '';
        }
    }



}