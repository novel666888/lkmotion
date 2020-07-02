<?php

namespace application\modules\permission\controllers;

use application\controllers\BossBaseController;
use common\util\Json;
use common\logic\permission\DepartmentLogic;
use common\util\Request;
use common\util\Validate;

class DepartmentController extends BossBaseController
{
    private $_messages = [
        10000 => '操作失败',
        10001 => '请输入部门名称',
        10002 => '部门已存在',
        10003 => '缺少关键参数',
        11000 => '数据为空',
    ];

    /**
     * 新增部门接口
     *
     * @return void
     */
    public function actionAdd()
    {
        $request_data = $this->key2lower(Request::input());

        $model = Validate::validateData($request_data, [
            ['department_name', 'required', 'message' => 10001],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }
        $department_logic = new DepartmentLogic();
        $result = $department_logic->add([
            'department_name' => $request_data['department_name'],
        ]);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 更新部门信息接口
     *
     * @return void
     */
    public function actionUpdate()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10003],
            ['department_name', 'required', 'message' => 10001]
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $department_logic = new DepartmentLogic();

        $result = $department_logic->update($request_data['id'], $request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 获取单个部门的信息
     *
     * @return void
     */
    public function actionInfo()
    {
        $request_data = $this->key2lower(Request::input());
        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10003],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $department_logic = new DepartmentLogic();
        $info = $department_logic->info(['id' => $request_data['id']]);

        if (is_numeric($info)) {
            return Json::message($this->_messages[$info]);
        }
        return Json::success($this->keyMod($info));
    }

    /**
     * 获取部门列表信息
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
        $department_logic = new DepartmentLogic();
        $lists = $department_logic->lists($request_data, $is_page);
        if (is_numeric($lists)) {
            return Json::message($this->_messages[$lists]);
        }
        $lists['list'] = $this->keyMod($lists['list']);
        return Json::success($lists);
    }
}
