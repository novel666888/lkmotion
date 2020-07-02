<?php

namespace application\modules\auth\controllers;

use common\models\Decrypt;
use common\models\SmsCode;
use common\models\SysUser;
use common\services\CConstant;
use common\util\Common;
use common\util\Json;
use common\util\Request;
use yii\base\Exception;
use yii\base\UserException;
use common\logic\sysuser\UserLogic;
use common\util\Validate;
use application\controllers\BossBaseController;

class AuthController extends BossBaseController
{
    private $_messages = [
        10002 => '请输入账号',
        10004 => '请输入密码',
        10005 => '请输入短信验证码',
        10010 => '账号密码不正确',
        10011 => '用户被禁用',
        10012 => '账号密码不正确',
        10016 => '请先登录',
        10017 => '原始密码不正确',
        10018 => '请输入原密码',
    ];


    private $i18nCategory = 'boss_auth';

    /**
     * 登录
     *
     * @return array
     */
    public function actionLogin()
    {
        try {

            $request_data = $this->key2lower(Request::input());

            $model = Validate::validateData($request_data, [
                ['account', 'required', 'message' => 10002],
                ['password', 'required', 'message' => 10004],
                ['sms_code', 'required', 'message' => 10005]
            ]);

            if ($model->hasErrors()) {
                return Json::message($this->_messages[$model->getFirstError()]);
            }

            $this->checkSmsCode($request_data['sms_code'], $request_data['account']);

            $user_logic = new UserLogic();
            $result = $user_logic->login($request_data['account'], $request_data['password']);
            if (is_numeric($result)) {
                return Json::message($this->_messages[$result]);
            }

            $params['username'] = $request_data['account'];
            $params['is_deleted'] = CConstant::DEL_NO;
            $params['status'] = 1;
            $info = SysUser::lists($params);
            $info = array_shift($info);

            $res_check = $this->checkPasswordExpire($info['last_update_password_time']);

            $token = Decrypt::createBossToken($result['id']);
            $info['account'] = $result['username'];
            $info['token'] = strval($token);
            $info['noticeMessage'] = $res_check['notice'];

            return Json::success($info);
        } catch (Exception $e) {
            $this->renderJson($e);
        }

    }

    public function actionGetLoginUserInfo()
    {
        $user_id = $this->getUserId();

        $user_logic = new UserLogic();
        $user_info = $user_logic->info(['id' => $user_id]);

        if (is_numeric($user_info)) {
            return Json::message($this->_messages[$user_info]);
        }
        if (empty($user_info)) {
            return Json::success([]);
        }

        // 查询权限列表
        $permission_list = $user_logic->getUserPermissionList($user_id);

        // 查询用户角色
        $role_list = $user_logic->getRoleListByUserId($user_id);
        $user_info['role_list'] = $role_list ? $role_list : [];

        $user_info['permission_list'] = $permission_list;
        return Json::success($this->keyMod($user_info));
    }

    /**
     * 更新管理员自己的密码
     * @return array
     */
    public function actionUpdate()
    {
        $request_data = Request::post();
        $request_data['user_id'] = $this->getUserId();
        $model = Validate::validateData($request_data, [
            ['user_id', 'required', 'message' => 10016],
            ['oldPassword', 'required', 'message' => 10018],
            ['password', 'required', 'message' => 10004],
        ]);
        if ($model->hasErrors()) {
            return Json::message($this->_messages[$model->getFirstError()]);
        }
        $user_logic = new UserLogic();
        $result = $user_logic->updatePwd($request_data['user_id'], $request_data['oldPassword'], $request_data['password']);

        if ($result === true) {
            return Json::message('操作成功', 0);
        }
        return Json::message($this->_messages[$result]);
    }

    /**
     * 退出登录
     * @return array
     */
    public function actionLogout()
    {
        return Json::success([]);
    }

    /**
     * @return string
     */
    private function getUserId()
    {
        return intval($this->userInfo['id']);
    }

    public function actionRegister(){
        try{

            if($this->userInfo['id'] != 1){
                throw new UserException('您无权操作！请联系管理员', 1);
            }

            $username = Request::input('username');
            $password = Request::input('password');

            $attributes = ['username', 'password'];
            $rules = [
                [
                    ['username', 'password'],
                    'required',
                ],
                [
                    ['username', 'password'],
                    'string',
                    'length'=>[5,20],
                ],
            ];

            $this->verifyParam($attributes, Request::input(), $rules);

            $data['username'] = $username;
            $data['salt'] = SysUser::makeSalt();
            $password = strtolower(md5($password));
            $data['password'] = SysUser::makePasswd($password, $data['salt']);
            $data['modify_id'] = $this->userInfo['id'];
            $query = new SysUser();
            $query->setAttributes($data);

            $res = $query->save();

            if(!$res){
                throw new UserException($query->getFirstError(), 1);
            }

            $this->renderJson([]);
        }catch (Exception $e){
            $this->renderJson($e);
        }

    }


