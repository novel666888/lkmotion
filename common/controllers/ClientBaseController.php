<?php

namespace common\controllers;

use Yii;
use common\logic\Sign;
use common\models\Decrypt;
use common\services\CConstant;
use common\util\Common;
use yii\base\UserException;


class ClientBaseController extends BaseController
{
    protected $checkSign = true; // 客户端签名总开关
    private $i18nCategory = 'sys_error';
    public $userInfo = ['id' => '', 'identity' => '', 'phone' => '', ];
    public $tokenInfo = null;

    /**
     * 验签开关说明:
     * 配置状态为false的时候, 所有客户端不开启验证
     * 配置状态为true或者不配置的时候, 根据成员属性$checkSign状态来决定是否开启验签
     * 成员属性$checkSign可以被子类更改
     */
    public function init()
    {
        $checkSign = \Yii::$app->params['checkSign'] ?? true;
        $this->checkSign = ($this->checkSign && $checkSign);
        // 白名单和验签
        if ($this->checkSign) {
            $notInWhiteList = Common::checkUrlWhiteList('whiteList');
//            $notInWhiteList && $this->checkReplayAttack(); // 加载重放攻击检测,!!!一定要放在验签之前
            $notInWhiteList && $this->checkSign();
        }

        //验证登陆信息
        $this->checkToken(Common::checkUrlWhiteList('whiteList'));

        parent::init();
    }

    public function checkSign()
    {
        $errData = ['code' => 901, 'message' => '请求失败', 'data' => new \stdClass()];
        // 验签
        $checkSignResult = (new Sign())->checkSign();
        if (!$checkSignResult) {
            $response = json_encode($errData, 256);
            \Yii::info($response, 'check_sign_failed');
            die($response);
        }
    }

    public function checkReplayAttack()
    {
        $errData = ['code' => 900, 'message' => '操作频繁', 'data' => new \stdClass()];
        // 重放攻击检测
        $attack = Sign::checkReplayAttack();
        if (is_string($attack)) {
            $response = json_encode(['code' => 1, 'message' => $attack, 'data' => new \stdClass()], 256);
            die($response);
        }
        if ($attack === true) {
            $response = json_encode($errData, 256);
            die($response);
        }
    }

    public function checkToken($check_login = true)
    {
        try {
            if ($check_login) {
                $header = \Yii::$app->request->headers->toArray();
                if (empty($header['authorization'][0])) {
                    throw new UserException(\Yii::t($this->i18nCategory, CConstant::ERROR_CODE_TOKEN_NULL), CConstant::ERROR_CODE_TOKEN_NULL);
                }
            }

            $tokenInfo = Decrypt::clientGetTokenInfo();
            if ($tokenInfo) {
                $this->tokenInfo = $tokenInfo;
                if (isset($this->tokenInfo->sub) && !empty($this->tokenInfo->sub)) {
                    $info = explode("_", $this->tokenInfo->sub);
                    $this->userInfo['id'] = isset($info[2]) ? $info[2] : '';
                    $this->userInfo['identity'] = isset($info[0]) ? $info[0] : '';
                    $this->userInfo['phone'] = isset($info[1]) ? $info[1] : '';
                    Yii::info($this->userInfo, 'loginInfo');
                }
            }
            if ($check_login) {
                if (empty($this->userInfo['id'])) {
                    throw new UserException(\Yii::t($this->i18nCategory, CConstant::ERROR_CODE_TOKEN_ERROR), CConstant::ERROR_CODE_TOKEN_ERROR);
                }
            }
        } catch (UserException $e) {
            if (in_array($e->getCode(), [CConstant::ERROR_CODE_TOKEN_ERROR, CConstant::ERROR_CODE_TOKEN_NULL])) {
                $data['code'] = $e->getCode();
                $data['message'] = $e->getMessage();
                $data['data'] = [];
                echo json_encode($data, 256);
                Yii::info(['token' => $this->tokenInfo, 'data' => $data], 'loginInfo');
                exit;
            } else {
                throw $e;
            }
        }
    }
}
