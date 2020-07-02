<?php

/**
 * 权限节点 Model.
 *
 * @category description
 *
 * @author guolei <guolei@lkmotion.com>
 */

namespace common\models;

/**
 * Undocumented class.
 */
class SysPermission extends BaseModel
{
    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';
    /**
     * Undocumented function.
     */
    public static function tableName()
    {
        return '{{%sys_permission}}';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_INSERT => ['pid', 'permission_name', 'route', 'permissions', 'level', 'sort', 'is_show'],
            self::SCENARIO_UPDATE => ['pid', 'permission_name', 'route', 'permissions', 'level', 'sort', 'is_show', 'id'],

        ];
    }

    public function rules()
    {
        return [
            ['pid', 'default', 'value' => 0],
            ['is_show', 'default', 'value' => 1],
            ['permission_name', 'required', 'message' => 10001],
            ['id', 'required', 'on' => 'update', 'message' => 10004],
            ['permission_name', 'checkUnique', 'params' => ['message' => 10003]],
            ['route', 'checkUniqueRoute', 'params' => ['message' => 10003]]
        ];
    }

    public function afterValidate()
    {
        if ($this->permissions) {
            $this->permissions = self::formatPermissions($this->permissions);
        }
    }

    public function checkUnique($attribute, $params)
    {
        $query = static::find()->where([
            'pid' => $this->pid,
            'permission_name' => $this->permission_name,
            'level' => $this->level,
            'route' => $this->route,
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }

    public function checkUniqueRoute($attribute, $params)
    {
        $query = static::find()->where([
            'route' => $this->route,
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }

    public static function formatPermissions($permissions)
    {
        return implode(',', array_filter(array_map(function ($_v) {
            return trim(trim($_v, '/'));
        }, explode(',', $permissions))));
    }

    public static function tree($list, $pid = 0)
    {
        $data = [];
        foreach ($list as $_k => $_v) {
            if ($_v['pid'] == $pid) {
                $data[$_v['id']] = $_v;
                $_child = self::tree($list, $_v['id']);
                if ($_child) {
                    $data[$_v['id']]['child'] = array_values($_child);
                }
            }
        }
        return array_values($data);
    }
}
