<?php

namespace driver\modules\ucenter\controllers;

use common\logic\blacklist\LoginBlackList;
use common\logic\FileUrlTrait;
use common\logic\HttpTrait;
use common\logic\Sign;
use common\models\CarInfo;
use common\models\Decrypt;
use common\models\DriverBaseInfo;
use common\models\DriverInfo;
use common\models\Jpush;
use common\models\Order;
use common\models\PushAccount;
use common\models\SmsCode;
use common\util\Common;
use common\util\Json;
use yii\web\Controller;

/**
 * Site controller
 */
class AuthController extends Controller
{
    use FileUrlTrait, HttpTrait;

    const TYPE_SCREEN = 3; // 车机
    const TYPE_DRIVER = 2; // 司机
    private $servicePhone = '';

    public function init()
    {
        $servicePhone = \Yii::$app->params['driverServicePhone'] ?? '0571-8690-809';
        $this->servicePhone = $servicePhone;
    }

    /**
     * @return array
     */
    public function actionLogin()
    {
        $request = \Yii::$app->request;
        $mobile = trim($request->post('mobile'));
        $smsCode = trim($request->post('smsCode'));

        if (!$mobile || !$smsCode) {
            return Json::message('参数错误');
        }

        // 登录阻塞检测
        $ttl = LoginBlackList::checkBlockList($mobile);
        if ($ttl) {
            return Json::message($ttl . '后才能登录');
        }
        try {
            $encryptPhone = Decrypt::encryptPhone($mobile);
        } catch (\Exception $e) {
            $encryptPhone = '';
        }
        $driver = DriverInfo::findOne(['phone_number' => $encryptPhone]);
        // 检测司机账号
        $result = $this->checkDriverStatus($driver);
        if ($result !== true) {
            return Json::error(['phone' => $this->servicePhone], $result[0], $result[1]);
        }
        // 检测验证码
        $result = SmsCode::validate($mobile, $smsCode);
        if ($result == -1) {
            LoginBlackList::pushBlockList($mobile);
        } elseif ($result >= 5) {
            LoginBlackList::pushBlockList($mobile, 3);
        } elseif ($result >= 3) {
            LoginBlackList::pushBlockList($mobile, 2);
        }
        if ($result !== true) {
            return Json::message(SmsCode::getMessageByTimes($result));
        }

        // 注册token
        $requestData = ['type' => self::TYPE_DRIVER, 'phoneNum' => $mobile, 'id' => $driver->id];
        try {
            $token = Decrypt::generateToken($requestData);
        } catch (\Exception $e) {
            $token = '';
        }
        if (!$token) {
            return Json::message('登录失败');
        }

        // 绑定推送ID
        $pushId = trim($request->post('pushId'));
        $source = trim($request->post('source'));
        $bindResult = PushAccount::bindDriverPushId($driver->id, $pushId, $source);
        if (!$bindResult) {
            \Yii::debug('司机' . $driver->id . '绑定极光账号失败');
        }

        // 获取车机deviceCode
        $carDevice = CarInfo::getScreenDeviceCodesById($driver->car_id);

        // 返回结果
        $driverBaseInfo = DriverBaseInfo::findOne(['id' => $driver->id]);
        $responseData = [
            'accessToken' => $token, // token
            'phoneNumber' => $mobile, // 手机号
            'gender' => $driver->gender, // 性别
            'driverName' => $driver->driver_name, // 司机姓名/昵称
            'headImg' => $driver->head_img,
            'driverId' => $driver->id, // 司机ID
            'workStatus' => $driver->work_status, // 工作状态
            'driverDevice' => $carDevice->driverDevice, // 车机设备ID
            'address' => $driverBaseInfo ? $driverBaseInfo->address : '', // 司机地址
            'secret' => (new Sign())->genKey($token), // 签名密钥
        ];
        // 头像URL处理
        $this->patchUrl($responseData, ['headImg']);
        // 记录登录日志, 防止各种踢皮球问题
        $this->createLoginLog();
        return Json::success($responseData);
    }

    // 测试功能, 后续会注释掉
    public function actionSignTest()
    {
        $result = false;
        $result && $result = (new Sign())->checkSign();
        return Json::success(['result' => $result]);
    }

    /**
     * 重置签名key
     * 用于客户端或服务端redis丢失key数据等意外情况
     * @return array
     */
    public function actionResetKey()
    {
        $token = Decrypt::getToken();
        return Json::success(['secret' => (new Sign())->genKey($token)]);
    }


