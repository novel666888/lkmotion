<?php

namespace common\logic\sysuser;

use common\models\SysUser;
use common\util\Common;
use common\models\SysUserRoleMap;
use common\services\traits\ModelTrait;
use common\models\SysRolePermissionMap;
use common\models\SysPermission;
use common\models\SysRole;
use common\models\SysDepartment;
use common\logic\permission\PermissionLogic;
use common\models\City;
use common\util\Validate;
use common\logic\permission\RoleLogic;

/**
 * 后台用户Logic
 *
 * ErrorMsg
 * 10001 => 缺少用户名
 * 10002 => 缺少username
 * 10004 => 缺少密码
 * 10005 => 缺少手机号
 * 10006 => 手机号格式不正确
 * 10017 => 缺少城市编码
 * 10008 => 权限不存在
 * 10009 => 用户必须保留一个角色
 * 10010 => 用户不存在
 * 10011 => 用户被禁用
 * 10012 => 用户名和密码不正确
 * 10013 => 手机号已存在
 * 10014 => 用户已存在
 * 10015 => 没有权限登录
 * 10016 => 缺少关键参数
 * 10017 => 原始密码不正确
 */
class UserLogic
{
    use ModelTrait;

    /**
     * 登录方法.
     *
     * @param string $username
     * @param string $password
     */
    public function login($username, $password)
    {
        $model = Validate::validateData([
            'username' => $username,
            'password' => $password
        ], [
            ['username', 'required', 'message' => 10002],
            ['password', 'required', 'message' => 10004],
        ]);
        if ($model->hasErrors()) {
            return $model->getFirstError();
        }
        $user = SysUser::find()->select([
            'id', 'username', 'password', 'salt', 'phone', 'city_code', 'phone', 'status'
        ])->where(['username' => $username, 'is_deleted' => 0])->limit(1)->asArray()->one();

        if (!$user) {
            return 10010;   // 用户不存在
        }
        if ($user['status'] != 1) {
            return 10011;   // 用户被禁用
        }
        $result = SysUser::checkPassword($user['id'], $password);
        if (!$result) {
            return 10012;   // 用户名和密码不正确
        }
        
        $user_model = new SysUser();
        $result = $user_model->updateAll([
            'last_login_time' => date('Y-m-d H:i:s')
        ], ['id' => $user['id']]);

        unset($user['password'], $user['salt']);
        return $user;
    }

