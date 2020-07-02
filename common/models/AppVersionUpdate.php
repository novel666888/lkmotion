<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
/**
 * This is the model class for table "tbl_app_version_update".
 *
 * @property string $id id
 * @property int $platform 上线系统 1: ios, 2: android
 * @property int $notice_type 通知类型（1:强制 2:非强制）
 * @property string $prompt 升级提示（不超过30个字）
 * @property string $note 备注
 * @property string $start_time 生效开始时间	
 * @property string $end_time 生效结束时间
 * @property string $download_url 安装包url
 * @property string $app_versions 需要升级的app客户端版本（以‘|’分隔）
 * @property string $os_versions 操作系统版本（以‘|’分隔）
 * @property string $create_time 创建时间
 * @property string $operator_id 创建人
 * @property int $app_type 1:乘客端 2:司机端 3:车机端
 * @property string $app_version
 * @property int $use_status 启用停用状态，0：停用，1：启用
 * @property int $version_code 版本号
 * @property string $update_time
 */
class AppVersionUpdate extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_app_version_update';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_version', 'version_code', 'platform', 'start_time', 'notice_type','prompt','app_type'], 'required'],
            [['app_version', 'version_code', 'platform', 'start_time', 'notice_type','prompt','app_type'], 'trim'],
            [['platform', 'notice_type', 'app_type', 'use_status', 'version_code','operator_id'], 'integer'],
            [['start_time', 'end_time', 'create_time', 'update_time'], 'safe'],
            [['prompt'], 'string', 'max' => 50],
            [['note', 'download_url'], 'string', 'max' => 500],
            [['app_version'], 'string', 'max' => 16],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'notice_type' => 'Notice Type',
            'prompt' => 'Prompt',
            'note' => 'Note',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'download_url' => 'Download Url',
            'create_time' => 'Create Time',
            'operator_id' => 'Operator Id',
            'app_type' => 'App Type',
            'app_version' => 'App Version',
            'use_status' => 'Use Status',
            'version_code' => 'Version Code',
            'update_time' => 'Update Time',
        ];
    }
    
    
    /**
     * APP更新应用列表
     * @param  int  $appType  app类型1:乘客端 2:司机端 3:车机端
     * @return array
     */
    public static function AppVersionList($appType){
        $query = self::find();
        //$query->select("id,app_version,version_code,platform,notice_type,app_type,prompt
        //,start_time,update_time,create_time,operator_id");
        $query->select("*");
        if ($appType)
            $query->Where(['app_type'=>$appType]);
        $appVersionList = self::getPagingData($query, null, true);
        LogicTrait::fillUserInfo($appVersionList['data']['list']);
        $appVersionList['data']['currentTime']=time()."000";
        return $appVersionList['data'];
    }
    
    /**
     * APP平台应用添加
     * @param array $requestData
     * @return string
     */
    public static function getAppVersionAdd($appVersionData){
        return self::add($appVersionData);
    }

    /**
     * APP平台应用是否可修改
     * @param int $appId
     * @return boolean
     */
    public static function getTvUpdateCheck($appId){
        $query = self::find()->select("start_time");
        $tvData=$query->where([ 'id' => $appId ])->asArray()->one();
        $currentTime=time();
        if(strtotime($tvData['start_time'])>$currentTime)
            return true;
        else
            return false;
    }

    /**
     * APP平台应用修改
     * @param array $requestData
     * @param int $appId
     * @return boolean
     */
    public static function getAppVersionUpadte($requestData,$appId){
        return self::edit($appId,$requestData,true);
    }

    /**
     * 查询乘客端是否需要返回升级提示
     * @return array
     */
    public static function checkVersion($condition, $field=['*']){
        $model = self::find()->select($field);
        $model->andFilterWhere(['platform'=>intval($condition['platform'])]);
        $model->andFilterWhere(['>', 'version_code', intval($condition['versionCode'])]);
        $date = date("Y-m-d H:i:s", time());
        $model->andFilterWhere(['<', 'start_time', $date]);
        $jg = $model->andFilterWhere(['app_type'=>1])->andFilterWhere(['use_status'=>1])
            ->orderby("start_time DESC")->asArray()->one();
        if(!empty($jg)){
            $model = self::find();
            $model->andFilterWhere(['platform'=>intval($condition['platform'])]);
            $model->andFilterWhere(['>', 'version_code', intval($condition['versionCode'])]);
            //$date = date("Y-m-d H:i:s", time());
            $model->andFilterWhere(['<', 'start_time', $jg['start_time']]);
            $jg2 = $model->andFilterWhere(['app_type'=>1])->andFilterWhere(['use_status'=>1])
                ->andFilterWhere(['notice_type'=>1])
                ->asArray()->all();
            if(!empty($jg2)){
                $jg['notice_type'] = 1;
            }
            $jg['download_url'] = \Yii::$app->params['ossFileUrl'].$jg['download_url'];
            return ['code'=>0, 'data'=>$jg];
        }else{
            return ['code'=>0, 'data'=>[]];
        }
    }
    
}
