<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "admin_role".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property int $is_admin
 * @property string $permission
 * @property string $desc
 * @property int $created_at
 * @property int $updated_at
 */
class AdminRole extends BaseModel
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'admin_role';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'is_admin', 'created_at', 'updated_at'], 'integer'],
            [['permission'], 'string'],
            [['name', 'desc'], 'string', 'max' => 255],
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
            'status' => 'Status',
            'is_admin' => 'Is Admin',
            'permission' => 'Permission',
            'desc' => 'Desc',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
