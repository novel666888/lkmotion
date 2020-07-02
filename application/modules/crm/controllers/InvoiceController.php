<?php
namespace application\modules\crm\controllers;

use common\logic\InvoiceLogic;
use common\util\Json;
use common\util\Common;
use common\models\InvoiceRecord;
use application\controllers\BossBaseController;
/**
 * 发票业务相关
 */
class InvoiceController extends BossBaseController
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }

    /**
     * 获取指定用户的发票列表
     * @return [type] [description]
     */
    public function actionList(){
        $request = $this->getRequest();
        $requestData = $request->post();
        //乘客ID，token
        $requestData['passengerId'] = isset($requestData['passengerId']) ? trim($requestData['passengerId']) : '';
        if(empty($requestData['passengerId'])){
            return Json::message("Parameter error");
        }

        $condition=[];
        $condition['passengerId'] = $requestData['passengerId'];
        $select=[
            'id',
            'passenger_info_id AS passengerId',
            'invoice_title',
            'taxpayer_id',
            'reg_address',
            'reg_phone',
            'deposit_bank',
            'bank_account',
            'receiver_name',
            'receiver_phone',
            'receiver_address',
            'create_time',
        ];
        $data = InvoiceLogic::getInvoiceList($condition, false, $select);
        if(!empty($data['list'])){
            $data['list'] = Common::key2lowerCamel($data['list']);
        }
        return Json::success($data);
    }






}
