<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_tag_info".
 *
 * @property int $id 自增主键
 * @property string $tag_name 标签名称
 * @property string $create_time 添加时间
 * @property string $update_time 最后修改时间
 */
class TagInfo extends BaseModel
{

    const STATUS_NORMAL = 1;//标签状态-启用
    const STATUS_DENY = 0;//标签状态-禁用

    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tag_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'operator_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['tag_name'], 'string', 'max' => 70],
            [['tag_img'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag_name' => 'Tag Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
