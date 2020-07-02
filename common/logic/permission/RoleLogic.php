<?php

namespace common\logic\permission;

use common\models\SysRole;
use common\models\SysRolePermissionMap;
use common\models\SysPermission;
use common\models\SysDepartment;
use common\services\traits\ModelTrait;
use common\models\SysUserRoleMap;
use common\util\Validate;

/**
 * 角色Logic
 * 
 * ErrorMsg:
 * 10001 => 缺少部门id
 * 10002 => 缺少角色名称
 * 10003 => 角色已存在
 * 10004 => 缺少主键id
 * 10005 => 缺少角色id
 * 10006 => 缺少permission_ids
 */
class RoleLogic
{
    use ModelTrait;
    /**
     * 添加角色.
     */
    public function add($data)
    {
        $role = new SysRole(['scenario' => 'insert']);
        $role->attributes = $data;
        if (!$role->validate()) {
            return $role->getFirstError();
        }
        try {
            $result = $role->save();
            if ($result) {
                return true;
            }
            return 10000;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'RoleLogic/add');
            return 10000;   // 操作失败
        }
    }

    /**
     * 更新角色信息.
     */
    public function update($id, $data)
    {
        if ($id == 1) {
            return 10000;
        }
        $role = new SysRole(['scenario' => 'update']);
        $role->attributes = $data;

        if (!$role->validate()) {
            return $role->getFirstError();
        }

        try {
            $role->setOldAttribute('id', $role->id);
            $role->save();
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'RoleLogic/update');
            return 10000;   // 操作失败
        }
    }

    /**
     * 单个数据的信息.
     */
    public function info($requestData)
    {
        $id = isset($requestData['id']) ? $requestData['id'] : '';
        if ($id == 1) {
            return [];
        }
        $role_name = isset($requestData['role_name']) ? $requestData['role_name'] : '';

        $query = SysRole::find()
            ->where(['is_deleted' => 0]);
        if ($id) {
            $query = $query->andWhere(['id' => $id]);
        }
        if ($role_name) {
            $query = $query->andWhere(['role_name' => $role_name]);
        }
        $info = $query->select(['id', 'department_id', 'role_name'])
            ->asArray()->one();

        return $info;
    }

    /**
     * 获取角色列表.
     */
    public function lists($requestData, $is_page = 1)
    {
        $role_name = isset($requestData['role_name']) ? $requestData['role_name'] : '';
        $department_id = isset($requestData['department_id']) ? $requestData['department_id'] : 0;

        $query = SysRole::find()->alias('r');

        $query = $query->leftJoin(SysDepartment::tableName() . ' d', 'd.id = r.department_id')
            ->select([
                'id' => 'r.id',
                'department_id' => 'r.department_id',
                'department_name' => 'd.department_name',
                'role_name' => 'r.role_name'
            ])->where(['r.is_deleted' => 0]);
        $query = $query->andWhere(['!=', 'r.id', 1]);

        if ($role_name) {
            $query = $query->andWhere(['r.role_name' => $role_name]);
        }
        if ($department_id) {
            $query = $query->andWhere(['r.department_id' => $department_id]);
        }
        if ($is_page) {
            $lists = static::getPagingData($query, ['field' => 'department_id', 'type' => 'desc'], true);
        } else {
            $list = $query->asArray()->orderBy(['department_id' => SORT_DESC])->all();
            $lists['data']['list'] = $list;
        }

        return $lists['data'];
    }

    /**
     * 更新角色权限.
     */
    public function updatePermission($role_id, $permission_ids = [])
    {
        if ($role_id == 1) {
            return 10000;
        }
        $model = Validate::validateData([
            'role_id' => $role_id,
            'permission_ids' => $permission_ids
        ], [
            ['role_id', 'required', 'message' => 10005],
            ['permission_ids', 'required', 'message' => 10006]
        ]);
        if ($model->hasErrors()) {
            return $model->getFirstError();
        }

        $data = [];
        foreach ($permission_ids as $_permission_id) {
            $data[] = [
                'role_id' => $role_id,
                'permission_id' => $_permission_id,
                'is_deleted' => 0
            ];
        }

        $transaction = SysRole::getDb()->beginTransaction();
        try {
            $_sql = SysRolePermissionMap::getDb()->createCommand()
                ->batchInsert(SysRolePermissionMap::tableName(), ['role_id', 'permission_id', 'is_deleted'], $data);

            $_sql = $_sql->getSql() . " ON DUPLICATE KEY UPDATE `is_deleted`=0";
            \Yii::info($_sql, 'RoleLogic/updatePermission update sql');
            $result = SysRolePermissionMap::getDb()->createCommand($_sql)->execute();

        } catch (\Exception $e) {
            $transaction->rollBack();
            \Yii::error($e->getMessage(), 'RoleLogic/updatePermission update sql');
            return 10000;
        }
        $role_permission_map = new SysRolePermissionMap();
        $role_permission_map->updateAll(['is_deleted' => 1], [
            'and', ['not in', 'permission_id', $permission_ids], ['role_id' => $role_id]
        ]);
        try {
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'RoleLogic/updatePermission delete sql');
            $transaction->rollBack();
            return 10000;
        }
    }

    /**
     * 删除角色
     */
    public function delete($id)
    {
        if ($id == 1) {
            return 10000;
        }
        $user = new SysRole();
        try {
            $result = $user->updateAll([
                'is_deleted' => 1,
            ], ['id' => $id]);
            if ($result) {
                return true;
            }

            return 10000;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'RoleLogic/delete');
            return 10000;   // 操作失败
        }
    }

    /**
     * 获取角色拥有的后台权限.
     */
    public function getRolesPermissionList($role_id)
    {
        $query = SysRolePermissionMap::find()
            ->alias('map')->leftJoin(SysPermission::tableName() . ' as p', 'map.permission_id = p.id')
            ->where(['map.is_deleted' => 0, 'map.role_id' => $role_id]);
        $list = $query->select([
            'id' => 'p.id',
            'pid' => 'p.pid',
            'role_id' => 'map.role_id',
            'permission_name' => 'p.permission_name',
            'route' => 'p.route',
            'permissions' => 'p.permissions',
            'level' => 'p.level',
            'is_show' => 'p.is_show'
        ])->orderBy(['p.pid' => SORT_ASC, 'p.sort' => SORT_ASC])->asArray()->all();

        $list = SysPermission::tree($list, 0);
        return $list;
    }

    /**
     * 获取指定角色下的用户数
     */
    public function getRoleUserCount($role_ids)
    {
        $list = SysUserRoleMap::find()
            ->select(['role_id', 'user_count' => 'count(*)'])
            ->where(['is_deleted' => 0, 'role_id' => $role_ids])
            ->groupBy('role_id')->asArray()->all();

        if ($list) {
            return array_column($list, 'user_count', 'role_id');
        }
        return [];
    }
}
