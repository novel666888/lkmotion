<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

/**
 * This is the model class for table "tbl_fence_info".
 *
 * @property int $id
 * @property string $gid 高德ID
 * @property string $city_code 城市编码
 * @property string $fence_name 围栏名称
 * @property string $valid_start_time 生效开始时间
 * @property string $valid_end_time 生效结束时间
 * @property int $is_deny 是否禁用 0未禁用 1已禁用
 * @property int $ is_delete 是否删除 0未删除 1已删除
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class FenceInfo extends BaseModel
{
    use ModelTrait;

    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tbl_fence_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['fence_name', 'required', 'message' => 10001],
            ['city_code', 'required', 'message' => 10002],
            ['valid_start_time', 'required', 'message' => 10003],
            ['valid_end_time', 'required', 'message' => 10004],
            ['fence_name', 'checkUnique', 'params' => ['message' => 10005]]
        ];
    }

    public function checkUnique($attribute, $params)
    {
        $query = static::find()->where([
            'fence_name' => $this->fence_name,
            'is_detede' => 0
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_INSERT => ['gid', 'fence_name', 'city_code', 'valid_start_time', 'valid_end_time', 'is_deny'],
            self::SCENARIO_UPDATE => ['gid', 'fence_name', 'city_code', 'valid_start_time', 'valid_end_time', 'is_deny', 'id']
        ];
    }

    public function afterValidate()
    {
        if ($this->getScenario() == self::SCENARIO_INSERT) {
            $this->is_delete = 0;
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'gid' => 'Gid',
            'city_code' => 'City Code',
            'fence_name' => 'Fence Name',
            'valid_start_time' => 'Valid Start Time',
            'valid_end_time' => 'Valid End Time',
            'is_deny' => 'Is Deny',
            'is_delete' => 'Is Delete',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
