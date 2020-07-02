<?php
namespace passenger\modules\service\controllers;

use common\util\Json;
use common\controllers\ClientBaseController;

use passenger\models\Ads;
use passenger\models\AdPosition;
use common\logic\AdLogic;
use common\util\Common;

class AdController extends ClientBaseController
{

    public function actionIndex()
    {
        //echo "hello world";
    }

    /**
     * 获取广告列表
     * @return [type] [description]
     */
    public function actionGet(){
        $request = $this->getRequest();
        $post = $request->post();

        if(empty($this->userInfo['id'])){
            //return Json::message("Identity error");
        }
        $post['position_id'] = isset($post['position_id']) ? trim($post['position_id']) : "";
        $post['city_code']   = isset($post['city_code']) ? trim($post['city_code']) : "";
        if(empty($post['position_id'])){
            return Json::message("Parameter loss");
        }
        $post['position_id'] = explode(",", $post['position_id']);
        if(empty($post['position_id'])){
            return Json::message("Parameter loss");
        }

        $rs = AdLogic::getPassengerAdList($post['position_id'], $post['city_code']);
        if($rs===false){
            return  Json::message("service error");
        }
        return Json::success(Common::key2lowerCamel($rs));
    }
    


}