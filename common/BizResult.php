<?php

namespace common;

use Yii;
use yii\base\UserException;

class BizResult
{
    static public function ensureNotFalse($result, $err_no, $msg_param = null)
    {
        if ($result === false) {
            throw new BizException(is_array($msg_param) ? vsprintf(Yii::t('sys_error', $err_no), $msg_param) : Yii::t('sys_error', $err_no), $err_no);
        }
        return $result;
    }

    static public function ensureFalse($result, $err_no, $msg_param = null)
    {
        if ($result !== false) {
            throw new BizException(is_array($msg_param) ? vsprintf(Yii::t('sys_error', $err_no), $msg_param) : Yii::t('sys_error', $err_no), $err_no);
        }
        return $result;
    }

    static public function ensureNotFalseMsg($result, $err_msg)
    {
        if ($result === false) {
            throw new BizException($err_msg, 400);
        }
        return $result;
    }
}

class BizException extends UserException
{
    public function __construct($err_msg, $err_no = null)
    {
        if (is_array($err_msg)) {
            $err_msg = implode(',', $err_msg);
        }
        if ($err_no === null) {
            $err_no = 1;
        }
        parent::__construct($err_msg, $err_no);
    }
}
