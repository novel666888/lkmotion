<?php

namespace driver\modules\basic\controllers;

use common\logic\FileUrlTrait;
use common\models\AppVersionUpdate;
use common\util\Json;
use yii\web\Controller;


class AppVersionController extends Controller
{
    use FileUrlTrait;

    public function actionLatest()
    {
//        $request = \Yii::$app->request;
//        $source = strtolower($request->post('source', 'Android'));

        $latestVersion = AppVersionUpdate::find()
            ->where(['platform' => 2, 'use_status' => 1, 'app_type' => 2])
            ->orderBy('version_code DESC, create_time DESC')
            ->limit(1)
            ->one();
        if (!$latestVersion) {
            return Json::success();
        }
        // 检测历史版本是否有强制更新
        $forceFlag = 1; // 强制更新表示
        if ($latestVersion->notice_type != $forceFlag) {
            $appVersionCode = abs(intval(\Yii::$app->request->post('appVersionCode'))) + 1;
            $forceVersion = AppVersionUpdate::find()
                ->where(['platform' => 2, 'use_status' => 1, 'app_type' => 2, 'notice_type' => $forceFlag])
                ->andWhere(['between', 'version_code', $appVersionCode, $latestVersion->version_code])
                ->limit(1)
                ->one();
            if ($forceVersion) {
                $latestVersion->notice_type = $forceFlag;
            }
        }
        $responseData = [
            "appVersion" => $latestVersion->app_version, // 版本号
            "appVersionCode" => $latestVersion->version_code, // 版本号
            "downloadUrl" => $this->patchOne($latestVersion->download_url), // 软件包下载地址
            "noticeId" => $latestVersion->note, // 更新规则ID
            "noticeType" => $latestVersion->notice_type, // 更新规则类型：1强制更新，2非强制更新
            "prompt" => $latestVersion->prompt, // 更新提示语
        ];
        return Json::success($responseData);
    }
}
