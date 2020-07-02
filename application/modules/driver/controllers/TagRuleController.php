<?php

namespace application\modules\driver\controllers;

use common\logic\ServiceLogic;
use common\logic\TagLogic;
use common\logic\TagRuleLogic;
use common\models\TagRuleInfo;
use common\util\Common;
use common\util\Request;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
/**
 * Default controller for the `charge` module
 */
class TagRuleController extends BossBaseController
{

    private $i18nCategory = TagRuleLogic::I18N_CATEGORY;
    private $mapping = [];

    public function beforeAction($action)
    {
        $this->mapping['status'] = [TagRuleInfo::STATUS_NORMAL => '启用', TagRuleInfo::STATUS_DENY => '禁用'];

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
            $list = TagRuleLogic::lists($params, $pager);
//            ArrayHelper::multisort($list, 'city_code');
            $count = TagRuleLogic::get_total_count($params);

            Common::int_to_string($list, $this->mapping);
            TagLogic::fillTagInfo($list);
            TagRuleLogic::fillUserInfo($list);
            TagRuleLogic::fillBaseData($list, ['city_code', 'service_type_id']);

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

            list($city_code, $service_type_id, $tag_id, $tag_price, $tag_desc, $status) = $this->checkParams(false);

            TagRuleLogic::checkRepeat($city_code, $service_type_id, $tag_id);

            $data['city_code'] = $city_code;
            $data['tag_id'] = $tag_id;
            $data['tag_price'] = $tag_price;
            $data['tag_desc'] = $tag_desc;
            $data['status'] = $status;
            $data['operator_id'] = $this->userInfo['id'];

            foreach ($service_type_id as $v){
                $data['service_type_id'] = $v;
                $res = TagRuleLogic::add($data);

                if (!$res) {
                    throw new Exception(\Yii::t($this->i18nCategory, 'error.operation.fail'), 1);
                }
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

            list($city_code, $service_type_id, $tag_id, $tag_price, $tag_desc, $status, $id) = $this->checkParams(true);

            TagRuleLogic::checkRepeat($city_code, $service_type_id, $tag_id, $id);

            $data['city_code'] = $city_code;
            $data['service_type_id'] = $service_type_id;
            $data['tag_id'] = $tag_id;
            $data['tag_price'] = $tag_price;
            $data['tag_desc'] = $tag_desc;
            $data['status'] = $status;
            $data['operator_id'] = $this->userInfo['id'];

            $res = TagRuleLogic::edit($id, $data);

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
            $filter && $params['status'] = TagRuleInfo::STATUS_NORMAL;
            $list = TagRuleLogic::lists($params, $pager);
            TagLogic::fillTagInfo($list);

            $this->renderJson(array_values($list));
        } catch (Exception $e) {
            $this->renderJson($e);
        }
    }

    /**
     * actionCheckRepeat --
     * @author JerryZhang
     * @cache No
     */
    public function actionGetServiceType(){
        try {
            $city_code = Request::input('city_code');
            $tag_id = Request::input('tag_id');

            $attributes = ['city_code', 'tag_id'];
            $rules = [
                [
                    ['city_code', 'tag_id'],
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'city_code',
                    'string',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],
                [
                    'tag_id',
                    'integer',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params'),
                ],

            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $list = ServiceLogic::getServiceType($city_code);
            $service_type_ids = TagRuleLogic::getServiceTypeIdByCityCodeAndTagId($city_code, $tag_id);
            foreach ($list as &$v){
                if(isset($v)){
                    $v['enable'] = in_array($v['id'], $service_type_ids) ? 0 : 1;
                }
            }

            $this->renderJson($list);
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
        $city_code = Request::input('city_code');
        $service_type_id = Request::input('service_type_id');
        $tag_id = Request::input('tag_id');
        $tag_price = Request::input('tag_price');
        $tag_desc = Request::input('tag_desc');
        $status = Request::input('status');

        $attributes = ['city_code', 'service_type_id', 'tag_id', 'tag_price', 'tag_desc', 'status'];
        $rules = [
            [
                ['city_code', 'service_type_id', 'tag_id', 'tag_price', 'tag_desc', 'status'],
                'required',
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'city_code',
                'string',
                'min' => 1,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'tag_id',
                'integer',
                'min' => 1,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'tag_price',
                'number',
                'min' => 0,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ],
            [
                'tag_desc',
                'string',
                'min' => 1,
                'max' => 200,
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
            array_push($rules, ['id', 'integer', 'min' => 1, 'message' => \Yii::t($this->i18nCategory, 'error.params')]);
            array_push($rules, [
                'service_type_id',
                'integer',
                'min' => 1,
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ]);
        }else{
            array_push($rules, [
                'service_type_id',
                'each',
                'rule' => ['integer', 'min' => 1],
                'message' => \Yii::t($this->i18nCategory, 'error.params'),
            ]);
        }

        $this->verifyParam($attributes, Request::input(), $rules);

        $res = [$city_code, $service_type_id, $tag_id, $tag_price, $tag_desc, $status];
        $check_id && array_push($res, $id);

        return $res;
    }

}
