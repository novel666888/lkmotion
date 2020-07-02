<?php
namespace application\modules\ad\controllers;

use common\models\Ads;
use common\models\AdPosition;
use common\util\Json;
use common\util\Cache;
use common\logic\LogicTrait;
use common\logic\AdLogic;
use common\logic\BossAdLogic;
use yii\helpers\ArrayHelper;
use application\controllers\BossBaseController;
class AdSystemController extends BossBaseController
{
    use LogicTrait;
    //广告位列表
    public function actionAdPositionList(){
        $request = $this->getRequest();
        $requestData = array(
            'position_name' => $request->post('positionName'),
            'position_type' => $request->post('positionType'),
            'status' => $request->post('status'),
            'content_type' => $request->post('contentType')
        );
        if (!empty($requestData['position_type']) && !in_array($requestData['position_type'], [1,2])){
            return Json::message('广告位类型参数错误');
        }
        if (!empty($requestData['status']) && !in_array($requestData['status'], [0,1])){
            return Json::message('状态参数错误');
        }
        if (!empty($requestData['content_type']) && !in_array($requestData['content_type'], [1,2])){
            return Json::message('内容类型参数错误');
        }
        $adPositionList = BossAdLogic::getAdPositionList($requestData);
        $this->fillUserInfo($adPositionList['list'],'operator_user');
        $adPositionList['list'] = $this->keyMod($adPositionList['list']);
        return Json::success($adPositionList);
    }

    //添加广告位
    public function actionAddAdPosition(){
        $request = $this->getRequest();
        $requestData = array(
            'position_id' => intval($request->post('adLocation')),
            'position_name' => trim($request->post('positionName')),
            'position_desc' => trim($request->post('positionDesc')),
            'position_type' => intval($request->post('positionType')),
            'most_count' => intval($request->post('mostCount')),
            'content_type' => intval($request->post('contentType')),
            'status' => intval($request->post('status')),
            'operator_user' => $this->userInfo['id'],
        );
        //验证参数
        if (empty($requestData['position_id']) || empty($requestData['position_name']) || empty($requestData['position_type']) || empty($requestData['most_count']) || empty($requestData['content_type'])){
            return Json::message('请传递完整参数');
        }
        //判断广告位名称是否存在
        if (!BossAdLogic::checkAdPositionName($requestData['position_name'])){
            return Json::message('广告位名称已存在');
        }
        //判断广告位置是否存在
        if (!BossAdLogic::checkPositionId($requestData['position_id'])){
            return Json::message('广告位置已存在');
        }
        $adPosition = new AdPosition();
        $requestData = array_filter($requestData,function($v){
            return !is_null($v) && ($v!='');
        });
        $adPosition->attributes = $requestData;
        // 验证输入内容
        if (!$adPosition->validate()) {
            $msg = $adPosition->getFirstError();
            return Json::message($msg);
        }

        if (!$adPosition->save()){
            return Json::message('添加失败');
        }else{
            //添加成功将数据做redis缓存
            $insertId = $adPosition->attributes['id'];
            $insertData = AdPosition::find()->where(['id'=>$insertId])->indexBy('id')->asArray()->all();
            Cache::set('ad_position', $insertData, 0);
        }
        return Json::message('广告位添加成功', 0);
    }

