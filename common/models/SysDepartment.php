<?php

/**
 * 部门Model.
 *
 * @category description
 *
 * @author guolei <guolei@lkmotion.com>
 */

namespace common\models;

/**
 * 部门Model类.
 */
class SysDepartment extends BaseModel
{
    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    /**
     * Undocumented function.
     */
    public static function tableName()
    {
        return '{{%sys_department}}';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_INSERT => ['department_name'],
            self::SCENARIO_UPDATE => ['department_name', 'id']
        ];
    }

    public function rules()
    {
        return [
            ['department_name', 'required', 'message' => 10001],
            ['id', 'required', 'on' => 'update', 'message' => 10003],['department_name', 'checkUnique', 'params' => ['message' => 10002]
        ]];
    }

    public function checkUnique($attribute, $params)
    {
        $query = static::find()->where([
            'department_name' => $this->department_name
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
