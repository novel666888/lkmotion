<?php

namespace application\modules\dispatch\controllers;

use common\logic\dispatch\FenceLogic;
use common\util\Json;
use common\util\Request;
use common\api\FenceApi;
use common\util\Validate;
use application\controllers\BossBaseController;

class FenceController extends BossBaseController
{
    private $_messages = [
        10001 => '请输入围栏名称',
        10002 => '请选择城市',
        10003 => '请选择有效期开始时间',
        10004 => '请选择有效期结束时间',
        10005 => '围栏已存在',
        10006 => '围栏有效期不正确',
        10007 => '请选择围栏范围',
        10008 => '围栏范围不正确',
        10009 => '数据不存在',
        10010 => '缺少关键参数',
        10011 => '启用状态的围栏不可被删除',
        10000 => '操作失败',
    ];

    /**
     * 获取围栏列表
     */
    public function actionList()
    {
        $request_data = $this->key2lower(Request::input());
        $fence_logic = new FenceLogic();
        if (isset($request_data['page'])) {
            $is_page = 1;
        } else {
            $is_page = 0;
        }
        $lists = $fence_logic->lists($request_data, $is_page);
        if ($lists) {
            $lists['list'] = $this->keyMod($lists['list']);
            return Json::success($lists);
        } elseif (is_string($lists)) {
            return Json::message($lists);
        }
        return Json::message($this->_messages[11000]);
    }

    /**
     * 新增围栏
     */
    public function actionAdd()
    {
        $request_data = $this->key2lower(Request::input());

        $fence_logic = new FenceLogic();

        $result = $fence_logic->add($request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        } elseif (is_string($result)) {
            return Json::message($result);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 编辑围栏
     */
    public function actionEdit()
    {
        $request_data = $this->key2lower(Request::input());
        $fence_logic = new FenceLogic();

        $model = Validate::validateData($request_data, [
            ['id', 'required', 'message' => 10010],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }

        $result = $fence_logic->update($request_data['id'], $request_data);
        if ($result === true) {
            return Json::message('操作成功', 0);
        } elseif (is_string($result)) {
            return Json::message($result);
        }

        return Json::message($this->_messages[$result]);
    }

    /**
     * 删除围栏
     */
    public function actionDelete()
    {
        $id = Request::input('id', '');
        if (empty($id)) {
            return Json::message($this->_messages[10010]);
        }
        $fence_logic = new FenceLogic();

        $result = $fence_logic->delete($id);
        if ($result === true) {
            return Json::success('操作成功', 0);
        } elseif (is_string($result)) {
            return Json::message($result);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 更改围栏状态
     */
    public function actionChangeStatus()
    {
        $request_data = $this->key2lower(Request::input());
        if (empty($request_data['id'])) {
            return Json::message($this->_messages[10010]);
        }
        $fence_logic = new FenceLogic();
        $request = $fence_logic->changeStatus($request_data['id'], $request_data['is_deny']);
        if ($request === true) {
            return Json::success('操作成功', 0);
        } elseif (is_string($result)) {
            return Json::message($result);
        }
        return Json::message($this->_messages[$request]);
    }

    /**
     * 获取围栏详情
     */
    public function actionInfo()
    {
        $id = Request::input('id', '');
        if (empty($id)) {
            return Json::message($this->_messages[10010]);
        }
        $fence_logic = new FenceLogic();
        $result = $fence_logic->info($id);
        if (is_numeric($result)) {
            return Json::message($this->_messages[$result]);
        } elseif (is_string($result)) {
            return Json::message($result);
        }
        $fence_list = $fence_logic->lists(['city_code' => $result['city_code']]);
        if ($fence_list) {
            $result['fence_list'] = $fence_list['list'];
        } else {
            $result['fence_list'] = [];
        }
        return Json::success($this->keyMod($result));
    }
}
