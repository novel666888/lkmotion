<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "tbl_device_blacklist".
 *
 * @property string $id
 * @property string $device_type 1 ios  2 android 3 other
 * @property string $device_code 手机设备串码
 * @property string $last_login_time 最后一次登录时间
 * @property string $is_available 1有效 
 0无效
 * @property string $memo 备注
 * @property string $create_time create
 * @property string $update_time update
 */
class DeviceBlacklist extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_device_blacklist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['last_login_time', 'create_time', 'update_time'], 'safe'],
            [['is_release'], 'integer'],
            [['phones', 'memo'], 'string'],
            [['memo'], 'required'],
            [['device_type'], 'string', 'max' => 1],
            [['device_code'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'device_type' => 'Device Type',
            'device_code' => 'Device Code',
            'last_login_time' => 'Last Login Time',
            'is_release' => 'Is Release',
            'memo' => 'Memo',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 输出设备黑名单列表
     * @return [type] [description]
     */
    public static function getBlacklist($condition, $field=['*']){
        $model = self::find()->select($field);
        //手机号模糊查询
        if(!empty($condition['phone'])){
            $model->andFilterWhere(['like', 'memo', $condition['phone']]);
        }
        $data = self::getPagingData($model, ['type'=>'desc','field'=>'last_login_time'], true);
        return $data['data'];
    }
}
