<?php
namespace application\modules\basic\controllers;

use common\util\Common;
use common\util\Json;
use common\models\City;
use common\models\Channel ;
use common\util\Cache;
use common\logic\LogicTrait;
use application\controllers\BossBaseController;
/**
 * City controller
 */
class CityController extends BossBaseController
{
    /** 基础信息-城市列表api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionCityList()
    {
        //数据处理(查询,分页)
        $cityData = City::getCityList();
        LogicTrait::fillUserInfo($cityData['list']);
        return Json::success(Common::key2lowerCamel($cityData));
    }


    /** 基础信息-添加城市api
     * @param string  $cityName 城市名称
     * @param string $cityCode 城市编码
     * @param string $cityLongitudelatitude 城市中心经维度
     * @param int $orderRiskTop 下单风险上限值
     * @param int $cityStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionCityAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['city_name'] = $request->post('cityName');
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['city_longitude_latitude'] = $request->post('cityLongitudeLatitude');
        $requestData['order_risk_top'] = $request->post('orderRiskTop');
        $requestData['city_status'] = $request->post('cityStatus');
        $requestData['operator_id'] =  $this->userInfo['id'];

        //参数验证
        $City = new City();
        $City->load($requestData, '');
        $City->validate();
        if($City->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if($requestData['city_status']==1) return Json::message('添加城市失败，默认城市状态请设置为禁用！');
        if(!City::getCityCheck(trim($requestData['city_name']),'city_name',null))  return Json::message('城市名称已经存在');
        if(!City::getCityCheck(trim($requestData['city_code']),'city_code',null))  return Json::message('城市编码已经存在');
        if($requestData['city_status']!=0 && $requestData['city_status']!=1) return Json::message('请检查城市状态');

        //数据处理（添加,加入redis缓存）
        if (!$City->save())
            return Json::message('添加城市失败');
        else
            $insertId = $City->attributes['id'];
            $insertData = City::find()->where(['id'=>$insertId])->indexBy('city_code')->asArray()->all();
            Cache::set('tbl_city', $insertData, 0);
            return Json::message('添加城市成功', 0);
    }


    /** 基础信息-城市详情api
     * @param int $cityId 当前城市id
     * @return  array
     * @author lrn
     */
    public  function  actionCityInfo()
    {
        // 参数接收
        $request = $this->getRequest();
        $cityCode = intval($request->post('cityCode'));

        //参数验证
        if (!trim($cityCode)) return Json::message('参数不可为空');

        //数据处理（查询 redis缓存操作）
        $cityInfo = Cache::get('tbl_city', $cityCode);
        $cityCache =$cityInfo['tbl_city_' . $cityCode] ;
        if (empty($cityCache)){
            $cityInfo = City::getCityInfo($cityCode);
            if($cityInfo){
                $cityCache =$cityInfo[$cityCode] ;
                if (!$cityInfo) return Json::message('城市不存在');
                Cache::set('tbl_city', $cityInfo, 0);//加入到缓存中
            }else{
                $cityCache=array();
            }
        }
        return Json::success(Common::key2lowerCamel($cityCache));
    }


