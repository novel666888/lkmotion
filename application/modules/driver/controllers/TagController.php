<?php

namespace application\modules\driver\controllers;

use common\logic\TagLogic;
use common\models\TagInfo;
use common\models\TagRuleInfo;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class TagController extends BossBaseController
{

    private $i18nCategory = TagLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
        $this->mapping['status'] = [TagInfo::STATUS_NORMAL => '启用', TagInfo::STATUS_DENY => '禁用'];

        return parent::beforeAction($action);
    }

    /**
     * actionList --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionList()
    {
        try {
            $page = Request::input('page', 1);
            $page_size = Request::input('pageSize', 10);

            $attributes = ['page', 'pageSize'];
            $rules = [
                [
                    'page',
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.page.invalid'),
                    'tooSmall' => \Yii::t($this->i18nCategory, 'error.page.small', 1),
                ],
                [
                    'pageSize',
                    'integer',
                    'min' => 7,
                    'max' => 1000,
                    'message' => \Yii::t($this->i18nCategory, 'error.page_size.invalid'),
                    'tooSmall' => \Yii::t($this->i18nCategory, 'error.page_size.small', 10),
                    'tooBig' => \Yii::t($this->i18nCategory, 'error.page_size.big', 1000),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params = [];
            $pager = ['page' => $page, 'page_size' => $page_size];
            $list = TagLogic::lists($params, $pager);
            array_walk($list, function(&$item){
                $item['oss_uri'] = \Yii::$app->params['ossFileUrl'];
            });
            $count = TagLogic::get_total_count($params);

            Common::int_to_string($list, $this->mapping);
            TagLogic::fillUserInfo($list);

            $data['list'] = array_values($list);
            $data['pageInfo'] = [
                'page' => $page,
                'pageCount' => ceil($count / $page_size),
                'pageSize' => $page_size,
                'total' => $count
            ];

            $this->renderJson($data);
        } catch (Exception $e) {
            $this->renderJson($e);
        }

    }

    /**
     * actionAdd --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionAdd()
    {
        try {

            list($tag_name, $tag_img, $status) = $this->checkParams(false);

            $data['tag_name'] = $tag_name;
            $data['tag_img'] = $tag_img;
            $data['status'] = $status;
            $data['operator_id'] = $this->userInfo['id'];

            $res = TagLogic::add($data);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionEdit --
     * @author JerryZhang
     * @cache Yes
     */
    public function actionEdit()
    {
        try {

            list($tag_name, $tag_img, $status, $id) = $this->checkParams(true);

            $data['tag_name'] = $tag_name;
            $data['tag_img'] = $tag_img;
            $data['status'] = $status;
            $data['operator_id'] = $this->userInfo['id'];

            $res = TagLogic::safeEdit($id, $data);

            if (!$res) {
                throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
            }

            $this->renderJson([]);
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionGet --
     * @author JerryZhang
     * @cache No
     */
    public function actionGet()
    {
        try {
            $filter = Request::input('filter', 0);

            $attributes = ['filter'];
            $rules = [
                [
                    'filter',
                    'in',
                    'range' => [0,1],
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params = [];
            $pager = [];
            $filter && $params['status'] = TagInfo::STATUS_NORMAL;
            $list = TagLogic::lists($params, $pager);

            $this->renderJson(array_values($list));
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * checkParams --
     * @author JerryZhang
     * @param bool $check_id
     * @return array
     * @cache No
     */
    private function checkParams($check_id = true)
    {

        $id = Request::input('id');
        $tag_name = Request::input('tag_name');
        $tag_img = Request::input('tag_img');
        $status = Request::input('status');

        $attributes = ['tag_name', 'tag_img', 'status'];
        $rules = [
            [
                ['tag_name', 'tag_img', 'status'],
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'tag_name',
                'string',
                'min' => 1,
                'max' => 50,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'tag_img',
                'string',
                'min' => 1,
                'max' => 255,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'status',
                'in',
                'range' => [TagRuleInfo::STATUS_NORMAL, TagRuleInfo::STATUS_DENY],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],

        ];

        if ($check_id) {
            array_push($attributes, 'id');
            array_push($rules, ['id', 'required', 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
            array_push($rules,
                ['id', 'integer', 'min' => 1, 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
        }

        $this->verifyParam($attributes, Request::input(), $rules);

        $res = [$tag_name, $tag_img, $status];
        $check_id && array_push($res, $id);

        return $res;
    }

}
