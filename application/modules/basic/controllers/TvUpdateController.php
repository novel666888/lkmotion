<?php
namespace application\modules\basic\controllers;

use common\util\Common;
use common\util\Json;
use common\models\TvVersionUpdate;
use common\models\TvApps;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
/**
 * TvUpdate controller
 */
class TvUpdateController extends BossBaseController
{
    /** 基础信息-Tv平台更新列表api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionTvList()
    {
        //数据处理(查询,分页)
        $tvList = TvVersionUpdate::getTvList();
        return Json::success(Common::key2lowerCamel($tvList));
    }


    /** 基础信息-新增TV平台版本api
     * @property string $name TV平台版本名称
     * @property string $tvVersion 版本号
     * @property int $tvVersionCode tvVersionCode
     * @property string $startTime 生效开始时间
     * @property string $downloadUrl 安装包URL
     * @return  array
     * @author lrn
     */
    public function actionTvAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['name'] = $request->post('name');
        $requestData['tv_version'] = $request->post('tvVersion');
        $requestData['tv_version_code'] = $request->post('tvVersionCode');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['download_url'] = $request->post('downloadUrl');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $Tv = new TvVersionUpdate();
        $Tv->load($requestData, '');
        $Tv->validate();
        if($Tv->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        if (TvVersionUpdate::getTvAdd($requestData))
            return Json::message('添加Tv平台版本成功', 0);
        else
            return Json::message('添加Tv平台版本失败');
    }


    /** 基础信息-修改TV平台版本api
     * @property string $tvId tv平台id
     * @property string $name TV平台版本名称
     * @property string $tvVersion 版本号
     * @property int $tvVersionCode tvVersionCode
     * @property string $startTime 生效开始时间
     * @property string $downloadUrl 安装包URL
     * @return  array
     * @author lrn
     */
    public function actionTvUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $tvId = $request->post('tvId');
        $requestData['name'] = $request->post('name');
        $requestData['tv_version'] = $request->post('tvVersion');
        $requestData['tv_version_code'] = $request->post('tvVersionCode');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['download_url'] = $request->post('downloadUrl');
        $requestData['operator_id'] = $this->userInfo['id'];

        \Yii::info($requestData, 'getData');
        //参数验证
        $Tv = new TvVersionUpdate();
        $Tv->load($requestData, '');
        $Tv->validate();
        if($Tv->getErrors()){
            \Yii::info($Tv->getErrors(), 'getErrors');
            return Json::message('参数为空或不支持的数据类型');
        }
        if(!TvVersionUpdate::getTvUpdateCheck($tvId)) return Json::message('该记录不能修改');


