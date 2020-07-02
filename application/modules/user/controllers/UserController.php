<?php
namespace application\modules\user\controllers;

use application\controllers\BossBaseController;
use common\logic\sysuser\UserLogic;
use common\util\Json;
use common\util\Request;
use common\util\Validate;


/**
 * Default controller for the `user` module
 */
class UserController extends BossBaseController
{
    private $_messages = [
        10000 => '操作失败',
        10001 => '缺少用户名',
        10002 => '缺少用户名',
        10003 => '缺少关键参数',
        10004 => '缺少密码',
        10005 => '缺少手机号',
        10006 => '手机号格式不正确',
        10007 => '两次输入的密码不一致',
        10008 => '权限不存在',
        10009 => '角色必须有一个权限',
        10010 => '用户和密码不正确',
        10011 => '用户被禁用',
        10012 => '用户和密码不正确',
        10013 => '手机号已存在',
        10014 => '用户已存在',
        10015 => '请选择角色',
        10016 => '缺少关键参数',
        10017 => '请选择用户所属城市',
        11000 => '数据为空',
    ];

    /**
     * 添加用户
     *
     * @return void
     */
    public function actionAdd()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['username', 'required', 'message' => 10002],
            ['password', 'required', 'message' => 10004],
            ['city_code', 'required', 'message' => 10017],
            ['phone', 'required', 'message' => 10005],
            ['role_ids', 'required', 'message' => 10015]
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }
        $requests_data['modify_id'] = intval($this->userInfo['id']);
        $user_logic = new UserLogic();
        $result = $user_logic->add($request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }

        return Json::message($this->_messages[$result]);
    }

    /**
     * 更新用户信息
     *
     * @return void
     */
    public function actionUpdate()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['username', 'required', 'message' => 10002],
            ['city_code', 'required', 'message' => 10017],
            ['phone', 'required', 'message' => 10005],
            ['id', 'required', 'message' => 10016],
            ['role_ids', 'required', 'message' => 10015]
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $request_data['modify_id'] = intval($this->userInfo['id']);
        $user_logic = new UserLogic();
        $result = $user_logic->update($request_data['id'], $request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 获取用户信息
     *
     * @return void
     */
    public function actionInfo()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10016]
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $user_logic = new UserLogic();

        $info = $user_logic->info(['id' => $request_data['id']]);

        if (is_numeric($info)) {
            return Json::message($this->_messages[$info]);
        }
        return Json::success($this->keyMod($info));
    }

    /**
     * 获取用户列表
     *
     * @return void
     */
    public function actionLists()
    {
        $request_data = $this->key2lower(Request::input());
        $user_logic = new UserLogic();
        $lists = $user_logic->lists($request_data);
        if (is_numeric($lists)) {
            return Json::message($this->_messages[$lists]);
        }
        $lists['list'] = $this->keyMod($lists['list']);
        return Json::success($lists);
    }

    /**
     * 给用户添加角色
     */
    public function actionAddRole()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['user_id', 'required', 'message' => 10003],
            ['role_id', 'required', 'message' => 10015],
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $user_logic = new UserLogic();

        $result = $user_logic->addRole($request_data['user_id'], $request_data['role_id']);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 删除用户角色
     *
     * @return void
     */
    public function actionDelRole()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['user_id', 'required', 'message' => 10003],
            ['role_id', 'required', 'message' => 10015],
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }
        $user_logic = new UserLogic();

        $result = $user_logic->delRole($request_data['user_id'], $request_data['role_id']);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 修改用户状态
     *
     * @return void
     */
    public function actionUpdateStatus()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['user_id', 'required', 'message' => 10003],
            ['status', 'default', 'value' => 0],
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $user_logic = new UserLogic();

        $result = $user_logic->updateStatus($request_data['user_id'], $request_data['status']);

        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    public function actionDelete()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10016],
        ]);

        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $user_logic = new UserLogic();

        $result = $user_logic->delete($request_data['id']);

        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }
}
