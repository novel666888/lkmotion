<?php

namespace common\logic\permission;

use common\models\SysPermission;

/**
 * 权限Logic
 * 
 * ErrorMsg
 * 
 * 10001 => 缺少权限名称
 * 10003 => 权限已存在
 * 10004 => 缺少id
 */
class PermissionLogic
{
    /**
     * 新增权限节点.
     *
     * @param [type] $data
     */
    public function add($data)
    {
        $permission = new SysPermission(['scenario' => 'insert']);
        $permission->attributes = $data;

        if (!$permission->validate()) {
            return $permission->getFirstError();
        }

        try {
            $result = $permission->save();
            if ($result) {
                return true;
            }
            return 10000;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'PermissionLogic/add');
            return 10000;   // 操作失败
        }
    }

    /**
     * 更新权限节点信息
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public function update($id, $data)
    {
        $permission = new SysPermission(['scenario' => 'update']);
        $permission->attributes = $data;

        if (!$permission->validate()) {
            return $permission->getFirstError();
        }

        try {
            $permission->setOldAttribute('id', $permission->id);
            $permission->save();
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'PermissionLogic/update');
            return 10000;   // 操作失败
        }
    }

    /**
     * 获取单个权限信息.
     */
    public function info($requestData)
    {
        $id = isset($requestData['id']) ? $requestData['id'] : 0;

        if (empty($id)) {
            return 10004;
        }

        $query = SysPermission::find();
        $query = $query->where(['id' => $id]);
        $info = $query->select([
            'id', 'pid', 'permission_name', 'route', 'permissions', 'level', 'is_show'
        ])->asArray()->one();

        return $info;
    }

    /**
     * 获取权限列表.
     */
    public function lists($requestData = [])
    {
        $query = SysPermission::find();
        $pid = isset($requestData['pid']) ? (int)$requestData['pid'] : '';
        if ($pid !== '') {
            $query = $query->andWhere(['pid' => $requestData['pid']]);
        }

        $lists = $query->select(['id', 'pid', 'permission_name', 'route', 'permissions', 'level', 'sort', 'is_show'])
            ->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
        
        $pid = $pid ? $pid : 0;
        $lists = SysPermission::tree($lists, $pid);
        return $lists;
    }
}
