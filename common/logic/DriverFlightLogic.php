<?php
/**
 * DriverFlightLogic.php.php
 */

namespace common\logic;
use common\api\FlightApi;
use common\models\FlightNumber;
use common\models\Order;
class DriverFlightLogic
{

    /**
     * getFlightList --
     * @param $orderId
     * @return array
     * @cache No
     */
    public static function getFlightInfo($orderId)
    {
//        $passengerId = Order::findOne(['id' => $orderId])->passenger_info_id;
//        if(!$passengerId) return array();

        $flight = FlightNumber::userFlightInfo($orderId);
        \Yii::info($flight, 'flight');
        if(!$flight) return array();

        $flightRes = new FlightApi();
        $flightList = $flightRes->getFlightInfo($flight['flight_number'],$flight['flight_date']);

        if($flightList['status'] < 0||!$flightList['flightStatusList']) return array();

        $flightInfo['flightNo'] = $flightList['flightStatusList'][0]['flightNo'];

        $destFlightDate = isset($flightList['flightStatusList'][0]['destFlightDate'])?$flightList['flightStatusList'][0]['destFlightDate']:"";
        $eta = isset($flightList['flightStatusList'][0]['eta'])?$flightList['flightStatusList'][0]['eta']:"";
        $flightInfo['landingTime'] = $destFlightDate." ". $eta;

        $flightInfo['deptCity'] = isset($flightList['flightStatusList'][0]['deptCity'])?$flightList['flightStatusList'][0]['deptCity']:"";
        $flightInfo['destCity'] =  isset($flightList['flightStatusList'][0]['destCity'])?$flightList['flightStatusList'][0]['destCity']:"";

        $deptFlightDate = isset($flightList['flightStatusList'][0]['deptFlightDate'])?$flightList['flightStatusList'][0]['deptFlightDate']:"";
        $etd = isset($flightList['flightStatusList'][0]['etd'])?$flightList['flightStatusList'][0]['etd']:"";
        $flightInfo['deptFlightDate'] = $deptFlightDate." ".$etd;

        $destDate = isset($flightList['flightStatusList'][0]['destFlightDate'])?$flightList['flightStatusList'][0]['destFlightDate']:"";
        $destFlightDateEta = isset($flightList['flightStatusList'][0]['eta'])?$flightList['flightStatusList'][0]['eta']:"";
        $flightInfo['destFlightDate'] = $destDate." ". $destFlightDateEta;

        $deptAirportName = isset($flightList['flightStatusList'][0]['deptAirportName'])?$flightList['flightStatusList'][0]['deptAirportName']:"";
        $deptTerminal = isset($flightList['flightStatusList'][0]['deptTerminal'])?$flightList['flightStatusList'][0]['deptTerminal']:"";
        $flightInfo['deptTerminal'] = $deptAirportName. $deptTerminal;

        $destAirportName = isset($flightList['flightStatusList'][0]['destAirportName'])?$flightList['flightStatusList'][0]['destAirportName']:"";
        $destTerminal = isset($flightList['flightStatusList'][0]['destTerminal'])?$flightList['flightStatusList'][0]['destTerminal']:"";
        $flightInfo['destTerminal'] = $destAirportName. $destTerminal;

        $flightInfo['airlineName'] = isset($flightList['flightStatusList'][0]['airlineName'])?$flightList['flightStatusList'][0]['airlineName']:"";
        $flightInfo['flightStatus'] = isset($flightList['flightStatusList'][0]['flightStatus'])?$flightList['flightStatusList'][0]['flightStatus']:"";

        return $flightInfo;
    }

}