    /**
     * 发送短信验证码
     * @return array
     */
    public function actionSendSms()
    {
        $request = \Yii::$app->request;
        $mobile = trim($request->post('mobile'));

        $code = SmsCode::create($mobile);
        if (!$code) {
            return Json::message('短信发送过于频繁,请于一小时后再发');
        }
        // 注册检测
        try {
            $encryptPhone = Decrypt::encryptPhone($mobile);
        } catch (\Exception $e) {
            return Json::message('解密服务异常');
        }
        $driver = DriverInfo::findOne(['phone_number' => $encryptPhone]);
        // 司机账号状态检测
        $result = $this->checkDriverStatus($driver);
        if ($result !== true) {
            return Json::error(['phone' => $this->servicePhone], $result[0], $result[1]);
        }
        // 发送短信息
        try {
            Common::sendLoginCode($mobile, $code);
        } catch (\Exception $e) {
            return Json::message('发送短信失败');
        }
        // 返回结果
        return Json::message('发送短信验证码成功', 0);
    }

    /**
     * 车机登录
     * @return array
     */
    public function actionScreenLogin()
    {
        $request = \Yii::$app->request;
        $csDeviceCode = trim($request->post('csDeviceCode'));
        if (!$csDeviceCode) {
            return Json::message('参数异常');
        }
        $carId = CarInfo::checkDriverScreen($csDeviceCode);
        if (!$carId) {
            return Json::message('未登记的车机设备');
        }
        $driverId = $this->userInfo['id'] ?? false;
        if (!$driverId) {
            return Json::message('司机未登录APP');
        }
        $driverInfo = DriverInfo::findOne(['id' => $driverId]);
        if (!$driverInfo) {
            return Json::message('司机参数异常');
        }
        if ($driverInfo->car_id != $carId) {
            return Json::message('司机和绑定车辆不匹配');
        }
        $requestData = ['type' => self::TYPE_SCREEN, 'phoneNum' => $csDeviceCode, 'id' => $carId];
        try {
            $token = Decrypt::generateToken($requestData);
        } catch (\Exception $e) {
            $token = '';
        }
        if (!$token) {
            return Json::message('登录大屏失败');
        }

        return Json::message('登录成功');

    }

    /**
     * 极光别名生成
     * @return array
     */
    public function actionAliasGen()
    {
        $deviceCode = trim(strval(\Yii::$app->request->post('deviceCode')));
        return Json::success(['alias' => Jpush::genAlias($deviceCode)]);
    }

    /**
     * @return array|\yii\web\Response
     */
    public function actionLogout()
    {
        $token = Decrypt::getToken();
        if (!$token) {
            return Json::message('请登录后操作');
        }
        $serviceOrderNumber = $this->checkOnServiceOrders($this->getDriverId($token));
        if ($serviceOrderNumber > 0) {
            return Json::message('您还有未完成的订单, ');
        }
        // 司机自动收车
        $reqParams = \Yii::$app->request->post();
        $reqParams['id'] = $this->getDriverId($token); // 司机ID
        $reqParams['workStatus'] = 0; // 收车
        $reqParams['isFollowing'] = 0; // 关闭顺风单
        $result = self::httpPost('account.driverWorkStatus', $reqParams);
        if ($result['code']) {
            return $this->asJson($result);
        }
        // 注销token
        try {
            Decrypt::makeAccountRequest(['token' => $token], 'checkOut');
        } catch (\Exception $e) {
            \Yii::debug('注销token失败', 'logout_warning');
        }
        // 返回成功
        return Json::success();
    }

    private function checkDriverStatus($driver)
    {
        if (!$driver || !is_object($driver)) {
            return [399, '账号不存在，请联系客服 ' . $this->servicePhone];
        }
        if (!$driver->sign_status) {
            return [405, '您的账号已被解约,请联系客服' . $this->servicePhone];
        }
        if (!$driver->use_status) {
//            return [403, '您的账号已被冻结,请联系客服' . $this->servicePhone];
        }
        if (!trim($driver->car_id)) {
            return [404, '您还未绑定车辆,请联系客服' . $this->servicePhone];
        }
        return true;
    }

    private function checkOnServiceOrders($driverId)
    {
        $query = Order::find()
            ->where(['driver_id' => $driverId])
            ->andWhere(['between', 'status', 3, 5]);
        $serviceOrders = $query->count();
        return $serviceOrders;
    }

    private function getDriverId($token)
    {
        $tokenArray = explode('.', $token);
        $tokenObj = json_decode(base64_decode($tokenArray[1]));
        $sub = $tokenObj->sub;
        $subArray = explode('_', $sub);
        return $subArray[2] ?? 0;
    }

    private function createLoginLog()
    {
        $data = \Yii::$app->request->post();
        $content = date('ymdHis') . '|' . \Yii::$app->request->userIP . ':' . json_encode($data, 256) . PHP_EOL;
        file_put_contents(\Yii::getAlias('@runtime') . '/login.log', $content, 8);
    }

}
