<?php
/**
 * FenceApi.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\api;


use common\services\YesinCarHttpClient;

class MapApi
{

    public $server;

    public function __construct()
    {
        $this->server = \Yii::$app->params['api']['map'];
    }

    /**
     * getFenceInfo --获取围栏信息
     * @api-document
     * @author JerryZhang
     * @param $gids
     * @return array|mixed
     * @cache No
     */
    public function getFenceInfo($gids){

        $http = new YesinCarHttpClient(['serverURI'=>$this->server['serverName']]);

        $data['gid'] = $gids;
        $res = $http->post($this->server['fenceSearch'], $data);

        return $res;
    }

    /**
     * vehicleDispatch --调度服务
     * @api-document http://yapi.yesincar.com/project/47/interface/api/1376
     * @author JerryZhang
     * @param $data
     * @return array|mixed
     * @cache No
     */
    public function vehicleDispatch($data){
        $http = new YesinCarHttpClient(['serverURI'=>$this->server['serverName']]);
        $res = $http->post($this->server['vehicleDispatch'], $data);

        return $res;
    }

}