    //修改广告位
    public function actionUpdateAdPosition(){
        $request = $this->getRequest();
        $requestData = array(
            'id' => intval($request->post('positionId')),
            'position_id' => intval($request->post('adLocation')),
            'position_name' => trim($request->post('positionName')),
            'position_desc' => trim($request->post('positionDesc')),
            'position_type' => intval($request->post('positionType')),
            'most_count' => intval($request->post('mostCount')),
            'content_type' => intval($request->post('contentType')),
            'status' => intval($request->post('status')),
            'operator_user' => $this->userInfo['id'],
        );
        //验证参数
        if (empty($requestData['position_id']) || empty($requestData['position_name']) || empty($requestData['position_type']) || empty($requestData['most_count']) || empty($requestData['content_type'])){
            return Json::message('请传递完整参数');
        }
        if(!BossAdLogic::checkAdPosition($requestData['id'])){
            return Json::message('广告位不存在！');
        }else{
            //检查广告位名称是否存在
            if (!BossAdLogic::checkAdPositionName($requestData['position_name'], $requestData['id'])){
                return Json::message('广告位名称已存在');
            }
            //判断广告位置是否存在
            if (!BossAdLogic::checkPositionId($requestData['position_id'], $requestData['id'])){
                return Json::message('广告位置已存在');
            }
            $adPosition = new AdPosition();
            $adPosition->attributes = $requestData;

            $oldContentType = AdPosition::find()->select(['content_type'])->where(['id'=>$requestData['id']])->scalar();//更新前的内容类型
            // 验证输入内容
            if (!$adPosition->validate()) {
                $msg = $adPosition->getFirstError();
                return Json::message($msg);
            }
            $result = AdPosition::updateAll($requestData,['id'=>$requestData['id']]);
            if (!$result){
                return Json::message('更新失败');
            }else{
                //修改成功更新redis缓存
                $updateData = AdPosition::find()->where(['id'=>$requestData['id']])->indexBy('id')->asArray()->all();
                Cache::set('ad_position', $updateData, 0);
                //若广告位内容类型修改，冻结当前广告位下所有广告
                if ($requestData['content_type'] != $oldContentType){
                    Ads::updateAll(['status'=>0],['position_id'=>$requestData['position_id']]);
                }
            }
        }
        return Json::message('更新成功', 0);
    }

    //广告位详情
    public function actionAdPositionDetail(){
        $request = $this->getRequest();
        $adPositionId = intval($request->post('positionId'));
        //验证参数
        if (empty($adPositionId)){
            return Json::message('缺少id参数');
        }
        $adPositionDetail = BossAdLogic::getAdPositionDetail($adPositionId);
        if (!$adPositionDetail){
            return Json::message('广告位不存在！');
        }
        $this->fillUserInfo($adPositionDetail,'operator_user');
        $adPositionDetail = $this->keyMod(array_values($adPositionDetail));
        return Json::success($adPositionDetail[0]);
    }

    //广告位冻结/解冻
    public function actionFreezeAdPosition(){
        $request = $this->getRequest();
        $adPositionId = intval($request->post('positionId'));
        $status = intval($request->post('status'));
        //验证参数
        if (empty($adPositionId)){
            return Json::message('缺少id参数');
        }
        if(!BossAdLogic::checkAdPosition($adPositionId)){
            return Json::message('广告位不存在');
        }else{
            if ($status == 0 || $status == 1){
                $result = AdPosition::updateAll(['status'=>$status], ['id'=>$adPositionId]);
                if (!$result){
                    return Json::message('冻结/解冻失败');
                }else{
                    //更新成功，更新redis缓存数据
                    $updateData = AdPosition::find()->where(['id'=>$adPositionId])->indexBy('id')->asArray()->all();
                    Cache::set('ad_position', $updateData, 0);
                }
            }else{
                return Json::message('status参数错误');
            }
        }
        return Json::message('解冻/冻结成功', 0);
    }

    //广告列表
    public function actionAdList(){
        $request = $this->getRequest();
        $requestData = array(
            'name' => $request->post('adName'),
            'start_time' => $request->post('startTime'),
            'end_time' => $request->post('endTime'),
            'platform' => $request->post('platform'),
            'status' => $request->post('adStatus'),
            'is_using' => $request->post('isUsing'),
            'city' => $request->post('city'),
            'position_type' => $request->post('positionType'),
            'position_id' => $request->post('positionId'),
        );
        if (empty($requestData['position_type'])){
            return Json::message('缺少广告类型参数');
        }
        if (!empty($requestData['status']) && !in_array($requestData['status'], [0,1])){
            return Json::message('状态参数错误');
        }
        if (!empty($requestData['is_using']) && !in_array($requestData['is_using'], [1,2,3])){
            return Json::message('进行状态参数错误');
        }
        if (!empty($requestData['position_type']) && !in_array($requestData['position_type'], [1,2])){
            return Json::message('广告类型参数错误');
        }

        $adList = BossAdLogic::getAdList($requestData);
        if(!empty($adList['list'])){
            self::fillUserInfo($adList['list'],'operator_user');
            $adList['list'] = AdLogic::getCityNme($adList['list']);
            $adList['list'] = $this->keyMod($adList['list']);
        }

        return Json::success($adList);
    }

