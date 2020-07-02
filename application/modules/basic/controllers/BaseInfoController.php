<?php
namespace application\modules\basic\controllers;

use common\models\BaseInfoCompanyPay;
use common\models\BaseInfoCompanyPermit;
use common\models\BaseInfoCompanyService;
use common\util\Common;
use common\util\Json;
use common\logic\BaseInfoLogic;
use common\models\BaseInfoCompany;
use application\controllers\BossBaseController;
/**
 * AirportTerminalManage controller
 */
class BaseInfoController extends BossBaseController
{
    /** 基础信息-网约车数据上报-公司基本信息
     * @param int $id
     * @param string $companyId 公司标识
     * @param string $companyName 公司名称
     * @param string $identifier 统一社会信用代码
     * @param string $businessScope 经营范围
     * @param string $address 通信地址
     * @param string $contactAddress 通信地址
     * @param string $economicType 经营业户经济类型
     * @param string $regCapital 注册资本
     * @param string $legalName 法人代表姓名
     * @param string $legalId 法人代表身份证号
     * @param string $legalPhone 法人代表电话
     * @param string $legalPhoto 法人代表身份证扫描件文件编号
     * @return  array
     * @author lrn
     */
    public  function  actionCompanyUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['id'] = $request->post('id');
        $requestData['company_id'] = $request->post('companyId');
        $requestData['company_name'] = $request->post('companyName');
        $requestData['identifier'] = $request->post('identifier');
        $requestData['address'] = $request->post('address');
        $requestData['business_scope'] = $request->post('businessScope');
        $requestData['contact_address'] = $request->post('contactAddress');
        $requestData['economic_type'] = $request->post('economicType');
        $requestData['reg_capital'] = $request->post('regCapital');
        $requestData['legal_name'] = $request->post('legalName');
        $requestData['legal_id'] = $request->post('legalId');
        $requestData['legal_phone'] = $request->post('legalPhone');
        $requestData['legal_photo'] = "";//$request->post('legalPhoto');
        $requestData['state'] = 1;

        \Yii::info($requestData, 'get_data');
        //参数验证
        $model = new BaseInfoCompany();
        $model->load($requestData, '');
        $model->validate();
        \Yii::info($model->getErrors(), 'getErrors');
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理 添加修改
        $company = BaseInfoLogic::companyUpdate($requestData);
        \Yii::info($company, 'post_data');
        return Json::message($company['errorinfo'],$company['status']);
    }


    /** 基础信息-网约车数据上报-支付信息
     * @param int $id
     * @param string $payName 银行或非银行支付机构名称
     * @param string $payId 非银行支付机构支付业务许可证编号
     * @param string $payType 支付业务类型
     * @param string $payScope 业务覆盖范围
     * @param string $prepareBank 备付金存管银行
     * @param int $countDate 结算周期（天）
     * @return  array
     * @author lrn
     */
    public  function  actionCompanyPayUpdate()
    {
        $request = $this->getRequest();
        $requestData['id'] = $request->post('id');
        $requestData['pay_name'] = $request->post('payName');
        $requestData['pay_id'] = $request->post('payId');
        $requestData['pay_type'] = $request->post('payType');
        $requestData['pay_scope'] = $request->post('payScope');
        $requestData['prepare_bank'] = $request->post('prepareBank');
        $requestData['count_date'] = $request->post('countDate');
        $requestData['state'] = 1;

        \Yii::info($requestData, 'get_data');
        //参数验证
        $model = new BaseInfoCompanyPay();
        $model->load($requestData, '');
        $model->validate();
        \Yii::info($model->getErrors(), 'getErrors');
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理 添加修改
        $company = BaseInfoLogic::companyPayUpdate($requestData);
        \Yii::info($company, 'post_data');
        return Json::message($company['errorinfo'],$company['status']);
    }

    /** 基础信息-网约车数据上报-服务机构信息
     * @param int $id
     * @param string $serviceName 服务机构名称
     * @param string $serviceNo 服务机构代码
     * @param string $detailAddress 服务机构地址
     * @param string $responsibleName 服务机构负责人姓名
     * @param string $responsiblePhone 负责人联系电话
     * @param string $managerName 服务机构管理人姓名
     * @param string $managerPhone 管理人联系电话
     * @param string $contactPhone 服务机构紧急联系电话
     * @param string $mailAddress 行政文书送达邮寄地址
     * @param string $createDate 服务机构设立日期
     * @return  array
     * @author lrn
     */
    public  function  actionCompanyServiceUpdate()
    {
        $request = $this->getRequest();
        $requestData['id'] = $request->post('id');
        $requestData['service_name'] = $request->post('serviceName');
        $requestData['service_no'] = $request->post('serviceNo');
        $requestData['detail_address'] = $request->post('detailAddress');
        $requestData['responsible_name'] = $request->post('responsibleName');
        $requestData['responsible_phone'] = $request->post('responsiblePhone');
        $requestData['manager_name'] = $request->post('managerName');
        $requestData['manager_phone'] = $request->post('managerPhone');
        $requestData['contact_phone'] = $request->post('contactPhone');
        $requestData['mail_address'] = $request->post('mailAddress');
        $requestData['create_date'] = $request->post('createDate');
        $requestData['state'] = 1;

        \Yii::info($requestData, 'get_data');
        //参数验证
        $model = new BaseInfoCompanyService();
        $model->load($requestData, '');
        $model->validate();
        \Yii::info($model->getErrors(), 'getErrors');
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理 添加修改
        $company = BaseInfoLogic::companyServiceUpdate($requestData);
        \Yii::info($company, 'post_data');
        return Json::message($company['errorinfo'],$company['status']);
    }


    /** 基础信息-网约车数据上报-经营许可证信息
     * @param int $id
     * @param string $certificate 网络预约出租汽车经营许可证号
     * @param string $operationArea 经营区域
     * @param string $ownerName 公司名称
     * @param string $organization 发证机构名称
     * @param string $startDate 有效期起
     * @param string $stopDate 有效期止
     * @param string $certifyDate 初次发证日期
     * @param string $state 证照状态
     * @return  array
     * @author lrn
     */
    public  function  actionCompanyPermitUpdate()
    {
        $request = $this->getRequest();
        $requestData['id'] = $request->post('id');
        $requestData['certificate'] = $request->post('certificate');
        $requestData['operation_area'] = $request->post('operationArea');
        $requestData['owner_name'] = $request->post('ownerName');
        $requestData['organization'] = $request->post('organization');
        $requestData['start_date'] = $request->post('startDate');
        $requestData['stop_date'] = $request->post('stopDate');
        $requestData['certify_date'] = $request->post('certifyDate');
        $requestData['state'] = $request->post('state');

        \Yii::info($requestData, 'get_data');
        //参数验证
        $model = new BaseInfoCompanyPermit();
        $model->load($requestData, '');
        $model->validate();
        \Yii::info($model->getErrors(), 'getErrors');
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理 添加修改
        $company = BaseInfoLogic::companyPermitUpdate($requestData);
        \Yii::info($company, 'post_data');
        return Json::message($company['errorinfo'],$company['status']);
    }


    /** 基础信息-网约车数据上报-经营许可证信息
     * @return  array
     * @author lrn
     */
    public  function  actionCompanyInfo()
    {
        //数据处理 添加修改
        $company = BaseInfoLogic::companyInfo();
        return Json::success(Common::key2lowerCamel($company));
    }


}
