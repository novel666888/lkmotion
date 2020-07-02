<?php
namespace application\modules\basic\controllers;


use common\logic\ServiceLogic;
use common\logic\ValuationLogic;
use common\util\Common;
use common\util\Json;
use common\models\ServiceType;
use common\models\Service;
use application\controllers\BossBaseController;

/**
 * Service controller
 */
class ServiceController extends BossBaseController
{
    /** 基础信息-服务类型列表api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionServiceTypeList()
    {
        //数据处理(查询,分页)
        $servicetypedata = ServiceType::getServiceTypeList();
        return Json::success(Common::key2lowerCamel($servicetypedata));
    }


    /** 基础信息-添加服务类型api
     * @param string  $serviceTypeName 服务类型名称
     * @param int $serviceTypeStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionServiceTypeAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['service_type_name'] = $request->post('serviceTypeName');
        $requestData['service_type_status'] = $request->post('serviceTypeStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
       $model = new ServiceType();
       $model->load($requestData, '');
       $model->validate();
       if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');
       if(!ServiceType::getServiceTypeCheck(trim($requestData['service_type_name']),'service_type_name',null))
            return Json::message('服务类型名称已经存在');

        //数据处理（添加,加入redis缓存）
        if (ServiceType::add($requestData))
            return Json::message('添加服务类型成功', 0);
        else
            return Json::message('添加服务类型失败');
    }


    /** 基础信息-服务类型详情api
     * @param int $serviceTypeId 当前服务类型id
     * @return  array
     * @author lrn
     */
    public  function  actionServiceTypeInfo()
    {
        // 参数接收
        $request = $this->getRequest();
        $serviceTypeId = intval($request->post('serviceTypeId'));

        //参数验证
        if (!trim($serviceTypeId)) return Json::message('参数不可为空');

        //数据处理（查询 redis缓存操作）
        $serviceTypeCache = ServiceType::getServiceTypeInfo($serviceTypeId);
        return Json::success(Common::key2lowerCamel($serviceTypeCache[$serviceTypeId]));
    }


    /** 基础信息-修改服务类型api
     * @param int $serviceTypeId 当前服务类型id
     * @param string  $serviceTypeName 服务类型名称
     * @param int $serviceTypeStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionServiceTypeUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $serviceTypeId = $request->post('serviceTypeId');
        $requestData['service_type_name'] = $request->post('serviceTypeName');
        $requestData['service_type_status'] = $request->post('serviceTypeStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new Servicetype();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        if(!Servicetype::getServicetypeCheck(trim($requestData['service_type_name']),'service_type_name',$serviceTypeId))
            return Json::message('渠道名称已经存在');

        //数据处理（修改）
        if (Servicetype::ServicetypeUpdate($requestData,$serviceTypeId))
            return Json::message('修改服务类型成功', 0);
        else
            return Json::message('修改服务类型失败');
    }


    /** 基础信息-服务类型状态开启暂停api
     * @param int $serviceTypeId 当前服务类型id
     * @param int $serviceTypeStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionServiceTypeStatusUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $serviceTypeId = trim($request->post('serviceTypeId'));
        $requestData['service_type_status'] = trim($request->post('serviceTypeStatus'));
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!$serviceTypeId) return Json::message('参数为空或不支持的数据类型');

        //数据处理（修改）
        if (Servicetype::ServicetypeUpdate($requestData,$serviceTypeId))
            return Json::message('服务类型状态开启暂停成功', 0);
        else
            return Json::message('服务类型状态开启暂停失败');
    }


    /** 基础信息-服务列表api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @param  int  $cityCode 城市编码
     * @param  int  $serviceTypeId  服务类型id
     * @param  int  $serviceStatus  是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public function actionServiceList()
    {
        // 参数接收
        $request = $this->getRequest();
        $requestData['city_code'] = trim($request->post('cityCode'));
        $requestData['service_type_id'] = trim($request->post('serviceTypeId'));
        $requestData['service_status'] = trim($request->post('serviceStatus'));

        //数据处理(查询,分页)
        $serviceTypeData = Service::getServiceList($requestData);
        \Yii::info($serviceTypeData, 'postData');
        return Json::success(Common::key2lowerCamel($serviceTypeData));
    }


    /** 基础信息-服务选择城市,服务类型 下拉框api
     * @param  int  $operatingType  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionCityServiceType()
    {
        // 参数接收
        $request = $this->getRequest();
        $operatingType = trim($request->post('operatingType'));

        //参数验证
        if(!$operatingType) return Json::message('参数为空或不支持的数据类型');

        //数据处理(查询)
        $CityServiceType = Service::getCityServiceType($operatingType);
        return Json::success(Common::key2lowerCamel($CityServiceType));
    }


    /** 基础信息-添加服务api
     * @param int  $cityCode 城市编码
     * @param int  $serviceTypeId  服务类型id
     * @param  int  $serviceStatus  是否开通 0未开通 1开通
     * @param  int  $togetherOrderNumber 同时可下单数量
     * @return  array
     * @author lrn
     */
    public  function  actionServiceAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['service_type_id'] = $request->post('serviceTypeId');
        $requestData['service_status'] = $request->post('serviceStatus');
        $requestData['together_order_number'] = $request->post('togetherOrderNumber');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new Service();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        $serviceData=Service::getServiceAdd($requestData);

