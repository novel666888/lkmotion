<?php
namespace application\modules\permission\controllers;

use application\controllers\BossBaseController;
use common\logic\permission\PermissionLogic;
use common\util\Json;
use common\util\Request;
use common\util\Validate;

class PermissionController extends BossBaseController
{
    private $_messages = [
        10001 => '缺少权限名称',
        10003 => '权限名称已存在',
        10000 => '操作失败',
        10004 => '缺少关键参数',
        11000 => '数据为空',
    ];

    /**
     * 添加权限
     */
    public function actionAdd()
    {
        $request_data = $this->key2lower(Request::input());
        $permission = new PermissionLogic();

        $result = $permission->add($request_data);

        if ($result === true) {
            return Json::message('操作成功', 0);
        }

        return Json::message($this->_messages[$result]);
    }

    /**
     * 更新权限信息
     *
     * @return void
     */
    public function actionUpdate()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10004],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages($model->getFirstError()));
        }

        $permission_logic = new PermissionLogic();

        $result = $permission_logic->update($request_data['id'], $request_data);

        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 获取单个权限信息
     *
     * @return void
     */
    public function actionInfo()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10004],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $permission_logic = new PermissionLogic();
        $info = $permission_logic->info(['id' => $request_data['id']]);

        if (is_numeric($info)) {
            return Json::message($this->_messages[$info]);
        }
        return Json::success($this->keyMod($info));
    }

    /**
     * 获取权限列表
     *
     * @return void
     */
    public function actionLists()
    {
        $request_data = $this->key2lower(Request::input());

        $permission_logic = new PermissionLogic();
        $lists = $permission_logic->lists($request_data);
        if (is_numeric($lists)) {
            return Json::message($this->_messages[$lists]);
        }
        return Json::success($this->keyMod($lists));
    }
}