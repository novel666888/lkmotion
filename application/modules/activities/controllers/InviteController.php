<?php

namespace application\modules\activities\controllers;

use application\controllers\BossBaseController;
use application\modules\order\models\SysUser;
use common\models\CarInfo;
use common\models\City;
use common\models\Decrypt;
use common\models\DriverInfo;
use common\models\DriverLicenceInfo;
use common\models\InviteRecord;
use common\models\ListArray;
use common\models\PassengerInfo;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use yii\db\ActiveQuery;

/**
 * Class InviteController
 * @package application\modules\activities\controllers
 */
class InviteController extends BossBaseController
{
    use ModelTrait;

    /**
     * @return \yii\web\Response
     */
    public function actionDriver()
    {
        $query = $this->getDriverQuery();

        $sort = intval(\Yii::$app->request->post('sort'));
        $sort = $sort ? 'ASC' : 'DESC';

        $result = self::getPagingData($query, 'total ' . $sort);
        if ($result['code'] == 0) {
            $this->patchDriverInfo($result['data']['list']);
        }
        return $this->asJson(Common::key2lowerCamel($result));
    }

    /**
     * @return array|\yii\console\Response|\yii\web\Response
     */
    public function actionDriverExport()
    {
        // 导出查询
        $query = $this->getDriverQuery();

        $number = $query->count();
        if ($number < 1) {
            return Json::message('暂无数据');
        }
        try {
            return $this->exportSummary($query);
        } catch (\Exception $e) {
            return Json::message('生成excel异常');
        }
    }

    /**
     * @param int $inviteDriverId
     * @return \yii\web\Response
     */
    public function actionDetails($inviteDriverId = 0)
    {
        $query = $this->getDriverDetailsQuery($inviteDriverId);
        // 导出检测
        $result = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        if ($result['code'] == 0) {
            $this->patchDriverInfo($result['data']['list'], 1);
        }
        return $this->asJson(Common::key2lowerCamel($result));
    }

    /**
     * @param $inviteDriverId
     * @return array|\yii\console\Response|\yii\web\Response
     */
    public function actionDetailsExport($inviteDriverId)
    {
        $query = $this->getDriverDetailsQuery($inviteDriverId);
        $number = $query->count();
        if ($number < 1) {
            return Json::message('暂无数据');
        }
        try {
            return $this->exportDetails($query);
        } catch (\Exception $e) {
            return Json::message('生成excel异常');
        }
    }

    /**
     * 根据推荐司机ID软删除推荐信息
     * @return array
     */
    public function actionDelDriver()
    {
        $hitIds = $this->getDriverIds();
        if (!$hitIds) {
            return Json::message('未找到对应邀请数据');
        }
        // 数据
        $changeData = [
            'is_delete' => 1,
            'operator_id' => isset($this->userInfo['id']) ? intval($this->userInfo['id']) : 0,
        ];
        $result = InviteRecord::updateAll($changeData, ['in', 'invite_driver_id', $hitIds]);
        if ($result) {
            return Json::message('操作成功', 0);
        }
        return Json::message('删除失败');
    }

    /**
     * @return \yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionPassenger()
    {
        $request = \Yii::$app->request;
        // 查询数据
        $query = InviteRecord::find()->where(['>', 'invite_passenger_id', 0]);
        $invitePhone = trim($request->post('invitePhone'));
        if (is_string($invitePhone) && strlen($invitePhone) >= 8) {
            $query->andWhere(['invite_passenger_id' => $this->getUserIdByPhone($invitePhone)]);
        }
        $phone = trim($request->post('phone'));
        if (is_string($phone) && strlen($phone) >= 8) {
            $query->andWhere(['passenger_id' => $this->getUserIdByPhone($phone)]);
        }
        $result = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);
        if ($result['code'] == 0) {
            $this->patchPassengerInfo($result['data']['list']);
        }

        return $this->asJson(Common::key2lowerCamel($result));
    }

    /**
     * @return ActiveQuery
     */
    private function getDriverQuery()
    {
        $request = \Yii::$app->request;
        $hitIds = $this->getDriverIds();

        // 查询数据
        $query = InviteRecord::find()->select('invite_driver_id,create_time,count(id) as total');
        $query->where(['>', 'invite_driver_id', 0])->andWhere(['is_delete' => 0]);
        $startTime = trim($request->post('startTime'));
        $endTime = trim($request->post('endTime'));
        if (is_string($startTime) && strlen($startTime) >= 6) {
            $query->andWhere(['>=', 'create_time', $this->formatTime($startTime)]);
        }
        if (is_string($endTime) && strlen($endTime) >= 6) {
            $query->andWhere(['<', 'create_time', $this->formatTime($startTime, 1)]);
        }
        if ($hitIds !== false) { // 邀请人筛选
            $query->andWhere(['in', 'invite_driver_id', $hitIds]);
        }
        $query->groupBy('invite_driver_id');
        // 返回查询对象
        return $query;
    }

