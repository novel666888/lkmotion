<?php
/**
 * FlightLogic.php
 */

namespace common\logic;

use common\api\FlightApi;
use common\models\FlightNumber;

class FlightLogic
{
    /**
     * getFlightList Passenger接机--
     * @param $flightNo
     * @param $flightDate
     * @return array|bool
     * @cache No
     */
    public static function getFlightList($flightNo,$flightDate)
    {
        if(!$flightNo||!$flightDate) return false;
        $flightRes = new FlightApi();
        $flightList = $flightRes->getFlightInfo($flightNo,$flightDate);

        if($flightList['status'] < 0) return false;
        if($flightList['flightStatusList']){
            $flightData = [];
            foreach ($flightList['flightStatusList'] as $key => $value){
                $data['flightStatus'] = $value['flightStatus'];
                $data['deptCityCode'] = $value['deptCityCode'];
                $data['destCityCode'] = $value['destCityCode'];
                $data['flightNo'] = $value['flightNo'];
                $data['std'] = isset($value['std'])?$value['std']:"";
                $data['sta'] = isset($value['sta'])?$value['sta']:"";
                $data['etd'] = isset($value['etd'])?$value['etd']:"";
                $data['eta'] = isset($value['eta'])?$value['eta']:"";
                $data['atd'] = isset($value['atd'])?$value['atd']:"";
                $data['ata'] = isset($value['ata'])?$value['ata']:"";
                $data['deptFlightDate'] = isset($value['deptFlightDate'])?$value['deptFlightDate']:"";
                $data['destFlightDate'] = isset($value['destFlightDate'])?$value['destFlightDate']:"";
                $data['deptCity'] = isset($value['deptCity'])?$value['deptCity']:"";
                $data['destCity'] = isset($value['destCity'])?$value['destCity']:"";
                $data['deptAirportName'] = isset($value['deptAirportName'])?$value['deptAirportName']:"";
                $data['destAirportName'] = isset($value['destAirportName'])?$value['destAirportName']:"";
                $data['airlineName'] = isset($value['airlineName'])?$value['airlineName']:"";
                $data['airlineCode'] = isset($value['airlineCode'])?$value['airlineCode']:"";
                $data['planeType'] = isset($value['planeType'])?$value['planeType']:"";
                $data['deptTerminal'] = isset($value['deptTerminal'])?$value['deptTerminal']:"";
                $data['destTerminal'] = isset($value['destTerminal'])?$value['destTerminal']:"";
                $flightData[]=$data;
            }
            if(!$flightData) return false;
            return $flightData;
        }

    }

    /**
     * getFlightList --
     * @param $flightNo     //航班号
     * @param $flightDate   //日期
     *@param $deptCityCode  //开始城市三字码
     * @param $destCityCode //到大城市三字码
     * @param $mobile       //用户手机号
     * @param $passengerId  //乘客id
     * @param $orderId  //订单id
     * @return array|bool
     * @cache No
     */
    public static function subscribeFlight($flightNo,$flightDate,$deptCityCode,$destCityCode,$mobile,$passengerId,$orderId)
    {
        if(!$flightNo || !$flightDate ||!$deptCityCode || !$destCityCode || !$mobile ||!$passengerId || !$orderId) return false;
        $flightRes = new FlightApi();
        $flightData=FlightNumber::checkFlightSubscribe($passengerId,$flightNo,$flightDate,$orderId);

        //if($flightData == 1) return true;
        if($flightData){
            foreach ($flightData as $key => $value){
                $data['flightNo'] = $value['flight_number'];
                $data['flightDate'] = $value['flight_date'];
                $data['deptAirportCode'] = $value['start_code'];
                $data['destAirportCode'] = $value['end_code'];
                $data['userId'] = $passengerId;
                $flightRes->cancelSubscribeFlight($data);
                $update['is_subscribe'] = 0;
                $condition['passenger_info_id'] = $passengerId;
                if(!FlightNumber::updateFlight($update, $condition)) return false;
            }
        }

        $data['flightNo'] = $flightNo;
        $data['flightDate'] = $flightDate;
        $data['deptAirportCode'] = $deptCityCode;
        $data['destAirportCode'] = $destCityCode;
        $data['mobile'] = $mobile;
        $data['userId'] = $passengerId;
        if(!$flightRes->subscribeFlight($data)) return false;
        $upData['flight_number'] = $flightNo;
        $upData['flight_date'] = $flightDate;
        $upData['passenger_info_id'] = $passengerId;
        $upData['start_code'] = $deptCityCode;
        $upData['end_code'] = $destCityCode;
        $upData['is_subscribe'] = 1;
        $upData['order_id'] = $orderId;
        if(FlightNumber:: add($upData))
            return true;
        else
            return false;
    }


}