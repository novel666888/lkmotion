<?php

namespace application\modules\coupon\controllers;

use application\controllers\BossBaseController;
use application\modules\order\models\SysUser;
use common\logic\LogicTrait;
use common\logic\PeopleTagTrait;
use common\models\Activities;
use common\models\CouponTask;
use common\models\PeopleTag;
use common\services\traits\ModelTrait;
use common\util\Common;
use common\util\Json;

/**
 * Site controller
 */
class PeopleTagController extends BossBaseController
{
    use ModelTrait, PeopleTagTrait;
    use LogicTrait;

    /**
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        $request = \Yii::$app->request;
        $tagName = trim(strval($request->post('tagName')));
        $query = PeopleTag::find();
        if ($tagName) {
            $query->where(['like', 'tag_name', $tagName]);
        }
        $tagType = intval($request->post('tagType'));
        if ($tagType) { // 1乘客2司机
            $query->andWhere(['tag_type' => $tagType]);
        }
        $createdUser = trim(strval($request->post('createdUser')));
        if ($createdUser) {
            $admin = SysUser::findOne(['username' => $createdUser]);
            if ($admin) {
                $query->andWhere(['operator_id' => $admin->id]);
            }
        }
        $tags = self::getPagingData($query, ['field' => 'create_time', 'type' => 'desc']);

        LogicTrait::fillUserInfo($tags['data']['list']);
        $this->getLinkNumbers($tags['data']['list']);

        // 返回数据
        return $this->asJson(Common::key2lowerCamel($tags));
    }

    /**
     * @return array
     */
    public function actionStore()
    {
        $request = \Yii::$app->request;

        $tagName = trim($request->post('tagName'));
        $id = intval($request->post('id'));
        if (!$tagName) {
            return Json::message('人群名称不能为空');
        }
        $hasOne = PeopleTag::findOne(['tag_name' => $tagName]);
        if ($hasOne && $hasOne->id != $id) {
            return Json::message('人群名称重复');
        }

        $tagClass = new PeopleTag();

        $tagTypes = $tagClass->tagTypes;
        $tagType = intval($request->post('tagType'));
        if (!isset($tagTypes[$tagType])) {
            return Json::message('暂不支持该人群类别');
        }

        $conditions = $request->post('conditions');
        if ($tagType == 2) {
            $conditions = $this->formatPlates($conditions);
        }
        if ($id) {
            $peopleTag = PeopleTag::findOne(['id' => $id]);
            if (!$peopleTag) {
                return Json::message('参数异常');
            }
        } else {
            $peopleTag = $tagClass;
        }
        $num = $num = $this->queryNumber($conditions, $tagType);
        $peopleTag->tag_name = $tagName;
        $peopleTag->tag_type = $tagType;
        $peopleTag->tag_conditions = $conditions;
        $peopleTag->tag_number = $num;
        $peopleTag->operator_id = $this->userInfo['id'];

        if (!$peopleTag->save()) {
            return Json::message('保存失败');
        }
        if (!$peopleTag->tag_no) {
            $peopleTag->tag_no = 'RD' . str_pad(strval($peopleTag->id), 4, '0', STR_PAD_LEFT);
            $peopleTag->save();
        }

        return Json::message('操作成功', 0);

    }

    /**
     * @return array
     */
    public function actionQueryQty()
    {
        $request = \Yii::$app->request;
        $tagClass = new PeopleTag();

        $tagTypes = $tagClass->tagTypes;
        $tagType = intval($request->post('tagType'));
        if (!isset($tagTypes[$tagType])) {
            return Json::message('暂不支持该人群类别');
        }
        $conditionsRaw = $request->post('conditions');
        $num = $this->queryNumber($conditionsRaw, $tagType);

        return Json::success(['num' => $num]);
    }


    private function queryNumber($conditionsRaw, $tagType = 1)
    {
        $conditions = json_decode($conditionsRaw);
        $num = 0;
        if ($tagType == 1) {
            $passengerIds = $this->filterPassengerIds($conditions);
            $num = count($passengerIds);
        } else {
            $num = count($conditions);
        }
        return $num;
    }

    /**
     * @return array
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete()
    {
        $request = \Yii::$app->request;
        $id = intval($request->post('id'));
        if (!$id) {
            return Json::message('参数异常');
        }
        $peopleTag = PeopleTag::findOne(['id' => $id]);
        if (!$peopleTag) {
            return Json::message('参数异常');
        }
        if (!$peopleTag->delete()) {
            return Json::message('操作失败');
        }
        return Json::message('操作成功', 0);

    }

    private function formatPlates($str)
    {
        $str = str_replace(' ', '', $str);
        $str = str_replace(["\r", "\n", "\t", '，'], ',', $str);
        return json_encode(array_filter(explode(',', $str)), 256);
    }

    /**
     * 非大量活动情况下的标签引用读取
     * 读取数据库, 性能优化应该改为触发更新!!!
     *
     * @param $list
     */
    private function getLinkNumbers(&$list)
    {
        $ids = array_column($list, 'id');
        if (!$ids) {
            return;
        }
        // 获取活动引用情况
        $time = date('Y-m-d H:i:s');
        $activityLinks = Activities::find()
            ->select('count(id) as total,people_tag')
            ->where(['in', 'people_tag', $ids])
            ->andWhere(['status' => 1])
            ->andWhere(['<', 'enable_time', $time])
            ->andWhere(['>', 'expire_time', $time])
            ->groupBy('people_tag')
            ->asArray()
            ->all();
        $linkMap = array_column($activityLinks, 'total', 'people_tag');
        if ($linkMap) {
            foreach ($list as &$item) {
                isset($linkMap[$item['id']]) && $item['link_number'] = strval($item['link_number'] + $linkMap[$item['id']]);
            }
        }
        // 获取发券引用情况
        $couponTasks = CouponTask::find()
            ->select('count(id) as total,task_tag')
            ->where(['in', 'task_tag', $ids])
            ->andWhere(['<', 'task_status', 2])
            ->andWhere(['is_cancel' => 0])
            ->groupBy('task_tag')
            ->asArray()
            ->all();
        $linkMap = array_column($couponTasks, 'total', 'task_tag');
        if ($linkMap) {
            foreach ($list as &$item) {
                isset($linkMap[$item['id']]) && $item['link_number'] = strval($item['link_number'] + $linkMap[$item['id']]);
            }
        }
    }
}
