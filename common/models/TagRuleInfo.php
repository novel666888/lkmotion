<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_tag_rule_info".
 *
 * @property int $id 自增主键
 * @property string $city_code 城市编码
 * @property int $service_type_id 服务类型id
 * @property string $tag_name 标签名称
 * @property string $tag_price 标签费用
 * @property string $tag_desc 标签说明
 * @property int $status 标签状态 0禁用1启用
 * @property string $create_time 创建时间
 * @property string $update_time 最后修改时间
 */
class TagRuleInfo extends BaseModel
{

    const STATUS_NORMAL = 1;//标签规则状态-启用
    const STATUS_DENY = 0;//标签规则状态-禁用

    use ModelTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_tag_rule_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tag_id', 'service_type_id', 'status', 'operator_id'], 'integer'],
            [['tag_price'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['city_code'], 'string', 'max' => 32],
            [['tag_desc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'city_code' => 'City Code',
            'service_type_id' => 'Service Type ID',
            'tag_name' => 'Tag Name',
            'tag_price' => 'Tag Price',
            'tag_desc' => 'Tag Desc',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * checkData --
     * @author JerryZhang
     * @param $city_code
     * @param $service_type_id
     * @param $tag_id
     * @param $id
     * @return int|string
     * @cache No
     */
    public static function checkData($city_code, $service_type_id, $tag_id, $id)
    {
        $query = self::find();
        $query->select('id');
        $query->andWhere(['city_code' => $city_code, 'service_type_id' => $service_type_id, 'tag_id' => $tag_id]);
        if ($id) {
            $query->andWhere(['<>', 'id', $id]);
        }

        return $query->count();
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = self::find();
        if (isset($params['is_delete'])) {
            $query->andWhere(['is_delete' => $params['is_delete']]);
        }
        if (isset($params['status'])) {
            $query->andWhere(['status' => $params['status']]);
        }
        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        if (!empty($params['service_type_id'])) {
            $query->andWhere(['service_type_id' => $params['service_type_id']]);
        }
        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }

        $query->orderBy('`city_code` + 0 ASC, `id` DESC');

        return $query;
    }
}