    /**
     * @param $inviteDriverId
     * @return ActiveQuery
     */
    private function getDriverDetailsQuery($inviteDriverId)
    {
        $request = \Yii::$app->request;
        if (!$inviteDriverId) {
            $inviteDriverId = intval($request->post('inviteDriverId'));
        }
        // 查询数据
        $query = InviteRecord::find()->where(['invite_driver_id' => $inviteDriverId]);
        $query->andWhere(['is_delete' => 0]);
        $ids = $request->post('ids');
        if (is_string($ids)) {
            $ids = trim($ids);
            $idArray = $ids ? array_filter(explode(',', $ids)) : [];
        } elseif (is_array($ids)) {
            $idArray = array_filter($ids);
        } else {
            $idArray = false;
        }
        if ($idArray) { // 优先使用ID筛选
            $query->andWhere(['in', 'id', $idArray]);
        } else { // 使用其他条件筛选
            $startTime = trim($request->post('startTime'));
            $endTime = trim($request->post('endTime'));
            if (is_string($startTime) && strlen($startTime) >= 6) {
                $query->andWhere(['>=', 'create_time', $this->formatTime($startTime)]);
            }
            if (is_string($endTime) && strlen($endTime) >= 6) {
                $query->andWhere(['<', 'create_time', $this->formatTime($startTime, 1)]);
            }
        }
        return $query;
    }

    /**
     * @param array $item
     * @param bool $ext
     * @return array
     */
    private function getInviteItem($item = [], $ext = false)
    {
        $tpl = [
            'invite_identity_card_id' => isset($item['invite_identity_card_id']) ? $item['invite_identity_card_id'] . ' ' : '身份证号',
            'invite_driver_name' => $item['invite_driver_name'] ?? '司机姓名',
            'invite_driver_id' => $item['invite_driver_id'] ?? '司机ID',
            'invite_driver_phone' => $item['invite_driver_phone'] ?? '司机手机号',
            'create_time' => $item['create_time'] ?? '最新提交时间',
            'invite_city_code' => $item['invite_city_code'] ?? '邀请司机城市',
            'invite_driver_leader' => $item['invite_driver_leader'] ?? '邀请司机主管',
        ];
        if ($ext) {
            $tpl['driver_name'] = $item['driver_name'] ?? '被推荐人';
            $tpl['driver_phone'] = $item['driver_phone'] ?? '被推荐人手机号';
        } else {
            $tpl['total'] = $item['total'] ?? '推荐个数';
        }
        return $tpl;
    }


