<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
/**
 * This is the model class for table "{{%tv_version_update}}".
 *
 * @property int $id ID
 * @property string $name TV平台版本名称
 * @property int $tv_version_code tvVersionCode
 * @property string $tv_version 版本号
 * @property int $notice_type 通知类型（1:强制 2:非强制）
 * @property string $prompt 升级提示（不超过30个字）
 * @property string $note 备注
 * @property string $start_time 生效开始时间	
 * @property string $end_time 生效结束时间
 * @property string $download_url 安装包URL
 * @property string $create_time 创建时间
 * @property string $operator_id 创建人
 * @property int $use_status 启用停用状态，0：停用，1：启用
 * @property string $update_time 更新时间
 */
class TvVersionUpdate extends BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tv_version_update}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'tv_version', 'tv_version_code','start_time','download_url'], 'required'],
            [['name', 'tv_version', 'tv_version_code','start_time','download_url'], 'trim'],
            [['tv_version_code', 'notice_type', 'use_status','operator_id'], 'integer'],
            [[ 'end_time', 'create_time', 'update_time'], 'safe'],
            [['name', 'tv_version'], 'string', 'max' => 16],
            [['prompt'], 'string', 'max' => 50],
            [['note'], 'string', 'max' => 500],
            [['download_url'], 'string', 'max' => 512],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'tv_version_code' => 'Tv Version Code',
            'tv_version' => 'Tv Version',
            'notice_type' => 'Notice Type',
            'prompt' => 'Prompt',
            'note' => 'Note',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'download_url' => 'Download Url',
            'create_time' => 'Create Time',
            'operator_id' => 'Operator Id',
            'use_status' => 'Use Status',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * TV平台更新列表
     * @return array
     */
    public static function getTvList(){
        $query = self::find();
        $query->select("id,name,tv_version_code,tv_version,start_time,download_url,update_time,operator_id,create_time");
        $TvList = static::getPagingData($query, null, true);
        LogicTrait::fillUserInfo($TvList['data']['list']);
        $TvList['data']['current_time']=time()."000";
        return $TvList['data'];
    }

    /**
     * 添加TV平台更新
     * @return string
     */
    public static function getTvAdd($tvData){
        return static::add($tvData);
    }

    /**
     * 检测TV平台是否可修改
     * @param int $tvId
     * @return boolean
     */
    public static function getTvUpdateCheck($tvId){
        $query = self::find()->select("start_time");
        $tvData=$query->where([ 'id' => $tvId ])->asArray()->one();
        $currentTime=time();
        if(strtotime($tvData['start_time'])>$currentTime)
            return true;
        else
            return false;
    }

    /**
     * 修改TV平台更新
     * @param array $requestData
     * @param int $tvId
     * @return boolean
     */
    public static function getTvUpdate($requestData,$tvId){

        return static::edit($tvId,$requestData,true);
    }


    /**
     * 大屏TV平台升级
     * @return boolean
     */
    public static function tvUpdate(){
        $query = self::find();
        $query->select("id,tv_version_code,download_url,start_time");
        $query->where(['<','start_time',date("Y-m-d H:i:s", time())]);
        $TvAppsList = $query->andWhere("use_status=1")->orderBy('start_time desc')->asArray()->one();
        \Yii::info($TvAppsList, 'TvAppsList');
        return $TvAppsList;
    }




}
