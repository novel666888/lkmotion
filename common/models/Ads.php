<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
/**
 * This is the model class for table "{{%ads}}".
 *
 * @property int $id
 * @property string $down_load_url
 * @property string $link_url
 * @property int $position_id 广告位id
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property string $start_time 开始时间
 * @property string $end_time 结束时间
 * @property string $city 展示城市
 * @property string $name 广告名称
 * @property string $platform 平台
 * @property string $video_img 视频过度图
 * @property int $people_tag_id 人群模板id
 * @property int $type 广告类型（1：大屏；2：司机端；3：乘客端）
 * @property int $status 1启用0停用
 * @property int $operator_user 操作人
 */
class Ads extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ads}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['position_id', 'type', 'status','people_tag_id','operator_user'], 'integer'],
            [['create_time', 'update_time', 'start_time', 'end_time'], 'safe'],
            [['start_time', 'end_time', 'type'], 'required'],
            [['down_load_url', 'link_url','video_img'], 'string', 'max' => 255],
            [['city', 'name', 'platform'], 'string', 'max' => 64],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'down_load_url' => 'Down Load Url',
            'link_url' => 'Link Url',
            'video_img' => 'Video Img',
            'position_id' => 'Position ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'city' => 'City',
            'name' => 'Name',
            'platform' => 'Platform',
            'type' => 'Type',
            'people_tag_id' => 'People Tag ID',
            'status' => 'Status',
            'operator_user' => 'Operator User',
        ];
    }
}
