<?php
namespace passenger\modules\user\controllers;

use yii;
use common\util\Json;
use common\controllers\ClientBaseController;
use common\util\Common;

use common\logic\MessageLogic;
//use common\util\Cache;

//use common\models\UserCoupon;

class MessageController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }

    /**
     * 判断是否有未读消息
     */
    public function actionUnreadNum(){
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $rs = MessageLogic::checkUnReadMessage(1, $this->userInfo['id']);
        \Yii::info([$this->userInfo['id'],$rs], "UnreadNum");
        if($rs){
            return Json::success(['Unreads'=>1]);
        }else{
            return Json::success(['Unreads'=>0]);
        }
    }

    /**
     * 更新消息为已读状态
     */
    public function actionUpdateRead(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['type'] = isset($requestData['type']) ? trim($requestData['type']) : 1;
        if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $rs = MessageLogic::updateMessageRead(1, $this->userInfo['id']);
        \Yii::info([$this->userInfo['id'], $rs], "UpdateRead");
        if($rs){
            return Json::success();
        }else{
            return Json::message('update error');
        }
    }

    /**
     * 获取订单消息列表
     * @return [type] [description]
     */
    public function actionGetOrderMessageList(){
    	if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $data = MessageLogic::getMessageList(1, $this->userInfo['id'], 1);
        if(isset($data['list']) && !empty($data['list'])){
        	$data['list'] = Common::key2lowerCamel($data['list']);
        	return Json::success($data);
        }else{
        	return Json::success();
        }
    }

    /**
     * 获取活动消息列表
     * @return [type] [description]
     */
    public function actionGetActiveMessageList(){
    	if(empty($this->userInfo['id'])){
            return Json::message("Identity error");
        }
        $data = MessageLogic::getMessageList(1, $this->userInfo['id'], 2);
        if(isset($data['list']) && !empty($data['list'])){
        	$data['list'] = Common::key2lowerCamel($data['list']);
        	return Json::success($data);
        }else{
        	return Json::success();
        }
    }

}