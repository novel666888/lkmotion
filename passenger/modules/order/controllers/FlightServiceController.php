<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/10/19
 * Time: 15:44
 */
namespace passenger\modules\order\controllers;
use common\api\FlightApi;
use common\controllers\BaseController;
use common\logic\FlightLogic;
use common\models\AirportTerminalManage;
use common\util\Common;
use common\util\Json;
use yii\web\Request;

class FlightServiceController extends BaseController
{
    /**
     * @var  Request $_request
     */
    private $_request;

    public function init ()
    {
        $this->_request = \Yii::$app->getRequest();
        parent::init();
    }

    /**
     * 航班信息查询
     *
     * @return array
     */

    public function actionQueryFlight()
    {
        $flightNumber = $this->_request->post('flightNo','KN5987');
        $flightDate = $this->_request->post('flightDate','2018-10-20');
        $flightResult = FlightLogic::getFlightList($flightNumber,$flightDate);
        if(!$flightResult){
            return Json::error();
        }

        return Json::success(['list'=>$flightResult]);
    }

    /**
     * * 航站楼查询
     *
     * @return array
     */

    public function actionGetAirportTerminal()
    {
        $cityCode = $this->_request->post('cityCode');
        $terminalInfo = AirportTerminalManage::getCityAirportTerminal($cityCode);
        $newTerminalInfo = [];
        if(!empty($terminalInfo))
        {
            foreach ($terminalInfo as $key=>$value){
                //$value['terminalName'] = $value['terminal_name'];
                $locationArr = explode(',',$value['terminal_longitude_latitude']);
                $value['longitude'] = isset($locationArr[0])?$locationArr[0]:'';
                $value['latitude'] =isset($locationArr[1])?$locationArr[1]:'';
                unset($value['terminal_longitude_latitude']);
                $newTerminalInfo[] = $value;
            }
            $newTerminalInfo = Common::key2lowerCamel($newTerminalInfo);
        }


        return Json::success(['list'=>$newTerminalInfo]);
    }
}