    /** 基础信息-修改城市api
     * @param int $cityId 当前城市id
     * @param string  $cityName 城市名称
     * @param string $cityCode 城市编码
     * @param string $cityLongitudelatitude 城市中心经维度
     * @param int $orderRiskTop 下单风险上限值
     * @param int $cityStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionCityUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $cityId = $request->post('cityId');
        $requestData['city_name'] = $request->post('cityName');
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['city_longitude_latitude'] = $request->post('cityLongitudeLatitude');
        $requestData['order_risk_top'] = $request->post('orderRiskTop');
        $requestData['city_status'] = $request->post('cityStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new City();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!City::getCityCheck(trim($requestData['city_name']),'city_name',$cityId))  return Json::message('城市名称已经存在');
        if(!City::getCityCheck(trim($requestData['city_code']),'city_code',$cityId))  return Json::message('城市编码已经存在');
        if($requestData['city_status']!=0 && $requestData['city_status']!=1) return Json::message('请检查城市状态');

        //数据处理（修改,更新redis缓存）
        $citymodel = City::find()->where(['id'=>$cityId])->one();
        if($citymodel){
            $cityStatus = City::getCityStatus($cityId,$requestData['city_status'],$requestData['operator_id']);
            if($cityStatus) return Json::message($cityStatus);
            $citymodel->load($requestData,'');
            if ( $citymodel->save()) {
                $cityData = City::find()->where(['id'=>$cityId])->indexBy('city_code')->asArray()->all();
                Cache::set('tbl_city', $cityData, 0);
                return Json::message('修改城市成功', 0);
            }else{
                return Json::message('修改城市失败');
            }
        }else{
            return Json::message('城市不存在');
        }
    }


    /** 基础信息-修改城市开启状态api
     * @param int $cityId 当前城市id
     * @param int $cityStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionCityStatusUpdate()
    {
        // 参数接收
        $request = $this->getRequest();
        $cityId = $request->post('cityId');
        $requestData['city_status'] = $request->post('cityStatus')? $request->post('cityStatus') : '0';
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!trim($cityId))  return Json::message('参数不可为空');
        if($requestData['city_status']!=0 && $requestData['city_status']!=1) return Json::message('请检查城市状态');

        //数据处理（修改,redis数据更新）
        $citymodel = City::find()->where(['id'=>$cityId])->one();
        if(!$citymodel) return Json::message('该城市不存在');
        $citymodel->city_status = $requestData['city_status'];
        $citymodel->operator_id = $this->userInfo['id'];
        //City::getCityStatus($cityId,$requestData['city_status'],$requestData['operator_id']);
        $cityStatus = City::getCityStatus($cityId,$requestData['city_status'],$requestData['operator_id']);
        if($cityStatus) return Json::message($cityStatus);

        if ($citymodel->save()) {
            $cityData = City::find()->where(['id'=>$cityId])->indexBy('city_code')->asArray()->all();
            Cache::set('tbl_city', $cityData, 0);
            return Json::message('修改城市状态成功', 0);
        }else{
            return Json::message('修改城市状态失败');
        }
    }


    /** 基础信息-渠道列表api&渠道搜索api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @param  string  $channelName  渠道名称
     * @param  int  $channelStatus  是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public function actionChannelList()
    {
        // 参数接收
        $request = $this->getRequest();
        $requestData['channel_name'] = trim($request->post('channelName'));
        $requestData['channel_status'] = trim($request->post('channelStatus'));

        //数据处理(查询,分页)
        $channeData = Channel::getChannelList($requestData);
        return Json::success(Common::key2lowerCamel($channeData));
    }


    /** 基础信息-添加渠道api
     * @param string  $channelName 渠道名称
     * @param int $channelStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionChannelAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['channel_name'] = $request->post('channelName');
        $requestData['channel_status'] = $request->post('channelStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new Channel();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!Channel::getChannelCheck(trim($requestData['channel_name']),'channel_name',null))
            return Json::message('渠道名称已经存在');

        //数据处理（添加）
       if (Channel::getChannelAdd($requestData))
           return Json::message('添加渠道成功', 0);
        else
            return Json::message('添加渠道失败');
    }


    /** 基础信息-渠道详情api
     * @param int $channelId 当前渠道id
     * @return  array
     * @author lrn
     */
    public  function  actionChannelInfo()
    {
        // 参数接收
        $request = $this->getRequest();
        $channelId = intval($request->post('channelId'));

        //参数验证
        if(!trim($channelId))  return Json::message('参数不可为空');

        //数据处理（查询）
        $channelIdCache = Channel::getChannelInfo($channelId);
        return Json::success(Common::key2lowerCamel($channelIdCache));
    }


    /** 基础信息-修改渠道api&
     * @param int $channelId 当前渠道id
     * @param string  $channelName 渠道名称
     * @param int $channelStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionChannelUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $channelId = $request->post('channelId');
        $requestData['channel_name'] = $request->post('channelName');
        $requestData['channel_status'] = $request->post('channelStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new Channel();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!Channel::getChannelCheck(trim($requestData['channel_name']),'channel_name',$channelId))  return Json::message('渠道名称已经存在');

        //数据处理（修改）
        if (Channel::ChannelUpdate($requestData,$channelId))
            return Json::message('修改渠道成功', 0);
        else
            return Json::message('修改渠道失败');
    }


    /** 基础信息-渠道状态开启暂停api
     * @param int $channelId 当前渠道id
     * @param int $channelStatus 是否开通 0未开通 1开通
     * @return  array
     * @author lrn
     */
    public  function  actionChannelStatusUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $channelId = trim($request->post('channelId'));
        $requestData['channel_status'] = trim($request->post('channelStatus'));
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!$channelId) return Json::message('参数为空或不支持的数据类型');

        //数据处理（修改）
        if (Channel::ChannelUpdate($requestData,$channelId))
            return Json::message('渠道状态开启暂停成功', 0);
        else
            return Json::message('渠道状态开启暂停失败');
    }


    /** 基础信息-渠道名称api
     * @return  array
     * @author lrn
     */
    public  function  actionChannelName()
    {
        //数据处理（查询）
        $channelName = Channel::getChannelName();
        return Json::success(Common::key2lowerCamel($channelName));
    }
}

















