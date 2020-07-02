<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%driver_invite_posts}}".
 *
 * @property int $id
 * @property int $driver_id 司机ID
 * @property string $post_raw 提交原始信息
 * @property int $effective 是否有效的提交, 0无效,其他:推荐表ID
 * @property string $create_time 录入时间
 */
class DriverInvitePosts extends \common\models\BaseModel
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%driver_invite_posts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['driver_id', 'effective'], 'integer'],
            [['create_time'], 'safe'],
            [['post_raw'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'driver_id' => 'Driver ID',
            'post_raw' => 'Post Raw',
            'effective' => 'Effective',
            'create_time' => 'Create Time',
        ];
    }
}
