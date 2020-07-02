<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use common\logic\LogicTrait;
/**
 * This is the model class for table "{{%channel}}".
 *
 * @property int $id
 * @property string $channel_name 渠道名称
 * @property int $channel_status 渠道开启暂停状态 1开启 0暂停
 * @property string $operator_id 操作人id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class Channel extends BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%channel}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['channel_status','operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['channel_name'], 'string', 'max' => 64],
            [['channel_status', 'channel_name'], 'required'],
            [['channel_status', 'channel_name'], 'trim'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'channel_name' => 'Channel Name',
            'channel_status' => 'Channel Status',
            'operator_id' => 'Operator Id',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }


    /**
     * 渠道列表&渠道多条件搜索
     * @param array $requestData
     * @return array
     */
    public static function getChannelList($requestData){
        $query = self::find();
        if ($requestData['channel_name'])
            $query->Where(['like', 'channel_name', $requestData['channel_name']]);
        if ($requestData['channel_status'] === '0' || $requestData['channel_status'] == 1)
            $query->andWhere(['channel_status'=>intval($requestData['channel_status'])]);
        $channelList = self::getPagingData($query, null, true);

        LogicTrait::fillUserInfo($channelList['data']['list']);
        return $channelList['data'];
    }


    /**
     * 渠道添加
     * @param array $requestData
     * @return int
     */
    public static function getChannelAdd($requestData){
        return static::add($requestData);
    }


    /**
     * 渠道详情
     * @param int $channelId
     * @return Mixed
     */
    public static function getChannelInfo($channelId){
       // $Info = static::showBatch($channelId,2);
        $Info = self::find()->where(['id'=>$channelId])->asArray()->all();
        LogicTrait::fillUserInfo($Info);
        return $Info[0];
    }


    /**
     * 渠道修改
     * @param array $requestData
     * @param int $channelId
     * @return boolean
     */
    public static function ChannelUpdate($requestData,$channelId){
        $result = self::find()->select("id")->where(['id'=>$channelId])->asArray()->one();
        if($result){
            return static::edit($channelId,$requestData,true);
        }else{
            return false;
        }
    }

    /**
     * 检测字段是否存已经在
     * @param string $checkName
     * @param string $fieldName
     * @return boolean
     */
    public static function getChannelCheck($checkName,$fieldName,$removeId = null){
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



    /**
     * 渠道名称数据
     * @return array
     */
    public static function getChannelName(){
        $channelName= self::find()
            ->select("id,channel_name")
            ->asArray()->all();
        return $channelName;
    }


}
