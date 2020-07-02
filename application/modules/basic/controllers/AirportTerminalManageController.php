<?php
namespace application\modules\basic\controllers;

use common\util\Common;
use common\util\Json;
use common\logic\LogicTrait;
use common\models\AirportTerminalManage;
use application\controllers\BossBaseController;
/**
 * AirportTerminalManage controller
 */
class AirportTerminalManageController extends BossBaseController
{
    /** 基础信息-机场航站楼列表
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionAirportTerminalList()
    {
        //数据处理(查询,分页)
        $airportTerminal = AirportTerminalManage::getAirportTerminalList();
        LogicTrait::fillUserInfo($airportTerminal['list']);
        return Json::success(Common::key2lowerCamel($airportTerminal));
    }


    /** 基础信息-机场航站楼添加
     * @param int  $city_code 城市编码
     * @param string  $airport_name 机场名称
     * @param  string  $terminal_name 航站楼名称
     * @param  string  $terminal_longitude_latitude 航站楼经纬度
     * @param  int  $airport_terminal_status 状态 1开启 0禁用
     * @return  array
     * @author lrn
     */
    public  function  actionAirportTerminalAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['airport_name'] = $request->post('airportName');
        $requestData['terminal_name'] = $request->post('terminalName');
        $requestData['terminal_longitude_latitude'] = $request->post('terminalLongitudeLatitude');
        $requestData['airport_terminal_status'] = $request->post('airportTerminalStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new AirportTerminalManage();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理（添加,加入redis缓存）
        if (AirportTerminalManage::getAirportTerminalAdd($requestData))
            return Json::message('添加机场航站楼成功', 0);
        else
            return Json::message('添加失败，城市不存在！');
    }


    /** 基础信息-机场航站楼详情
     * @param int $id 机场航站楼id
     * @return  array
     * @author lrn
     */
    public  function  actionAirportTerminalInfo()
    {
        //参数接收
        $request = $this->getRequest();
        $id = $request->post('id');

        //参数验证
        if(!$id) return Json::message('参数为空或不支持的数据类型');

        //数据处理（添加,加入redis缓存）
        $airportTerminalInfo = AirportTerminalManage::getAirportTerminalInfo($id);
        LogicTrait::fillUserInfo($airportTerminalInfo);
        return Json::success(Common::key2lowerCamel($airportTerminalInfo));
    }


    /** 基础信息-编辑机场航站楼
     * @param int $id 机场航站楼id
     * @param int  $city_code 城市编码
     * @param string  $airport_name 机场名称
     * @param  string  $terminal_name 航站楼名称
     * @param  string  $terminal_longitude_latitude 航站楼经纬度
     * @param  int  $airport_terminal_status 状态 1开启 0禁用
     * @return  array
     * @author lrn
     */
    public  function  actionAirportTerminalUpdate()
    {
        $request = $this->getRequest();
        $id = $request->post('id');
        $requestData['city_code'] = $request->post('cityCode');
        $requestData['airport_name'] = $request->post('airportName');
        $requestData['terminal_name'] = $request->post('terminalName');
        $requestData['terminal_longitude_latitude'] = $request->post('terminalLongitudeLatitude');
        $requestData['airport_terminal_status'] = $request->post('airportTerminalStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $model = new AirportTerminalManage();
        $model->load($requestData, '');
        $model->validate();
        if($model->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        if (AirportTerminalManage::getAirportTerminalUpdate($requestData,$id))
            return Json::message('修改机场航站楼成功', 0);
        else
            return Json::message('修改机场航站楼失败，城市不存在！');
    }


    /** 基础信息-机场航站楼冻结解冻
     * @param int $id 机场航站楼id
     * @param  int  $airport_terminal_status 状态 1开启 0禁用
     * @return  array
     * @author lrn
     */
    public  function  actionAirportTerminalStatusUpdate()
    {
        $request = $this->getRequest();
        $id = $request->post('id');
        $requestData['airport_terminal_status'] = $request->post('airportTerminalStatus');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!$id) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        if (AirportTerminalManage::getAirportTerminalUpdate($requestData,$id))
            return Json::message('更改机场航站楼状态成功', 0);
        else
            return Json::message('更改机场航站楼状态失败！');
    }


    /** 基础信息-机场航站楼删除
     * @param int $id 机场航站楼id
     * @return  array
     * @author lrn
     */
    public  function  actionAirportTerminalDelete()
    {
        $request = $this->getRequest();
        $id = $request->post('id');

        //参数验证
        if(!$id) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        $model = AirportTerminalManage::findOne($id);
        if(!$model) return Json::message('该机场航站楼不存在！');

        if ($model->delete())
            return Json::message('删除机场航站楼成功', 0);
        else
            return Json::message('删除机场航站楼失败！');
    }

}