    //插入广告
    public function actionInsertAd(){
        $request = $this->getRequest();
        $requestData = array(
            'city' => implode(",", $request->post('city')),
            'start_time' => trim($request->post('startTime')),
            'end_time' => trim($request->post('endTime')),
            'link_url' => trim($request->post('linkUrl')),
            'type' => intval($request->post('type')),
            'video_img' => trim($request->post('videoImg')),
            'name' => trim($request->post('name')),
            'platform' => trim($request->post('platform')),
            'position_id' => intval($request->post('positionId')),
            'people_tag_id' => intval($request->post('peopleTagId')),
            'status' => intval($request->post('status',1)),
            'operator_user' => $this->userInfo['id'],
        );

        //图片、视频key值区分
        if ($requestData['type'] == 1){
            $requestData['down_load_url'] = trim($request->post('videoUrl'));
        }elseif ($requestData['type'] == 2){
            $requestData['down_load_url'] = trim($request->post('imageUrl'));
        }

        //验证参数
        if (empty($requestData['city']) || empty($requestData['name']) || empty($requestData['position_id']) || empty($requestData['start_time']) || empty($requestData['end_time']) || empty($requestData['type'])){
            return Json::message('请传递完整参数');
        }
//         if (!BossAdLogic::checkAdName($requestData['name'])){
//             return Json::message('广告名称已存在');
//         }
        //检查人群是否达上限
        if ($requestData['people_tag_id'] > 0){
            $fullPeople = BossAdLogic::checkPeopleLimit($requestData['position_id'], $requestData['people_tag_id']);
            if ($fullPeople){
                return Json::message('人群已达上限');
            }
        }else{
            //检查广告位下面的广告是否已达上限
            $fullCity = BossAdLogic::checkAdLimit($requestData['start_time'], $requestData['end_time'], $requestData['position_id'], $request->post('city'));
            if ($fullCity){
                return Json::message('广告位广告已达上限');
            }
        }
        $ads = new Ads();
        $ads->attributes = $requestData;
        // 验证输入内容
        if (!$ads->validate()) {
            $msg = $ads->getFirstError();
            return array($msg);
        }
        if (!$ads->save()){
            return Json::message('添加失败');
        }else{
            //添加成功，做redis缓存
            $insertId = $ads->attributes['id'];
            $insertData = Ads::find()->where(['id'=>$insertId])->indexBy('id')->asArray()->all();
            Cache::set('ads', $insertData, 0);
        }
        return Json::message('广告添加成功', 0);
    }

