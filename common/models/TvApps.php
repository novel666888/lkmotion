<?php

namespace common\models;

use Yii;
use yii\db;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
/**
 * This is the model class for table "{{%tv_apps}}".
 *
 * @property int $id
 * @property string $app_name 应用名称
 * @property int $version_code 版本号
 * @property string $start_time 开始更新时间
 * @property string $down_load_url 应用包下载链接
 * @property string $package_name 包名
 * @property string $ico_url 应用图标
 * @property int $use_status 应用状态 1启用 0停用
 * @property int $position 应用位置
 * @property int $is_del 是否删除 1是 0否
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property string $operator_id 创建人
 */
class TvApps extends BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tv_apps}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['app_name', 'tv_versioncode','version_code', 'start_time', 'down_load_url', 'package_name', 'ico_url', 'use_status'], 'required'],
            [['app_name','tv_versioncode', 'version_code', 'start_time', 'down_load_url', 'package_name', 'ico_url', 'use_status'], 'trim'],
            [['version_code', 'use_status', 'position', 'is_del','operator_id'], 'integer'],
            [['start_time', 'create_time', 'update_time'], 'safe'],
            [['app_name'], 'string', 'max' => 255],
            [['down_load_url', 'ico_url'], 'string', 'max' => 255],
            [['package_name'], 'string', 'max' => 32],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_name' => 'App Name',
            'tv_versioncode' => 'Tv Versioncode',
            'version_code' => 'Version Code',
            'start_time' => 'Start Time',
            'down_load_url' => 'Down Load Url',
            'package_name' => 'Package Name',
            'ico_url' => 'Ico Url',
            'use_status' => 'Use Status',
            'position' => 'Position',
            'is_del' => 'Is Del',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_id' => 'Operator Id',
        ];
    }


    /**
     * TV平台应用列表
     * @return array
     */
    public static function getTvAppsList(){
        $query = self::find();
        $query->select("id,app_name,tv_versioncode,position,ico_url,down_load_url,package_name,version_code,use_status,start_time,create_time,update_time,operator_id");
        $query->where(['is_del'=>[0]]);

        $TvAppsList = static::getPagingData($query, ['position'=>'desc'], true);

        LogicTrait::fillUserInfo($TvAppsList['data']['list']);
        return $TvAppsList['data'];
    }


    /**
     * 检测字段是否存已经在
     * @param string $checkName
     * @param string $fieldName
     * @return boolean
     */
    public static function getTvAppsCheck($checkName,$fieldName,$removeId = null){
        $query = self::find()
            ->select("id")
            ->where([$fieldName=>$checkName]);
        if (!empty($removeId))
            $query->andWhere(['!=', 'id', $removeId]);
        $result = $query->asArray()->all();
        if($result)
            return false;
        else
            return true;
    }


    /** TV平台应用添加
    * @return string
    */
    public static function getTvAppsAdd($tvAppsData){
        $position = self::find();
        $positionArr= $position->select('position')->orderBy('position desc')->asArray()->one();
        $tvAppsData['position']=$positionArr['position'] + 1;
        return static::add($tvAppsData);
    }


    /** TV平台应用删除
     * @return string
     */
    public static function getTvAppsDelete($tvAppsId,$operatorId){
        $positionArr= self::find()->select('position')->where(['id'=>$tvAppsId])->asArray()->one();
        if(!$positionArr) return false;

        $tvAppsData['is_del']=1;
        $tvAppsData['use_status']=0;
        $tvAppsData['operator_id']=$operatorId;
        return static::edit($tvAppsId,$tvAppsData,true);
    }

    /** TV平台应用修改
     * @return string
     */
    public static function getTvAppsUpdate($tvAppsId,$tvAppsData){
        $positionArr= self::find()->select('position')->where(['id'=>$tvAppsId])->asArray()->one();
        if(!$positionArr) return false;
        return static::edit($tvAppsId,$tvAppsData,true);
    }

    /** TV平台应用排序
     * @return string
     */
    public static function getTvAppsSort($tvAppsId,$operationType,$requestData){
        $position = self::find();
        $positionArr= $position->select('position')->where(['id'=>$tvAppsId])->asArray()->one();
        if(!$positionArr) return false;

        if($operationType == 1){
            $positionMin = $position->select('position')->where(['is_del'=>0])->orderBy([ 'position' => SORT_ASC ])->limit(1)->asArray()->one();
            if($positionArr['position'] == $positionMin['position']) return false;

            $positionType='<';
            $positionOrder=SORT_DESC;
        }
        if($operationType == 2){
            $positionType='>';
            $positionOrder=SORT_ASC;
        }
        if($operationType == 3)
        {
            $positionMax = $position->select('position')->where(['is_del'=>0])->orderBy([ 'position' => SORT_DESC ])->limit(1)->asArray()->one();
            if($positionArr['position'] != $positionMax['position']) return false;

            $position->select('position')->where(['is_del'=>0])->andWhere(['<','position',$positionArr['position']]);
            $positionData = $position->orderBy([ 'position' => SORT_ASC ])->limit(1)->asArray()->one();
            if(!$positionData) return false;

            $requestData['position'] = $positionData['position'] - 1;
            $query = self::find()->where(['id' => $tvAppsId])->one();
            $query->setAttributes($requestData);
            if($query->save()) return true;
        }

        $position->select('position')->where(['is_del'=>0]);
        $position->andWhere([$positionType,'position',$positionArr['position']]);
        $position->orderBy([
            'position' => $positionOrder,
        ]);
        $positionData = $position->limit(1)->asArray()->one();

        if(!$positionData) return false;

        $requestData['position'] = $positionData['position'];
        $requestDataFront['position']=$positionArr['position'];
        $requestDataFront['operator_id']=$requestData['operator_id'];

        $queryOperation = self::find()->where(['position' => $positionData['position']])->one();
        $queryOperation->setAttributes($requestDataFront);

        $query = self::find()->where(['id' => $tvAppsId])->one();
        $query->setAttributes($requestData);

        if($queryOperation->save() && $query->save()) return true;
    }


    /**
     * TV平台应用大屏展示列表
     * @return array
     */
    public static function getTvApps(){
        $query = self::find();
        $query->where(['<','start_time',date("Y-m-d H:i:s", time())]);
        $query->select("id,app_name,version_code,start_time,down_load_url,package_name,ico_url,use_status,position,is_del");
        $TvAppsList = $query->orderBy('position asc')->asArray()->all();
        \Yii::info($TvAppsList, 'TvAppsListModel');
        return $TvAppsList;
    }


}