    /**
     * @return \yii\web\Response
     */
    public function actionPwdGen()
    {
        $request = \Yii::$app->request;
        $pwd = (trim($request->get('pwd')));
        $password = strtolower(md5($pwd));
        $salt = substr(uniqid(), -8);
        $password = SysUser::makePasswd($password, $salt);

        return $this->asJson(compact('salt', 'password'));

    }

    /**
     * actionSendSmsCode --
     * @author JerryZhang
     * @cache No
     */
    public function actionSendSmsCode(){
        try{
            $username = Request::input('username');

            $attributes = ['username'];
            $rules = [
                [
                    ['username'],
                    'required',
                    'message' => \Yii::t($this->i18nCategory, 'error.params.username'),
                ],
                [
                    ['username'],
                    'string',
                    'min' => 1,
                    'message' => \Yii::t($this->i18nCategory, 'error.params.username'),
                ]
            ];
            $this->verifyParam($attributes, Request::input(), $rules);

            $params['username'] = $username;
            $params['is_deleted'] = CConstant::DEL_NO;
            $params['status'] = 1;
            $info = SysUser::lists($params);
            $info = array_shift($info);
            if(empty($info)){
                throw new Exception(\Yii::t($this->i18nCategory, 'error.username.not_exist'),100020);
            }
            if(empty($info['phone'])){
                throw new Exception(\Yii::t($this->i18nCategory, 'error.username.not_set_phone'),100023);
            }

            $sms_code = SmsCode::create($info['phone'], 'boss_auth', 4);
            if(!$sms_code){
                throw new Exception(\Yii::t($this->i18nCategory, 'error.sms_code.send_limited'),100021);
            }
            $data = Common::sendLoginCode($info['phone'], $sms_code);
            \Yii::info(['param'=>[$info['phone'], $sms_code], 'result'=>$data], 'bossLoginSmsCode');
            if(!$data){
                throw new Exception(\Yii::t($this->i18nCategory, 'error.sms_code.send_fail'),100022);
            }

            $this->renderJson([]);
        }catch (Exception $e){
            $this->renderJson($e);
        }

    }

    /**
     * checkSmsCode --
     * @author JerryZhang
     * @param $sms_code
     * @param $username
     * @return bool
     * @cache No
     * @throws Exception
     */
    private function checkSmsCode($sms_code, $username){

        $sms_code_check_switch = \Yii::$app->params['BossLoginSMSCheckSwitch'];
        if(!$sms_code_check_switch){
            return true;
        }

        $params['username'] = $username;
        $params['is_deleted'] = CConstant::DEL_NO;
        $params['status'] = 1;
        $info = SysUser::lists($params);
        $info = array_shift($info);
        if(empty($info)){
            throw new Exception(\Yii::t($this->i18nCategory, 'error.username.not_exist'),100024);
        }
        if(empty($info['phone'])){
            throw new Exception(\Yii::t($this->i18nCategory, 'error.username.not_set_phone'),100025);
        }

        $res = SmsCode::validate($info['phone'], $sms_code, 'boss_auth');

        if($res !== true){
            throw new Exception(SmsCode::getMessageByTimes($res), 100026);
        }
    }

    /**
     * checkPasswordExpire --校验密码过期
     * @author JerryZhang
     * @param $last_update_password_time
     * @return mixed
     * @cache No
     * @throws Exception
     */
    private function checkPasswordExpire($last_update_password_time){
        $res['notice'] = '';
        $last_update_password_time = strtotime($last_update_password_time);
        $passwordUpdatePeriod = \Yii::$app->params['passwordUpdateRule']['updatePeriod'];
        $startNoticeTime = \Yii::$app->params['passwordUpdateRule']['startNoticeTime'];
        if(time() - $last_update_password_time > $passwordUpdatePeriod){
            throw new Exception(\Yii::t($this->i18nCategory, 'error.password.expired'),100027);
        }

        $remain_time = $passwordUpdatePeriod - (time() - $last_update_password_time );
        if($remain_time <= $startNoticeTime){
            $res['notice'] = \Yii::t($this->i18nCategory, 'error.password.will_expire', ['days' => floor($remain_time / 86400)]);
        }

        return $res;
    }

}
