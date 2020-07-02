<?php
namespace application\controllers;

use Yii;
use common\controllers\BaseController;
use common\logic\sysuser\UserLogic;
use common\models\Decrypt;
use common\services\CConstant;
use common\util\Common;
use yii\base\UserException;
use yii\helpers\ArrayHelper;

class BossBaseController extends BaseController
{

    private $i18nCategory = 'boss_auth';
    public $userInfo = ['id' => '', 'identity' => '', 'phone' => '', ];
    public $tokenInfo = null;

    public function init()
    {
        parent::init();
        //验证请求签名
        $this->checkEncryptParam();
        //验证登陆信息
        $this->checkToken(Common::checkUrlWhiteList('whiteList'));
        //验证用户权限
        $this->checkSysUserPermission();
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

            $tokenInfo = Decrypt::bossGetTokenInfo();
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

    public function checkSysUserPermission()
    {
        if (in_array($this->userInfo['id'], [1])) {
            return true;
        }

        $pathInfo = \Yii::$app->getRequest()->pathInfo;
        
        $pathInfo = trim(trim($pathInfo, '/'));
        
        if (in_array($pathInfo, \Yii::$app->params['permissionWhiteList'])) {
            return true;
        }

        $user_logic = new UserLogic();

        $permission_lists = $user_logic->getUserPermissionList($this->userInfo['id'], 0);
        $permissions = [];
        foreach ($permission_lists as $_k => $_v) {
            $permissions = array_merge($permissions, explode(',', $_v['permissions']));
        }
        
        $permissions = array_unique(array_filter($permissions));
        if (in_array($pathInfo, $permissions)) {
            return true;
        }
        echo json_encode([
            'code' => 100401,
            'messages' => '无权限访问',
            'data' => []
        ]);
        exit();
    }

    /*
     * boss 后台签名验证
     * */
    public function checkEncryptParam()
    {
        $bossCheck = ArrayHelper::getValue(\Yii::$app->params,'bossSignCheckSwitch');
        if($bossCheck == 1 && Common::checkUrlWhiteList('signCheckWhite')){
            $result = \Yii::$app->request->post();
            \Yii::info($result, 'getData');

            if(!$result['sign']){
                echo json_encode(['code' => 403, 'message' => '接口参数验证失败!', 'data' => []]); exit();
            }
            $result = array_filter($result, function($v) {
                if (!is_array($v)) {
                    return true;
                }
            });
            \Yii::info($result, 'result');
            $sign = $result['sign'];
            $dateline = $result['dateline'];
            unset($result['sign']);
            unset($result['dateline']);

            ksort($result);
            $result['dateline'] = $dateline;
            $result['secretKey'] = ArrayHelper::getValue(\Yii::$app->params,'secretKey');
            \Yii::info($result, 'paramresult');

            $param = http_build_query($result, null,'&', PHP_QUERY_RFC3986);
            \Yii::info($param, 'param');

            $checkSign = md5($param);
            \Yii::info($checkSign, 'checkSign');

            if( $sign != $checkSign ){
                echo json_encode(['code' => 403, 'message' => '接口参数验证失败!', 'data' => []]); exit();
            }
        }
    }

}