        //数据处理（添加,加入redis缓存）
        if ($serviceData > 0)  return Json::message('添加服务类型成功', 0);
        if ($serviceData == 0) return Json::message('添加服务失败,请检查参数是否正确！');
        if ($serviceData < 0)  return Json::message('该服务已经存在，不可重复添加！');
    }


    /** 基础信息-修改服务api
     * @param int $serviceId 当前服务id
     * @param int  $cityCode 城市编码
     * @param int  $serviceTypeId  服务类型id
     * @param  int  $serviceStatus  是否开通 0未开通 1开通
     * @param  int  $togetherOrderNumber 同时可下单数量
     * @return  array
     * @author lrn
     */
    public  function  actionServiceUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $serviceId = $request->post('serviceId');
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['service_type_id'] = $request->post('serviceTypeId');
        $requestData['service_status'] = $request->post('serviceStatus');
        $requestData['together_order_number'] = $request->post('togetherOrderNumber');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new Service();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理（修改）
        $serviceData=Service::find()->where(['id'=>$serviceId])->asArray()->one();
        if(!$serviceData){
            return Json::message('修改服务失败');
        }
        if (Service::ServiceUpdate($requestData,$serviceId)) {
            Service::ServiceStatus($requestData['service_status'],$serviceData['city_code'],$requestData['operator_id']);
            return Json::message('修改服务成功', 0);
        }else{
            return Json::message('修改服务失败');
        }
    }


    /** 基础信息-服务状态开启暂停api
     * @param int $serviceId 当前服务id
     * @param  int  $serviceStatus  是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionServiceStatusUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $serviceId = trim($request->post('serviceId'));
        $requestData['service_status'] = trim($request->post('serviceStatus'));
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!$serviceId) return Json::message('参数为空或不支持的数据类型');

        //数据处理（修改）
        $serviceData=Service::find()->where(['id'=>$serviceId])->asArray()->one();
        if(!$serviceData){
            return Json::message('修改服务状态开启暂停失败');
        }
        if (Service::ServiceUpdate($requestData,$serviceId)) {
            Service::ServiceStatus($requestData['service_status'],$serviceData['city_code'],$requestData['operator_id']);
            return Json::message('修改服务状态开启暂停成功', 0);
        }else{
            return Json::message('修改服务状态开启暂停失败');
        }
    }


    /** 基础信息-服务状态开启暂停api
     * @param int $cityCode 当前服务id
     * @return  array
     * @author lrn
     */
    public  function  actionGetServiceType()
    {
        $request = $this->getRequest();
        $cityCode = $request->post('cityCode');
        if(!$cityCode) return Json::message('参数为空或不支持的数据类型');

        $serviceTypeArr = ServiceLogic::getServiceType($cityCode);
        return Json::success(Common::key2lowerCamel($serviceTypeArr));
    }
}
