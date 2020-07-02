<?php
/**
 * ServiceLogic.php
 */

namespace common\logic;


use common\models\AirportTerminalManage;
use common\models\City;
use common\models\Service;
use common\models\ServiceType;
use common\services\CConstant;


class ServiceLogic
{

    /**
 * Service status --
 * @param string $cityCode
 * @param int $serviceTypeId
 * @return array | bool
 * @cache No
 */
    public static function serviceStatus($cityCode, $serviceTypeId)
    {
        $cityStatus = City::checkCityStatus($cityCode);
        if($cityStatus == 0) {
            return false;
        }
        $serviceStatus = Service::checkServiceStatus ($cityCode,$serviceTypeId);

        return (boolean)$serviceStatus;
    }

    /**
     * Service serviceOrderNumber --
     * @param string $cityCode
     * @param int $serviceTypeId
     * @return int
     * @cache No
     */
    public static function serviceOrderNumber($cityCode, $serviceTypeId)
    {
        $serviceOrderNumber = Service::serviceOrderNumber ($cityCode,$serviceTypeId);
        if ($serviceOrderNumber)
            return $serviceOrderNumber;
        else
            return false;
    }


    /**
     * Service serviceTypeStatus --
     * @param string $cityCode
     * @param bool $check_city
     * @return array
     * @cache No
     */
    public static function serviceTypeStatus($cityCode,$check_city = true)
    {
        if(!$cityCode) return array();
        if($check_city && !City::checkCityStatus($cityCode)) return array();
        return Service::getServiceTypeStatus ($cityCode);
    }

    /**
     * @param $cityCode
     * @return int
     */

    public static function getOrderRiskTopByCityCode($cityCode)
    {
        $riskTop = City::fetchFieldBy(['city_code'=>$cityCode],'order_risk_top');
        return (int)$riskTop;
    }


    /**
     * @param $cityCode
     * @return array
     */

    public static function getServiceType($cityCode)
    {
        $serviceTypeId = Service::find()->select("service_type_id")
            ->where(["city_code" => $cityCode])
            ->andWhere(["service_status" => Service::SERVICE_STATUS_YES])
            ->asArray()->all();
        if(!$serviceTypeId) return array();
        $serviceTypeArr = array();
        foreach ($serviceTypeId as $key =>$value){
            $serviceType = ServiceType::find()->select("id,service_type_name")
                ->where(["service_type_status" => ServiceType::SERVICE_TYPE_STATUS_YES])
                ->andWhere(["id" => $value['service_type_id']])
                ->asArray()->one();
            if($serviceType){
                $serviceTypeArr[]=$serviceType;
            }
        }
        return $serviceTypeArr;
    }


    /**
     * 返回接机服务下开启的城市,该城市并存在航站楼
     * @return array
     */

    public static function getAirportTerminalServiceCity()
    {
        $cityCode = Service::find()->select("city_code")
            ->where(["service_type_id" => CConstant::SERVICE_AIRPORT_DROP_OFF])
            ->andWhere(["service_status" => Service::SERVICE_STATUS_YES])
            ->asArray()->all();
        if(!$cityCode) return array();

        $cityData = array();
        foreach ($cityCode as $key=>$value){
            $airportTerminal = AirportTerminalManage::find()->select("airport_name")
                ->where(["city_code" => $value['city_code']])
                ->andWhere(['airport_terminal_status'=>AirportTerminalManage::AIRPORT_TERMINAL_YES])
                ->asArray()->all();
            if($airportTerminal){
                $cityData[] = $value['city_code'];
            }
        }
        return $cityData;
    }

}