    //修改广告
    public function actionUpdateAd(){
        $request = $this->getRequest();
        $post = $request->post();
        $requestData = array(
            'id' => !empty($post['id']) ? intval($post['id']) : '0',
            'city' => implode(",", $request->post('city')),
            'start_time' => trim($request->post('startTime')),
            'end_time' => trim($request->post('endTime')),
            'link_url' => trim($request->post('linkUrl')),
            'type' => intval($request->post('type')),
            'video_img' => trim($request->post('videoImg')),
            'name' => trim($request->post('name')),
            'platform' => trim($request->post('platform')),
            'position_id' => intval($request->post('positionId')),
            'people_tag_id' => intval($request->post('peopleTagId')),
            'status' => intval($request->post('status',1)),
            'operator_user' => $this->userInfo['id'],
        );

        //图片、视频key值区分
        if ($requestData['type'] == 1){
            $requestData['down_load_url'] = trim($request->post('videoUrl'));
        }elseif ($requestData['type'] == 2){
            $requestData['down_load_url'] = trim($request->post('imageUrl'));
        }

        //验证参数
        if (empty($requestData['city']) || empty($requestData['name']) || empty($requestData['position_id']) || empty($requestData['start_time']) || empty($requestData['end_time']) || empty($requestData['type'])){
            return Json::message('请传递完整参数');
        }
        if(!BossAdLogic::checkAds($requestData['id'])){
            return Json::message('广告不存在');
        }else{
//             if (!BossAdLogic::checkAdName($requestData['name'], $requestData['id'])){
//                 return Json::message('广告名称已存在');
//             }
            //检查人群是否达上限
            if ($requestData['people_tag_id'] > 0){
                $fullPeople = BossAdLogic::checkPeopleLimit($requestData['position_id'], $requestData['people_tag_id']);
                if ($fullPeople){
                    return Json::message('人群已达上限');
                }
            }else{
                //检查广告位下面的广告是否已达上限
                $fullCity = BossAdLogic::checkAdLimit($requestData['start_time'], $requestData['end_time'], $requestData['position_id'], $post['city']);
                if ($fullCity){
                    return Json::message('广告位广告已达上限');
                }
            }
            $ads = new Ads();
            $ads->attributes = $requestData;
            // 验证输入内容
            if (!$ads->validate()) {
                $msg = $ads->getFirstError();
                return Json::message($msg);
            }
            $result = Ads::updateAll($requestData,['id'=>$requestData['id']]);
            if (!$result){
                return Json::message('更新失败');
            }else{
                //修改成功更新redis缓存
                $updateData = Ads::find()->where(['id'=>$requestData['id']])->indexBy('id')->asArray()->all();
                Cache::set('ads', $updateData, 0);
            }
        }
        return Json::message('修改广告成功',0);
    }

    //广告详情
    public function actionAdDetail(){
        $request = $this->getRequest();
        $adId = intval($request->post('id'));
        //验证参数
        if (empty($adId)){
            return Json::message('缺少广告id参数');
        }
        $adDetail = BossAdLogic::getAdDetail($adId);
        if (!$adDetail){
            return Json::message('广告不存在');
        }
        $this->fillUserInfo($adDetail,'operator_user');
        $adDetail = $this->keyMod(array_values($adDetail));
        $adDetail[0]['ossLink'] = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
        return Json::success($adDetail[0]);
    }

    //冻结,解冻广告
    public function actionFreezeAd(){
        $request = $this->getRequest();
        $adId = $request->post('id');
        $status = $request->post('status');
        //验证参数
        if (empty($adId)){
            return Json::message('缺少id参数');
        }
        if(!BossAdLogic::checkAds($adId)){
            return Json::message('广告不存在');
        }else{
            if ($status == 0 ||$status == 1){
                $result = Ads::updateAll(['status'=>$status], ['id'=>$adId]);
                if (!$result){
                    return Json::message('冻结/解冻失败');
                }else{
                    $updateData = Ads::find()->where(['id'=>$adId])->indexBy('id')->asArray()->all();
                    Cache::set('ads', $updateData, 0);
                }
            }else{
                return Json::message('status参数错误');
            }
        }
        return Json::message('冻结/解冻成功', 0);
    }

    //查看广告内容
    public function actionAdShow(){
        $request = $this->getRequest();
        $requestData = array(
            'position_type' => intval($request->post('positionType')),
            'people_tag_id' => intval($request->post('peopleId')),
            'start_time' => trim($request->post('startTime')),
            'end_time' => trim($request->post('endTime')),
            'city' => trim($request->post('city')),
        );
        $adShow = BossAdLogic::getAdShow($requestData);
        if (!$adShow){
            return Json::message('暂无广告信息');
        }
        $adShow = $this->keyMod($adShow);
        return Json::success($adShow);
    }

}