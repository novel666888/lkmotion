<?php
namespace passenger\modules\user\controllers;

use yii;
use common\util\Json;
use common\controllers\ClientBaseController;
//use common\util\Cache;

use common\models\Feedback;

class FeedbackController extends ClientBaseController
{

    public function actionIndex()
    {
        echo "hello world";
    }


    public function actionType(){
        $passengerFeedback = \Yii::$app->params['passengerFeedback'];
        return Json::success($passengerFeedback);
    }

    /**
     * 乘客端提交反馈问题
     * @return [type] [description]
     */
    public function actionAdd(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['large_class'] = isset($requestData['largeClass']) ? trim($requestData['largeClass']) : '';
        $requestData['category'] = isset($requestData['category']) ? trim($requestData['category']) : '';
        $requestData['content'] = isset($requestData['content']) ? trim($requestData['content']) : '';
        $requestData['terminal'] = '1';//乘客端
        if(empty($requestData['category']) || empty($requestData['large_class'])){
            return Json::message("参数错误");
        }
        if(empty($this->userInfo['id'])){
            return Json::message("身份错误");
        }else{
            $requestData['user_id'] = $this->userInfo['id'];
        }
        \Yii::info($requestData, "feedback add data");
        $rs = Feedback::add($requestData);
        if($rs['code']==0){
            return Json::success();
        }else{
            return Json::message($rs['message']);
        }
    }



}