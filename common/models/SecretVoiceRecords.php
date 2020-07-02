<?php

namespace common\models;

use Faker\Provider\Base;
use Yii;

/**
 * This is the model class for table "{{%secret_voice_records}}".
 *
 * @property int $id
 * @property string $call_id
 * @property string $subs_id 对应的三元组的绑定关系ID
 * @property string $call_time 呼叫时间
 * @property int $flag 是否下载文件0：未下载  1：已下载
 * @property string $oss_download_url 文件下载地址
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class SecretVoiceRecords extends BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%secret_voice_records}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['call_id', 'subs_id', 'call_time'], 'required'],
            [['call_time', 'create_time', 'update_time'], 'safe'],
            [['flag'], 'integer'],
            [['call_id', 'subs_id'], 'string', 'max' => 64],
            [['oss_download_url'], 'string', 'max' => 512],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'call_id' => 'Call ID',
            'subs_id' => 'Subs ID',
            'call_time' => 'Call Time',
            'flag' => 'Flag',
            'oss_download_url' => 'Oss Download Url',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
