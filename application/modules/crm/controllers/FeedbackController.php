<?php
namespace application\modules\crm\controllers;

use common\util\Json;

use common\models\Feedback;
use common\util\Common;

use yii\db\Query;
use PHPExcel;
use PHPExcel_IOFactory;
use yii\db\Expression;
use SimpleExcel\SimpleExcel;

use yii\base\UserException;
use application\controllers\BossBaseController;
/**
 * 反馈信息相关
 */
class FeedbackController extends BossBaseController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }


    /**
     * 处理反馈信息
     */
    public function actionChk(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['id']              = isset($requestData['id']) ? trim($requestData['id']) : '';
        $requestData['status']          = isset($requestData['status']) ? trim($requestData['status']) : '';
        $requestData['solution']        = isset($requestData['solution']) ? trim($requestData['solution']) : '';
        $requestData['operator_id']     = isset($this->userInfo['id']) ? $this->userInfo['id'] : 0;
        if(empty($requestData['id']) || empty($requestData['status']) || empty($requestData['solution'])){
            return Json::message('ID、处理状态和处理文案不可以为空');
        }
        if(!in_array($requestData['status'], [1,2,3])){
            return Json::message('status范围1-3');
        }
        $data = Feedback::find()->where(['id'=>$requestData['id']])->one();
        if(!empty($data)){
            $data->status       =   $requestData['status'];
            $data->solution    .=   $requestData['solution'].'；';
            $data->operator_id  =   $requestData['operator_id'];
            $data->update_time  =   date("Y-m-d H:i:s", time());
            $data->validate();
            if($data->getErrors()){
                \Yii::info($data->getErrors(), 'ChkFeedback');
                return Json::message('参数为空或不支持的数据类型');
            }
            if($data->save()){
                switch ($requestData['status']){
                    case 2;
                        $send_phone = Common::decryptCipherText($data->phone, true);
                        $msgdata = [$data->create_time,'个人中心-问题反馈'];
                        \Yii::info([$send_phone,$msgdata], 'sendSms_2_HX_0031');
                        Common::sendMessageNew($send_phone, "HX_0031", $msgdata);
                        break;
                    case 3;
                        $send_phone = Common::decryptCipherText($data->phone, true);
                        $msgdata = [$data->create_time,'个人中心-问题反馈'];
                        \Yii::info([$send_phone,$msgdata], 'sendSms_3_HX_0030');
                        Common::sendMessageNew($send_phone, "HX_0030", $msgdata);
                        break;
                }
                return Json::message('操作成功', 0);
            }else{
                return Json::message('操作失败');
            }
        }else{
            return Json::message('找不到反馈信息');
        }
    }

    /**
     * 获取乘客/司机反馈信息列表
     * @return [type] [description]
     */
    public function actionList(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['queryInfo']['beginTime']      = isset($requestData['queryInfo']['beginTime']) ? trim($requestData['queryInfo']['beginTime']) : '';
        $requestData['queryInfo']['status']         = isset($requestData['queryInfo']['status']) ? trim($requestData['queryInfo']['status']) : '';
        $requestData['queryInfo']['endTime']        = isset($requestData['queryInfo']['endTime']) ? trim($requestData['queryInfo']['endTime']) : '';
        $requestData['queryInfo']['terminal']       = isset($requestData['queryInfo']['terminal']) ? trim($requestData['queryInfo']['terminal']) : '';
        $requestData['queryInfo']['phone']          = isset($requestData['queryInfo']['phone']) ? trim($requestData['queryInfo']['phone']) : '';

        if(!empty($requestData['queryInfo']['phone'])){
            try{
                $rs = Common::phoneEncrypt([$requestData['queryInfo']['phone']]);
                if(!empty($rs)){
                    $requestData['queryInfo']['phone'] = $rs;
                }
            }catch (UserException $exception){
                return $this->renderErrorJson($exception);
            }catch(\yii\httpclient\Exception $exception){
                return $exception->getMessage();
            }
        }
        if(!empty($requestData['queryInfo']['beginTime'])){
            $requestData['queryInfo']['beginTime'] = date("Y-m-d", strtotime($requestData['queryInfo']['beginTime']));
            $requestData['queryInfo']['beginTime'].= " 00:00:00";
        }
        if(!empty($requestData['queryInfo']['endTime'])){
            $requestData['queryInfo']['endTime'] = date("Y-m-d", strtotime($requestData['queryInfo']['endTime']));
            $requestData['queryInfo']['endTime'].= " 23:59:59";
        }
        $condition=[];
        $condition=$requestData;
        $field=['*'];
        $data = Feedback::list($condition, $field);

        return Json::success($data);
        /**
        if(!empty($data['list'])){
            return Json::success($data);
        }else{
            return Json::success();
        }*/
    }

    /**
     * 导出乘客反馈信息=>excel
     * @return [type] [description]
     */
    public function actionExport(){

        $request = $this->getRequest();
        $requestData = $request->post();
        \Yii::info($requestData, 'getpPostData');
        $requestData['queryInfo']['beginTime']      = isset($requestData['queryInfo']['beginTime']) ? trim($requestData['queryInfo']['beginTime']) : '';
        $requestData['queryInfo']['endTime']        = isset($requestData['queryInfo']['endTime']) ? trim($requestData['queryInfo']['endTime']) : '';
        $requestData['queryInfo']['terminal']       = isset($requestData['queryInfo']['terminal']) ? trim($requestData['queryInfo']['terminal']) : '';
        $requestData['queryInfo']['phone']          = isset($requestData['queryInfo']['phone']) ? trim($requestData['queryInfo']['phone']) : '';
        \Yii::info($requestData, 'getData');
        if(!empty($requestData['queryInfo']['phone'])){
            try{
                $rs = Common::phoneEncrypt([$requestData['queryInfo']['phone']]);
                if(!empty($rs)){
                    $requestData['queryInfo']['phone'] = $rs;
                }
            }catch (UserException $exception){
                return $this->renderErrorJson($exception);
            }catch(\yii\httpclient\Exception $exception){
                return $exception->getMessage();
            }
        }

        //$condition=$requestData;
        $data = Feedback::list($requestData, ['*'], false);
        Feedback::outPutExcel($data['list']);

        return Json::message('导出乘客反馈信息成功',0);

    }




}
