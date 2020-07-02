<?php
namespace application\modules\permission\controllers;

use application\controllers\BossBaseController;
use common\logic\permission\RoleLogic;
use common\util\Json;
use yii\helpers\ArrayHelper;
use common\util\Request;
use common\util\Validate;

class RoleController extends BossBaseController
{
    private $_messages = [
        10001 => '请选择部门',
        10002 => '请输入角色名称',
        10003 => '角色已存在',
        10004 => '缺少关键参数',
        10005 => '请选择角色',
        10006 => '请选择权限',
        10000 => '操作失败',
        11000 => '数据为空',
    ];

    /**
     * 添加权限
     *
     * @return void
     */
    public function actionAdd()
    {
        $request_data = $this->key2lower(Request::input());

        $model = Validate::validateData($request_data, [
            ['department_id', 'required', 'message' => 10001],
            ['role_name', 'required', 'message' => 10002],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $role_logic = new RoleLogic();
        $result = $role_logic->add($request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 更新权限
     *
     * @return void
     */
    public function actionUpdate()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10004],
            ['department_id', 'required', 'message' => 10001],
            ['role_name', 'required', 'message' => 10002],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $role_logic = new RoleLogic();

        $result = $role_logic->update($request_data['id'], $request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    public function actionDelete()
    {
        $id = Request::post('id', '');
        if (empty($id)) {
            return Json::message($this->_messages[10004]);
        }
        $role_logic = new RoleLogic();

        $result = $role_logic->delete($id);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 获取角色信息
     *
     * @return void
     */
    public function actionInfo()
    {
        $id = Request::post('id', '');
        if (empty($id)) {
            return Json::message($this->_messages[10004]);
        }
        $role_logic = new RoleLogic();

        $info = $role_logic->info(['id' => $id]);
        if (is_numeric($info)) {
            return Json::message($this->_messages[$info]);
        }
        return Json::success($this->keyMod($info));
    }

    /**
     * 角色列表
     *
     * @return void
     */
    public function actionLists()
    {
        $request_data = $this->key2lower(Request::input());
        $page = (int)\Yii::$app->getRequest()->post('page');
        if ($page) {
            $is_page = 1;
        } else {
            $is_page = 0;
        }
        $role_logic = new RoleLogic();
        $lists = $role_logic->lists($request_data, $is_page);
        if (is_numeric($lists)) {
            return Json::message($this->_messages[$lists]);
        }
        if ($is_page) {
            // 统计角色下的人数
            $role_ids = array_column($lists['list'], 'id');
            $role_user_count_list = $role_logic->getRoleUserCount($role_ids);
            foreach ($lists['list'] as $_k => $_v) {
                if (isset($role_user_count_list[$_v['id']])) {
                    $lists['list'][$_k]['user_count'] = $role_user_count_list[$_v['id']];
                } else {
                    $lists['list'][$_k]['user_count'] = 0;
                }
            }
        }
        $lists['list'] = $this->keyMod($lists['list']);
        return Json::success($lists);
    }

    /**
     * 更新角色拥有的权限
     *
     * @return void
     */
    public function actionUpdateRolePermission()
    {
        $request_data = $this->key2lower(Request::input());

        if (empty($request_data['role_id'])) {
            return Json::message($this->_messages[10005]);
        }
        if (empty($request_data['permission_ids'])) {
            return Json::message($this->_messages[10006]);
        }

        $role_logic = new RoleLogic();

        $result = $role_logic->updatePermission($request_data['role_id'], explode(',', $request_data['permission_ids']));
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 获取角色拥有的权限
     *
     * @return void
     */
    public function actionGetRolePermissionList()
    {
        $role_id = Request::post('roleId', '');
        if (empty($role_id)) {
            return Json::message($this->_messages[10005]);
        }

        $role_logic = new RoleLogic();
        $lists = $role_logic->getRolesPermissionList($role_id);
        if (is_numeric($lists)) {
            return Json::message($this->_messages[$lists]);
        }
        return Json::success($this->keyMod($lists));
    }
}