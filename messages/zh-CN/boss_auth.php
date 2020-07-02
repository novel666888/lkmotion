<?php

use common\services\CConstant;

return [

    CConstant::ERROR_CODE_TOKEN_ERROR => '登陆已过期，请重新登录',
    CConstant::ERROR_CODE_TOKEN_NULL => '请登录',
    'error.params.username' => '用户名输入错误',
    'error.username.not_exist' => '用户名不存在',
    'error.username.not_set_phone' => '该用户未设置手机号，请联系管理员',
    'error.sms_code.send_fail' => '验证码发送失败',
    'error.sms_code.send_limited' => '验证码发送受限',
    'error.password.expired' => '密码已过期，请重新修改登录',
    'error.password.will_expire' => '您的密码将在{days}天后过期，请尽快修改，过期后将无法登陆',

    'error.operation.fail' => '操作失败，请联系系统管理员',
];