    /**
     * 添加新用户.
     */
    public function add($data)
    {
        $user = new SysUser(['scenario' => 'insert']);
        $user->attributes = $data;

        $model = Validate::validateData($data, [
            ['role_ids', 'required', 'message' => 10015]
        ]);
        if ($model->hasErrors()) {
            return $model->getFirstError();
        }

        if (!$user->validate()) {
            return $user->getFirstError();
        }

        $transaction = SysUser::getDb()->beginTransaction();

        try {
            $result = $user->save(false);

            if (!$result) {
                $transaction->rollBack();
                return 10000;
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/add add user sql');
            $transaction->rollBack();
            return 10000;
        }
        if (!is_array($data['role_ids'])) {
            $data['role_ids'] = explode(',', $data['role_ids']);
            $data['role_ids'][] = 1;
        }
        $result = $this->updateRole($user->id, $data['role_ids']);
        if ($result === true) {
            $transaction->commit();
            return true;
        } else {
            return $result;
        }
    }

    /**
     * 更新用户信息.
     *
     * @param int    $id          用户id
     * @param [type] $requestData
     *
     * @return int|bool
     */
    public function update($id, $data)
    {
        $user = new SysUser(['scenario' => 'update']);
        $user->attributes = $data;

        if (!$user->validate()) {
            return $user->getFirstError();
        }
        $transaction = SysUser::getDb()->beginTransaction();

        try {
            $user->setOldAttribute('id', $id);
            $user->save(false);
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/update update user sql');
            $transaction->rollBack();
            return 10000;   // 操作失败
        }

        $result = $this->updateRole($id, $data['role_ids']);
        if ($result === true) {
            $transaction->commit();
            return true;
        } else {
            return $result;
        }
    }

    /**
     * 更改指定用户密码
     *
     * @param [type] $id
     * @param [type] $old_password
     * @param [type] $password
     * @return void
     */
    public function updatePwd($id, $old_password, $password)
    {
        $user = SysUser::find()->select([
            'id', 'password', 'salt'
        ])->where(['id' => $id, 'is_deleted' => 0])->limit(1)->one();
        if (!$user) {
            return 10010;   // 用户不存在
        }

        if (SysUser::makePasswd($old_password, $user->salt) != $user->password) {
            return 10017;   // 原始密码不正确
        }
        $salt = SysUser::makeSalt();

        try {
            $result = $user->updateAll([
                'salt' => $salt,
                'password' => SysUser::makePasswd($password, $salt),
                'last_update_password_time' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            if ($result) {
                return true;
            }
            return 10000;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/updatePwd sql');
            return 10000;
        }
    }

    public function updateStatus($id, $status)
    {
        $user = new SysUser();
        try {
            $result = $user->updateAll([
                'status' => $status,
            ], ['id' => $id, 'is_deleted' => 0]);
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/updateStatus sql');
            return 10000;   // 操作失败
        }
    }

    public function delete($id)
    {
        $user = new SysUser();
        $transaction = SysUser::getDb()->beginTransaction();
        try {
            $result = $user->updateAll([
                'is_deleted' => 1,
            ], ['id' => $id]);
            if (!$result) {
                $transaction->rollBack();
                return 10000;
            }
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/delete sql');
            $transaction->rollBack();
            return 10000;   // 操作失败
        }

        try {
            $result = $this->delUserAllRole($id);
            if ($result !== true) {
                $transaction->rollBack();
                return $result;
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            return 10000;   // 操作失败
        }
    }

    /**
     * 获取单个用户的信息.
     */
    public function info($requestData)
    {
        $id = isset($requestData['id']) ? $requestData['id'] : 0;
        $username = isset($requestData['username']) ? $requestData['username'] : '';
        $phone = isset($requestData['phone']) ? $requestData['phone'] : '';

        $query = SysUser::find();
        $query = $query->where(['is_deleted' => 0]);
        if ($id) {
            $query = $query->andWhere(['id' => $id]);
        }
        if ($username) {
            $query = $query->andWhere(['username' => $username]);
        }

        if ($phone) {
            $query = $query->andWhere(['phone' => $phone]);
        }
        $info = $query->select([
            'id', 'username', 'phone', 'city_code', 'status',
        ])->asArray()->one();

        if (!$info) {
            return [];
        }
        $info['phone'] = SysUser::decryptPhoneNumber($info['phone']);
    
        // 查询城市名称
        $city_info = City::find()->where(['city_code' => $info['city_code']])
            ->select(['city_name'])->asArray()->one();
        if ($city_info) {
            $info['city_name'] = $city_info['city_name'];
        }

        return $info;
    }

    /**
     * 获取后台用户列表.
     */
    public function lists($requestData)
    {
        $username = isset($requestData['username']) ? $requestData['username'] : '';
        $phone = isset($requestData['phone']) ? $requestData['phone'] : '';
        $status = isset($requestData['status']) ? $requestData['status'] : '';
        $city_code = isset($requestData['city_code']) ? $requestData['city_code'] : '';

        $query = SysUser::find();
        $query = $query->where(['is_deleted' => 0]);
        if ($username) {
            $query = $query->where(['username' => $username]);
        }
        if ($phone) {
            $query = $query->andWhere(['phone' => SysUser::makePhone($phone)]);
        }
        if ($status === '') {
            $query = $query->andWhere(['in', 'status', [0, 1]]);
        } elseif (in_array($status, [0, 1])) {
            $query = $query->andWhere(['status' => $status]);
        }
        if ($city_code) {
            $query = $query->andWhere(['city_code' => $city_code]);
        }

        $query = $query->select([
            'id', 'username', 'phone', 'city_code', 'status', 'last_login_time'
        ]);

        $lists = static::getPagingData($query, ['field' => 'id', 'type' => 'desc'], true);

        if ($lists['data']['list']) {
            // 解密手机号
            foreach ($lists['data']['list'] as $_k => $_v) {
                if ($_v['phone']) {
                    $lists['data']['list'][$_k]['phone'] = Common::getPhoneNumberByEncrypt([['encrypt' => $_v['phone']]])[0]['phone'];
                }
            }
            $user_ids = array_column($lists['data']['list'], 'id');
            $role_query = SysUserRoleMap::find();
            $role_query = $role_query->alias('rmap')
                ->leftJoin(SysRole::tableName() . ' r', 'rmap.role_id = r.id')
                ->leftJoin(SysDepartment::tableName() . ' d', 'r.department_id = d.id')
                ->where(['rmap.user_id' => $user_ids, 'rmap.is_deleted' => 0, 'r.is_deleted' => 0]);
            $role_query = $role_query->andWhere(['!=', 'r.id', 1]);
            $role_list = $role_query->select([
                'role_id' => 'rmap.role_id',
                'user_id' => 'rmap.user_id',
                'role_name' => 'r.role_name',
                'department_id' => 'r.department_id',
                'department_name' => 'd.department_name',
            ])->asArray()->all();
            if ($role_list) {
                $lists['data']['list'] = array_column($lists['data']['list'], null, 'id');

                foreach ($role_list as $_k => $_v) {
                    $lists['data']['list'][$_v['user_id']]['role_list'][] = $_v;
                }

                $lists['data']['list'] = array_values($lists['data']['list']);
            }

            // 城市名称
            $city_codes = array_unique(array_filter(array_column($lists['data']['list'], 'city_code')));
            if ($city_codes) {
                $city_lists = City::find()->where(['city_code' => $city_codes])->select(['city_code', 'city_name'])->all();
                if ($city_lists) {
                    $city_lists = array_column($city_lists, null, 'city_code');
                    foreach ($lists['data']['list'] as $_k => $_v) {
                        if (isset($city_lists[$_v['city_code']])) {
                            $lists['data']['list'][$_k]['city_name'] = $city_lists[$_v['city_code']]['city_name'];
                        }
                    }
                }
            }
        }

        return $lists['data'];
    }

    /**
     * 给用户添加角色.
     */
    public function updateRole($user_id, $role_ids)
    {
        if (!is_array($role_ids)) {
            $role_ids = explode(',', $role_ids);
        }
        $role_data = [];
        foreach ($role_ids as $_k => $_v) {
            $role_data[] = [
                'user_id' => $user_id,
                'role_id' => $_v,
                'is_deleted' => 0,
            ];
        }
        $transaction = SysUser::getDb()->beginTransaction();
        try {
            $_sql = SysUserRoleMap::getDb()->createCommand()
                ->batchInsert(SysUserRoleMap::tableName(), ['user_id', 'role_id', 'is_deleted'], $role_data);

            $_sql = $_sql->getSql() . " ON DUPLICATE KEY UPDATE `is_deleted`=0";

            $result = SysUserRoleMap::getDb()->createCommand($_sql)->execute();
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/update update role sql');
            $transaction->rollBack();
            return 10000;
        }
        $user_role_map = new SysUserRoleMap();
        $user_role_map->updateAll(['is_deleted' => 1], [
            'and', ['not in', 'role_id', $role_ids], ['user_id' => $user_id]
        ]);
        try {
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/update del role sql');
            $transaction->rollBack();
            return 10000;
        }
    }

    /**
     * 删除用户所有角色
     *
     * @return void
     */
    public function delUserAllRole($user_id)
    {
        $user_role_map = new SysUserRoleMap();
        try {
            $user_role_map->updateAll(['is_deleted' => 1], [
                'user_id' => $user_id
            ]);
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/delUserAllRole del role sql');
            return 10000;
        }
    }

    /**
     * 去掉用户拥有的权限.
     */
    public function delRole($user_id, $role_id)
    {
        $_info = SysUserRoleMap::find()
            ->select(['id'])
            ->where(['user_id' => $user_id, 'role_id' => $role_id, 'is_deleted' => 0])
            ->asArray()->one();

        if (!$_info) {
            return 10008;   // 权限不存在
        }
        $_count = SysUserRoleMap::find()
            ->where(['user_id' => $user_id, 'is_deleted' => 0])->count();

        if ($_count == 1) {
            return 10009;   // 角色必须有一个权限
        }

        $model = new SysUserRoleMap();

        try {
            $model->updateAll([
                'is_deleted' => 1,
            ], ['id' => $_info['id']]);

            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'UserLogic/delRole sql');
            return 10000;   // 操作失败
        }
    }

    /**
     * 获取指定用户拥有的角色
     *
     * @param [type] $user_id
     * @return void
     */
    public function getRoleListByUserId($user_id)
    {
        $query = SysUserRoleMap::find()->alias('rmap')
            ->leftJoin(SysRole::tableName() . ' r', 'rmap.role_id = r.id')
            ->leftJoin(SysDepartment::tableName() . ' d', 'r.department_id = d.id')
            ->where(['rmap.user_id' => $user_id, 'r.is_deleted' => 0, 'rmap.is_deleted' => 0]);
        $query = $query->andWhere(['!=', 'r.id', 1]);
        $role_list = $query->select([
            'id' => 'r.id',
            'role_name' => 'r.role_name',
            'department_id' => 'r.department_id',
            'department_name' => 'd.department_name',
        ])->asArray()->all();
        return $role_list;
    }

    /**
     * 获取用户拥有的权限列表.
     */
    public function getUserPermissionList($user_id, $is_tree = 1)
    {
        if (!in_array($user_id, [1])) {
            $list = SysRolePermissionMap::find()
                ->alias('pmap')
                ->leftJoin(SysRole::tableName() . ' as r', 'pmap.role_id = r.id')
                ->leftJoin(SysPermission::tableName() . ' as p', 'pmap.permission_id = p.id')
                ->leftJoin(SysUserRoleMap::tableName() . 'as rmap', 'rmap.role_id = r.id')
                ->select([
                    'id' => 'p.id',
                    'pid' => 'p.pid',
                    'role_id' => 'pmap.role_id',
                    'permission_name' => 'p.permission_name',
                    'route' => 'p.route',
                    'permissions' => 'p.permissions',
                    'level' => 'p.level',
                    'is_show' => 'p.is_show'
                ])->where(['rmap.user_id' => $user_id, 'r.is_deleted' => 0, 'rmap.is_deleted' => 0, 'pmap.is_deleted' => 0])
                ->orderBy(['p.pid' => SORT_ASC, 'p.sort' => SORT_ASC])->asArray()->all();
        } else {
            $permission_logic = new PermissionLogic();
            $list = $permission_logic->lists();
        }

        if ($is_tree == 1) {
            $list = SysPermission::tree($list, 0);
        }

        return $list;
    }
}
