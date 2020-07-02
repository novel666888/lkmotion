<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%push_account}}".
 *
 * @property int $id
 * @property string $source 设备来源 iOS,Android
 * @property string $jpush_id 消息推送设备id
 * @property string $yid 系统用户号，司机、乘客用yid，大屏、车机用唯一码
 * @property int $audience 听众类型：1：别名，2：注册Id
 * @property int $identity_status 身份标记 1:乘客，2：司机，3：车机，4：大屏
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class PushAccount extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%push_account}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['yid', 'identity_status'], 'required'],
            [['audience', 'identity_status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['source'], 'string', 'max' => 32],
            [['jpush_id'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source' => 'Source',
            'jpush_id' => 'Jpush ID',
            'yid' => 'Yid',
            'audience' => 'Audience',
            'identity_status' => 'Identity Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 插入一条极光推送
     * @param array $upData
     * @return
     */
    public static function addGpush($upData,$type=null)
    {
        if (empty($upData['yid']) || empty($upData['identity_status'])) {
            return false;
        }
        if($type==1){
            $modelData = self::find()->select(['*']);
            $data = $modelData->where(['identity_status' => $upData['identity_status']])
                ->andWhere(['jpush_id' => $upData['jpush_id']])->asArray()->one();
            \Yii::info($data, "pushData");
            if($data){
                return true;
            }
        }

        $model = self::find()->select(['*']);
        $model = $model->andFilterWhere(['yid' => $upData['yid']])->andFilterWhere(['identity_status' => $upData['identity_status']])->one();
        if ($model) {
            //return false;
            $model->jpush_id            = $upData['jpush_id'];
            $model->source              = $upData['source'];
            $model->audience            = $upData['audience'];
        }else{
            $model = new PushAccount();
        }
        $model->load($upData, "");
        if (!$model->validate()) {
            // $model->getFirstError();
            \Yii::info($model->getFirstError(), "add Jpush 1");
            return false;
        } else {
            if ($model->save()) {
                return true;
            } else {
                //return $model->getErrors();
                \Yii::info($model->getErrors(), "add Jpush 2");
                return false;
            }
        }
    }

    /**
     * 绑定司机推送ID
     * @param $driverId
     * @param $pushId
     * @param string $source
     * @return bool
     */
    public static function bindDriverPushId($driverId, $pushId, $source = 'android')
    {
        $pushRecord = self::find()
            ->where(['yid' => $driverId])
            ->andWhere(['identity_status' => 2])
            ->limit(1)
            ->one();
        if (!$pushRecord) {
            $pushRecord = new self();
        }
        $pushRecord->identity_status = 2;
        $pushRecord->yid = strval($driverId);
        $pushRecord->jpush_id = strval($pushId);
        $pushRecord->source = $source;

        return $pushRecord->save();
    }
    /*
    **
    * 绑定司机推送ID
    * @param $driverId
    * @param $pushId
    * @param string $source
    * @return bool
    */
    public static function getPushRecord ($pushRecord)
    {
        $pushRecord = self::find()
            ->where(['yid' => $pushRecord['yid']])
            ->andWhere(['identity_status' => 4])
            ->limit(1)
            ->one();
        return $pushRecord;
    }



}
