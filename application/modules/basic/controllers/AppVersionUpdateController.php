<?php
namespace application\modules\basic\controllers;

use common\util\Common;
use common\util\Json;
use common\models\AppVersionUpdate;
use application\controllers\BossBaseController;
/**
 * TvUpdate controller
 */
class AppVersionUpdateController extends BossBaseController
{
    /** 基础信息-App版本更新列表（乘客端，司机端，车机端）api
     * @param  int  $page  当前页数
     * @param  int  $pageSize  每页展示条数
     * @param  int  $appType  app类型1:乘客端 2:司机端 3:车机端
     * @return  array
     * @author lrn
     */
    public function actionAppVersionList()
    {
        //参数接收
        $request = $this->getRequest();
        $appType = trim($request->post('appType'));

        //数据处理(查询,分页)
        $versionList = AppVersionUpdate::AppVersionList($appType);
        return Json::success(Common::key2lowerCamel($versionList));
    }


    /** 基础信息-新增App版本更新列表（乘客端，司机端，车机端）api
     * @property string $appVersion 版本号
     * @property int $versionCode versionCode
     * @property int $platform 上线系统 平台 1: ios, 2: android
     * @property string $startTime 生效开始时间
     * @property string $downloadUrl 安装包URL
     * @property string $noticeType 是否强制更新 （1:强制 2:非强制）
     * @property string $prompt  文案升级提示
     * @property string $appType   app类型1:乘客端 2:司机端 3:车机端
     * @return  array
     * @author lrn
     */
    public function actionAppVersionAdd()
    {
        //参数接收
        $request = $this->getRequest();
        $requestData['app_version'] = $request->post('appVersion');
        $requestData['version_code'] = $request->post('versionCode');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['download_url'] = $request->post('downloadUrl');
        $requestData['prompt'] = $request->post('prompt');
        $requestData['app_type'] = $request->post('appType');
        $requestData['operator_id'] = $this->userInfo['id'];

        if($requestData['app_type'] == 3){
            $requestData['platform'] = 0;
            $requestData['notice_type'] = 0;
        }else{
            $requestData['platform'] = $request->post('platform');
            $requestData['notice_type'] = $request->post('noticeType');
        }


        //参数验证
        $AppVersion= new AppVersionUpdate();
        $AppVersion->load($requestData, '');
        $AppVersion->validate();
        if($AppVersion->getErrors()) return Json::message('参数为空或不支持的数据类型');

        //数据处理
        if (AppVersionUpdate::getAppVersionAdd($requestData))
            return Json::message('添加App版本成功', 0);
        else
            return Json::message('添加App版本失败');
    }


    /** 基础信息-新增App版本更新列表（乘客端，司机端，车机端）api
     * @property string $appId 应用id
     * @property string $appVersion 版本号
     * @property int $versionCode versionCode
     * @property int $platform 上线系统 平台 1: ios, 2: android
     * @property string $startTime 生效开始时间
     * @property string $downloadUrl 安装包URL
     * @property string $noticeType 是否强制更新 （1:强制 2:非强制）
     * @property string $prompt  文案升级提示
     * @property string $appType   app类型1:乘客端 2:司机端 3:车机端
     * @return  array
     * @author lrn
     */
    public function actionAppVersionUpdate()
    {
        //参数接收
        $request = $this->getRequest();
        $appId = $request->post('appId');
        $requestData['app_version'] = $request->post('appVersion');
        $requestData['version_code'] = $request->post('versionCode');
        $requestData['platform'] = $request->post('platform');
        $requestData['start_time'] = $request->post('startTime');
        $requestData['download_url'] = $request->post('downloadUrl');
        $requestData['notice_type'] = $request->post('noticeType');
        $requestData['prompt'] = $request->post('prompt');
        $requestData['app_type'] = $request->post('appType');
        $requestData['operator_id'] = $this->userInfo['id'];

        //参数验证
        $AppVersion= new AppVersionUpdate();
        $AppVersion->load($requestData, '');
        $AppVersion->validate();
        if($AppVersion->getErrors()) return Json::message('参数为空或不支持的数据类型');
        if(!AppVersionUpdate::getTvUpdateCheck($appId)) return Json::message('该记录不能修改');

        //数据处理
        if (AppVersionUpdate::getAppVersionUpadte($requestData,$appId))
            return Json::message('修改App版本成功', 0);
        else
            return Json::message('修改App版本失败');
    }

}
