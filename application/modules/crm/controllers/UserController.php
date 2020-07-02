<?php
namespace application\modules\crm\controllers;

use common\util\Json;
use common\util\Common;

use passenger\models\PassengerInfo;
use common\models\PassengerBlacklist;
use common\services\traits\PublicMethodTrait;
use common\logic\Passenger;
use yii\base\UserException;
use common\logic\blacklist\BlacklistDashboard;
use application\controllers\BossBaseController;
/**
 * CRM客户关系管理控制器
 */
class UserController extends BossBaseController
{
    use PublicMethodTrait;
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }
    
    /**
     * 通过手机号精准查询某个用户，返回基本信息
     * @return [type] [description]
     */
    public function actionInfo()
    {
		$request = $this->getRequest();
        $requestData = $request->post();
        $requestData['phone'] = isset($requestData['phone']) ? trim($requestData['phone']) : '';
        if(empty($requestData['phone'])){
            return Json::success();
        }
        else{
            try{
                $rs = Common::phoneEncrypt([$requestData['phone']]);
                if(!empty($rs)){
                    $phoneEncrypt = $rs;
                }else{
                    return Json::message("Phone number decryption error");
                }
            }catch (UserException $exception){
                return $this->renderErrorJson($exception);
            }catch(\yii\httpclient\Exception $exception){
                return $exception->getMessage();
            }
        }
        $condition=[];
        $condition['phone'] = $phoneEncrypt;
    	$rs = PassengerInfo::getUserDetailInfo($condition);
    	if(!empty($rs)){
            $_return['list']          = [$rs];
    	}else{
            $_return['list']          = [];
        }
    	return Json::success(Common::key2lowerCamel($_return));
    }

    /**
     * 查询用户详细信息
     * @return [type] [description]
     */
    public function actionInfoDetail()
    {
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['passengerId'] = isset($requestData['passengerId']) ? trim($requestData['passengerId']) : '';
        if(empty($requestData['passengerId'])){
            return Json::message("Parameter error");
        }
        $condition=[];
        $condition['id'] = $requestData['passengerId'];
        $select = [
            'i.id',
            'i.id as passengerId',
            'i.phone',
            'i.birthday',
            'i.passenger_name',
            'i.register_time',
            'i.gender',
            'i.head_img',
            'i.passenger_type',
            'i.user_level',
            'i.last_login_time',
            'i.last_login_method',
            'i.last_order_time',
        ];
        $rs = PassengerInfo::getUserDetailInfo($condition, $select);
        if(!empty($rs) && isset($rs['id'])){
            $models = new Passenger($rs['id']);
            $rs['MonthDistance'] = $models->getMonthDistance();//总里程
            $rs['TotalDistance'] = $models->getTotalDistance();//本月总里程
            $rs['TripInvoiceAmount'] = $models->getTripInvoiceAmount();//行程可开发票金额
            $rs['TotalRechargeAmount'] = $models->getTotalRechargeAmount();//总充值金额
            $rs['TotalRefundAmount'] = $models->getTotalRefundAmount();//总退款金额
            $rs['TotalOrderPaymentAmount'] = $models->getTotalOrderPaymentAmount();//总订单支付金额
            $rs['MonthOrderPaymentAmount'] = $models->getMonthOrderPaymentAmount();//本月订单支付金额
            return Json::success(Common::key2lowerCamel($rs));
        }else{
            return Json::success();
        }
    }

    /**
     * 输出乘客黑名单列表 - 不带传参返回列表
     * @return [type] [description]
     */
    public function actionBlacklist(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['phone'] = isset($requestData['phone']) ? trim($requestData['phone']) : '';
        $requestData['category'] = isset($requestData['category']) ? trim($requestData['category']) : 1;//默认临时
        //查询手机号
        $phoneEncrypt = "";
        if(!empty($requestData['phone'])){
            try{
                $rs = Common::phoneEncrypt([$requestData['phone']]);
                if(!empty($rs)){
                    $phoneEncrypt = $rs;
                }
            }catch (UserException $exception){
                return $this->renderErrorJson($exception);
            }catch(\yii\httpclient\Exception $exception){
                return $exception->getMessage();
            }
        }
        $condition=[];
        $condition['phone']     = $phoneEncrypt;
        $condition['category']  = $requestData['category'];
        $field=["id", "phone", "reason", "is_release AS isRelease", "release_time AS releaseTime"];
        $data = PassengerBlacklist::getBlacklist($condition, $field);
        return Json::success($data);
        /**
        if(!empty($data['list'])){
            return Json::success($data);
        }else{
            return Json::success();
        }
        */
    }

    /**
     * 解除乘客黑名单限制
     * @return [type] [description]
     */
    public function actionReleaseBlacklist(){
        $request = $this->getRequest();
        $requestData = $request->post();
        $requestData['blacklistId'] = isset($requestData['blacklistId']) ? trim($requestData['blacklistId']) : '';
        $requestData['isRelease'] = isset($requestData['isRelease']) ? trim($requestData['isRelease']) : 1;
        if(empty($requestData['blacklistId']) || !is_numeric($requestData['blacklistId'])){
            return Json::message("Parameter error");
        }
        if($requestData['isRelease']==0){
            return Json::message("isRelease error");
        }
        $rs = PassengerBlacklist::releaseBlacklist($requestData);
        if($rs['code']==0){
            return Json::success();
        }else{
            return Json::message($rs['message']);
        }
    }   
    
    
    /**
     * 新增会员等级
     */
    public function actionAddLevel(){
        exit("Please wait...");
    }


}