    /**
     * @param $phone
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    private function getUserIdByPhone($phone)
    {
        $encryptPhone = Decrypt::encryptPhone($phone);
        if (!$encryptPhone) return 0;
        $passengerInfo = PassengerInfo::findOne(['phone' => $encryptPhone]);
        if (!$passengerInfo) {
            return 0;
        }
        return $passengerInfo->id;
    }

    /**
     * 返回搜索条件筛选出的司机ID
     * 如果为false,则没有筛选; 如果返回数组,则有筛选,空数组则是筛选结果为空集.
     * @return array|bool
     */
    private function getDriverIds()
    {
        $request = \Yii::$app->request;

        // 优先使用身份证号筛选
        $ids = $request->post('ids');
        if (is_string($ids)) {
            $ids = trim($ids);
            $idArray = $ids ? array_filter(explode(',', $ids)) : [];
        } elseif (is_array($ids)) {
            $idArray = array_filter($ids);
        } else {
            $idArray = [];
        }
        if ($ids) {
            return $idArray;
        }
        // 再使用其他条件筛选
        $search = trim($request->post('search'));
        if ($search) {
            try {
                $encryptPhone = Common::phoneEncrypt($search);
            } catch (\Exception $e) {
                $encryptPhone = 'encryptError';
            }
            $query = DriverInfo::find()
                ->where(['phone_number' => $encryptPhone])// 手机号搜索
                ->orWhere(['driver_name' => $search]); // 姓名搜索
            $carInfo = CarInfo::findOne(['plate_number' => $search]);
            if ($carInfo) {
                $query->orWhere(['car_id' => $carInfo->id]); // 车牌号查询
            }
            $driverIds = $query->select('id')->asArray()->all();
            $byCarId = DriverLicenceInfo::find()
                ->where(['identity_card_id' => $search])// 身份证号码查询
                ->select('driver_id')->limit(1)->scalar();
            if ($byCarId) {
                $driverIds[] = $byCarId;
            }
        } else {
            $driverIds = [];
        }
        // 获取司机ID
        $hitIds = false;
        $cityCode = trim($request->post('cityCode'));
        $leaderId = $request->post('leaderId');
        if ($cityCode || $leaderId || $search) {
            $query = DriverInfo::find()->filterWhere(['city_code' => $cityCode, 'driver_leader' => $leaderId]);
            if ($search) {
                $query->andWhere(['in', 'id', $driverIds]);
            }
            $result = $query->select('id')->asArray()->all();
            $hitIds = array_column($result, 'id');
        }
        return $hitIds;
    }

    /**
     * @param $date
     * @param bool $nd
     * @return mixed
     */
    private function formatTime($date, $nd = false)
    {
        $time = strtotime($date);
        if ($nd) $time += 86400;
        return date('Y-m-d H:i:s', $time);
    }

    /**
     * @param $list
     * @param bool $ext
     */
    private function patchDriverInfo(&$list, $ext = false)
    {
        $hitIds = array_column($list, 'invite_driver_id', 'invite_driver_id');
        $driversInfo = DriverInfo::find()->where(['in', 'id', $hitIds])
            ->select('id,driver_name,phone_number,driver_leader,city_code')
            ->indexBy('id')->asArray()->all();
        $encrypts = [];
        foreach ($driversInfo as $item) {
            $encrypts[] = $item['phone_number'];
        }
        $phoneMap = (new ListArray())->getDecryptPhones($encrypts);
        // 获取身份证号码
        $identity = DriverLicenceInfo::find()->where(['in', 'driver_id', $hitIds])->select('driver_id,identity_card_id')->asArray()->all();
        $identityMap = array_column($identity, 'identity_card_id', 'driver_id');
        // 获取城市列表
        $city = (new ListArray())->getCityList(false);
        $cityMap = array_column($city, 'city_name', 'city_code');
        // 打包信息
        foreach ($list as &$item) {
            if (isset($item['invite_passenger_id'])) {
                unset($item['invite_passenger_id']);
            }
            if (isset($item['passenger_id'])) {
                unset($item['passenger_id']);
            }
            // 推荐人身份证
            $item['invite_identity_card_id'] = $identityMap[$item['invite_driver_id']] ?? '';
            // 推荐人信息
            if (isset($driversInfo[$item['invite_driver_id']])) {
                $invitor = (object)$driversInfo[$item['invite_driver_id']];
                $item['invite_driver_name'] = $invitor->driver_name;
                if (isset($phoneMap[$invitor->phone_number])) {
                    $item['invite_driver_phone'] = $phoneMap[$invitor->phone_number];
                }
                $item['invite_driver_leader'] = $invitor->driver_leader;
                $item['invite_city_code'] = $invitor->city_code;
                $item['invite_city'] = $cityMap[$invitor->city_code] ?? '';
            } else {
                $item['invite_driver_name'] = '';
                $item['invite_driver_phone'] = '';
                $item['invite_driver_leader'] = '';
                $item['invite_city_code'] = '';
                $item['invite_city'] = '';
            }
            if (!$ext) continue;
            // 被推荐人信息
            $item['invitee_info'] = json_decode($item['invitee_info'], 1);
        }
    }

