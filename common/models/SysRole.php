<?php

/**
 * SysRole Model.
 *
 * @category description
 *a
 * @author guolei <guolei@lkmotion.com>
 */

namespace common\models;

/**
 * Undocumented class.
 */
class SysRole extends BaseModel
{
    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    /**
     * Undocumented function.
     */
    public static function tableName()
    {
        return '{{%sys_role}}';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_INSERT => ['department_id', 'role_name'],
            self::SCENARIO_UPDATE => ['department_id', 'role_name', 'id']
        ];
    }

    public function rules()
    {
        return [
            ['department_id', 'required', 'message' => 10001],
            ['role_name', 'required', 'message' => 10002],
            ['id', 'required', 'on' => 'update', 'message' => 10004],
            ['role_name', 'checkUnique', 'params' => ['message' => 10003]],
        ];
    }

    /**
     * 检测数据是否唯一
     */
    public function checkUnique($attribute, $params)
    {
        $query = static::find()->where([
            'department_id' => $this->department_id,
            'role_name' => $this->role_name,
            'is_deleted' => 0
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }
}
