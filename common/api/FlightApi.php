<?php
/**
 * FlightApi.php
 * @author: lrn
 * 下午6:09
 */

namespace common\api;

use common\util\Common;
use common\util\Json;
use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;

class FlightApi
{

    public $server;

    public function __construct()
    {
        $this->server = \Yii::$app->params['api']['flight'];
    }

    /**
     * @api-document FlightToken
     * @author lrn
     * @return array|mixed
     * @cache No
     */
    public function getFlightToken(){
        $tokenPath = ArrayHelper::getValue(\Yii::$app->params,'api.flight.method.flightToken');
        return $this->httpClientFlight($tokenPath,1);
    }

    /**
     * @api-document FlightInfo
     * @author lrn
     * @param $flightNo
     *@param $flightDate
     * @return array|mixed
     * @cache No
     */
    public function getFlightInfo($flightNo,$flightDate){
        if(!$flightNo || !$flightDate) return false;

        $data['flightNo'] = $flightNo;
        $data['flightDate'] = $flightDate;
        $data['token'] = $this->getFlightToken();

        $flightInfonPath = ArrayHelper::getValue(\Yii::$app->params,'api.flight.method.getFlightInfo');
        return $this->httpClientFlight($flightInfonPath,2,$data);
    }


    /**
     * @api-document subscribeFlight
     * @author lrn
     * @param array $data
     * @return array|mixed
     * @cache No
     */
    public function subscribeFlight($data)
    {
        $data['token'] = $this->getFlightToken();
        $subscribeFlightPath = ArrayHelper::getValue(\Yii::$app->params,'api.flight.method.subscribeFlight');
        $subscribeFlight = $this->httpClientFlight($subscribeFlightPath,2,$data);
        if($subscribeFlight['result']==0)
            return true;
        else
            return false;
    }

    /**
     * @api-document cancelSubscribeFlight
     * @author lrn
     * @param array $data
     * @return array|mixed
     * @cache No
     */
    public function cancelSubscribeFlight($data)
    {
        $canceltPath = ArrayHelper::getValue(\Yii::$app->params,'api.flight.method.cancelSubscribeFlight');
        
        $canceltFlight = $this->httpClientFlight($canceltPath,2,$data);

        if($canceltFlight['result']==0)
            return true;
        else
            return false;
    }



    /**
     * @param $methodPath
     * @param $reponseType
     * @param $data
     * @return array|mixed
     * @cache No
     */
    public function httpClientFlight($methodPath,$reponseType,$data = array()){
        $flightServer = ArrayHelper::getValue(\Yii::$app->params,'api.flight.serverName');

        $httpClient = new YesinCarHttpClient(['serverURI'=>$flightServer]);

        $data['appid']=ArrayHelper::getValue(\Yii::$app->params,'umetripAppId');// \Yii::$app->params['umetripAppId'];

        return $httpClient->get($methodPath, $data,$reponseType);
    }


}