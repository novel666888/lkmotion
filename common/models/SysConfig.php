<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%sys_config}}".
 *
 * @property int $id
 * @property string $conf_key 配置项
 * @property string $conf_val 配置值
 * @property string $conf_name 配置名称
 */
class SysConfig extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%sys_config}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['conf_key', 'conf_val', 'conf_name'], 'required'],
            [['conf_key', 'conf_name'], 'string', 'max' => 30],
            [['conf_val'], 'string', 'max' => 2000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'conf_key' => 'Conf Key',
            'conf_val' => 'Conf Val',
            'conf_name' => 'Conf Name',
        ];
    }
}