    /**
     * @param $list
     */
    private function patchPassengerInfo(&$list)
    {
        $inviteIds = array_column($list, 'invite_passenger_id', 'invite_passenger_id');
        $passengerIds = array_column($list, 'passenger_id', 'passenger_id');
        $hitIds = $inviteIds + $passengerIds;
        $phoneMap = (new ListArray())->getPassengerPhoneNumberByIds($hitIds);
        // 打包信息
        foreach ($list as &$item) {
            if (isset($item['invite_driver_id'])) {
                unset($item['invite_driver_id']);
            }
            if (isset($item['driver_id'])) {
                unset($item['driver_id']);
            }
            $item['invite_passenger_phone'] = $phoneMap[$item['invite_passenger_id']] ?? '';
            $item['passenger_phone'] = $phoneMap[$item['passenger_id']] ?? '';
        }
    }

    /**
     * 打包司机城市和主管
     * @param $list
     */
    private function patchCityManage(&$list)
    {
        // 获取城市名称
        $res = City::find()->select('city_code,city_name')->all();
        $cityMap = array_column($res, 'city_name', 'city_code');
        // 获取主管名称
        $res = SysUser::find()->select('id,username')->all();
        $leaderMap = array_column($res, 'username', 'id');
        foreach ($list as &$item) {
            if (isset($item['invite_city_code']) && isset($cityMap[$item['invite_city_code']])) {
                $item['invite_city_code'] = $cityMap[$item['invite_city_code']] ?? $item['invite_city_code'];
            }
            if (isset($item['invite_driver_leader']) && isset($leaderMap[$item['invite_driver_leader']])) {
                $item['invite_driver_leader'] = $leaderMap[$item['invite_driver_leader']];
            }
            if (isset($item['city_code']) && isset($cityMap[$item['city_code']])) {
                $item['city_code'] = $cityMap[$item['city_code']];
            }
            if (isset($item['driver_leader']) && isset($leaderMap[$item['driver_leader']])) {
                $item['driver_leader'] = $leaderMap[$item['driver_leader']];
            }
        }
    }

    /**
     * @param ActiveQuery $query
     * @return \yii\console\Response|\yii\web\Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function exportSummary(ActiveQuery $query)
    {
        $list = $query->asArray()->all();
        $this->patchDriverInfo($list);
        $this->patchCityManage($list);

        // 新建sheet页
        $tpl = $this->getInviteItem();
        $spreadsheet = new Spreadsheet();
        $newData = [$tpl];
        foreach ($list as $item) {
            $newData[] = $this->getInviteItem($item);
        }
        // 生成数据
        $spreadsheet->getActiveSheet()->fromArray($newData);

        $writer = new Xlsx($spreadsheet);
        // 保存excel
        $filename = '司机邀请统计_' . date('ymdHis') . '.xlsx';
        $writer->save($filename);
        // 清除输出缓存
        ob_clean();
        // 输出文件
        return \Yii::$app->response->sendFile($filename);
    }

    /**
     * @param ActiveQuery $query
     * @return \yii\console\Response|\yii\web\Response
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function exportDetails(ActiveQuery $query)
    {
        $list = $query->asArray()->all();
        $this->patchDriverInfo($list, 1);
        $this->patchCityManage($list);

        // 新建sheet页
        $tpl = $this->getInviteItem([], 1);
        $spreadsheet = new Spreadsheet();
        $newData = [$tpl];
        foreach ($list as $item) {
            $newData[] = $this->getInviteItem($item, 1);
        }
        //echo "<pre>";var_dump($newData);exit;
        // 生成数据
        $spreadsheet->getActiveSheet()->fromArray($newData);

        $writer = new Xlsx($spreadsheet);
        // 保存excel
        $filename = '司机个人推荐_' . date('ymdHis') . '.xlsx';
        $writer->save($filename);
        // 清除输出缓存
        ob_clean();
        // 输出文件
        return \Yii::$app->response->sendFile($filename);
    }
}
