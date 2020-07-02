<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%ad_position}}".
 *
 * @property int $id 广告位id
 * @property int $position_id 广告位置
 * @property string $position_name 广告位名称
 * @property string $position_desc 广告位描述
 * @property int $position_type 广告位类型（1：乘客端；2：司机端；3：大屏端；）
 * @property int $status 状态（0：暂停；1：开启）
 * @property int $most_count 广告上限
 * @property int $content_type 内容类型（1：视频；2：图片）
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property int $operator_user 创建人
 */
class AdPosition extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ad_position}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_name', 'position_type'], 'required'],
            [['position_desc'], 'string'],
            [['position_type', 'status','content_type','operator_user','most_count','position_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['position_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'position_id' => 'Position ID',
            'position_name' => 'Position Name',
            'position_desc' => 'Position Desc',
            'position_type' => 'Position Type',
            'status' => 'Status',
            'most_count' => 'Most Count',
            'content_type' => 'Content Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'operator_user' => 'Operator User',
        ];
    }

}
