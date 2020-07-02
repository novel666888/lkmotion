<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "admin_menu".
 *
 * @property int $id
 * @property string $name 名称
 * @property int $pid 上级分类ID
 * @property int $sort 排序（同级有效）
 * @property string $url 链接地址
 * @property int $hide 是否隐藏
 * @property int $status 状态
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class AdminMenu extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_menu';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['pid', 'sort', 'hide', 'status', 'created_at', 'updated_at'], 'integer'],
            [['name'], 'string', 'max' => 64],
            [['url'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'pid' => 'Pid',
            'sort' => 'Sort',
            'url' => 'Url',
            'hide' => 'Hide',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