        //数据处理
        if (TvVersionUpdate::getTvUpdate($requestData,$tvId))
            return Json::message('修改Tv平台版本成功', 0);
        else
            return Json::message('修改Tv平台版本失败');
    }



    /** 基础信息-Tv平台应用列表api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @return  array
     * @author lrn
     */
    public function actionTvAppsList()
    {
        //数据处理(查询,分页)
        $tvAppsList = TvApps::getTvAppsList();

        foreach ($tvAppsList['list'] as  $key =>$val){
            $val['re_ico_url'] = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl').$val['ico_url'];
            $tvAppData[] = $val;
        }
        $tvAppsList['list'] = $tvAppData;
        return Json::success(Common::key2lowerCamel($tvAppsList));
    }


    /** 基础信息-新增TV平台应用版本api
     * @property string $appName 应用名称
     * @property int $versionCode 版本号
     * @property string $startTime 开始更新时间
     * @property string $downLoadUrl 应用包下载链接
     * @property string $packageName 包名
     * @property string $icoUrl 应用图标
     * @property int $useStatus 应用状态 1启用 0停用
     * @property string TvVersioncode TV-Versioncode
     * @return  array
     * @author lrn
     */
    public function actionTvAppsAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['app_name'] = $request->post('appName');
        $requestData['version_code'] = $request->post('versionCode');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['down_load_url'] = $request->post('downLoadUrl');
        $requestData['package_name'] = $request->post('packageName');
        $requestData['ico_url'] = $request->post('icoUrl');
        $requestData['use_status'] = $request->post('useStatus');
        $requestData['tv_versioncode'] = $request->post('tvVersioncode');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $Tv = new TvApps();
        $Tv->load($requestData, '');
        $Tv->validate();
        if($Tv->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!TvApps::getTvAppsCheck(trim($requestData['app_name']),'app_name',null))  return Json::message('应用名称已经存在');
        if(!TvApps::getTvAppsCheck(trim($requestData['package_name']),'package_name',null))  return Json::message('应用包名已经存在');

        //数据处理
        if (TvApps::getTvAppsAdd($requestData))
            return Json::message('添加Tv平台应用成功', 0);
        else
            return Json::message('添加Tv平台应用失败');
    }


    /** 基础信息-删除Tv平台应用api
     * @property string $tvAppsId tv平台应用id
     * @return  array
     * @author lrn
     */
    public function actionTvAppsDelete()
    {
        //参数接收
        $request = $this->getRequest();
        $tvAppsId = trim($request->post('tvAppsId'));
        $operatorId = $this->userInfo['id'];

        //参数验证
       if(!$tvAppsId) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        if (TvApps::getTvAppsDelete($tvAppsId,$operatorId))
            return Json::message('删除Tv平台应用成功', 0);
        else
            return Json::message('删除Tv平台应用失败');
    }


    /** 基础信息-修改TV平台应用api
     * @property string $tvAppsId tv平台应用id
     * @property string $appName 应用名称
     * @property int $versionCode 版本号
     * @property string $startTime 开始更新时间
     * @property string $downLoadUrl 应用包下载链接
     * @property string $packageName 包名
     * @property string $icoUrl 应用图标
     * @property int $useStatus 应用状态 1启用 0停用
     * @property string TvVersioncode TV-Versioncode
     * @return  array
     * @author lrn
     */
    public function actionTvAppsUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $tvAppsId = trim($request->post('tvAppsId'));
        $requestData['app_name'] = $request->post('appName');
        $requestData['version_code'] = $request->post('versionCode');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['down_load_url'] = $request->post('downLoadUrl');
        $requestData['package_name'] = $request->post('packageName');
        $requestData['ico_url'] = $request->post('icoUrl');
        $requestData['use_status'] = $request->post('useStatus');
        $requestData['tv_versioncode'] = $request->post('tvVersioncode');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $Tv = new TvApps();
        $Tv->load($requestData, '');
        $Tv->validate();
        if($Tv->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!TvApps::getTvAppsCheck(trim($requestData['app_name']),'app_name',$tvAppsId))  return Json::message('应用名称已经存在');
        if(!TvApps::getTvAppsCheck(trim($requestData['package_name']),'package_name',$tvAppsId))  return Json::message('应用包名已经存在');

        //数据处理
        if (TvApps::getTvAppsUpdate($tvAppsId,$requestData))
            return Json::message('修改Tv平台应用成功', 0);
        else
            return Json::message('修改Tv平台应用失败');
    }


    /** 基础信息-TV平台应用排序api
     * @property string $tvAppsId tv平台应用id
     * @property string $operationType 操作类型  1上移  2下移 3置顶
     * @return  array
     * @author lrn
     */
    public function actionTvAppsSort()
    {
        //参数接收
        $request = $this->getRequest();
        $tvAppsId = trim($request->post('tvAppsId'));
        $operationType = trim($request->post('operationType'));
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        if(!$tvAppsId || !$operationType) return Json::message('参数为空或不支持的数据类型');
        if($operationType != 1 && $operationType != 2 && $operationType != 3) return Json::message('不支持的数据类型');

        //数据处理
        if (TvApps::getTvAppsSort($tvAppsId,$operationType,$requestData))
            return Json::message('Tv平台应用排序成功', 0);
        else
            return Json::message('Tv平台应用排序无效');
    }



}
