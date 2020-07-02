<?php

namespace common;

class Constants
{

    //AJAX 错误码
    const AJAX_SUCCESS_CODE = 0;

    // 用户登录类错误, 从100开始
    const WEB_ERROR_LOGIN_USER_NOT_EXIST = 101;
    const WEB_ERROR_LOGIN_PASSWORD_ERR = 102;
    const WEB_ERROR_LOGIN_USER_EXPIRED = 103;
    const WEB_ERROR_LOGIN_USER_LOCK = 104;
    const WEB_ERROR_LOGIN_USER_NO_ROLE = 105;

    //10000的为web常见错误分类
    const WEB_ERROR_UNKNOWN = 10000;
    const WEB_ERROR_PARAM = 10001;
    const WEB_ERROR_PERMISSION_DENIED = 10002;
    const WEB_ERROR_LOGIN = 10003;
    const WEB_ERROR_VERIFY_CODE = 10004;
    const WEB_ERROR_DEFAULT_PASS = 10005;
    const WEB_ERROR_GROUP_NAME_EXIST = 10006;
    const WEB_ERROR_SEND_MAIL = 10007;
    const WEB_ERROR_USER_EXIST = 10008;
    const WEB_ERROR_OLD_PASSWORD = 10009;


    //20000为数据层错误
    const DATA_ERROR_NEED_TRANS = 20001;
    const DATA_ERROR_SQL = 20002;


    //30000的为API接口错误分类
    const API_ERROR_TSS_OFFLINE = 30001;
    const API_ERROR_NODE_EXIST = 30002;


    /****************************************************以下为静态变量定义区*****************************************************/

    //定义AJAX默认返回成功值
    public static $WEB_SUCCESS_RT
        = array(
            'code' => self::AJAX_SUCCESS_CODE,
            'msg' => 'success',
        );



}