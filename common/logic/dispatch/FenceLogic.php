<?php
namespace common\logic\dispatch;

use common\models\FenceInfo;
use common\api\FenceApi;
use common\services\traits\ModelTrait;
use common\models\City;
use common\util\Validate;
/**
 * 围栏Logic
 * 
 * ErrorCode
 * 10001 => fence_name为空
 * 10002 => city_code为空
 * 10003 => valid_start_time为空
 * 10004 => valid_end_time为空
 * 10005 => 围栏已存在(名称重复)
 * 10006 => 围栏有效期不正确(valid_end_time <= valid_start_time)
 * 10007 => points为空
 * 10008 => points不正确
 * 10009 => 数据不存在
 * 10010 => 缺少id
 * 10011 => 启用状态的围栏不可删除
 * 10000 => 操作失败
 */
class FenceLogic
{
    use ModelTrait;

    public function add($data)
    {
        $model = new Validate($data);

        $this->_validate($model, __FUNCTION__);

        if ($model->hasErrors()) {
            return $model->getFirstError();
        }

        $fence_info = new FenceInfo(['scenario' => 'insert']);
        $fence_info->attributes = $data;
        if (!$model->validate()) {
            return $model->getFirstError();
        }

        $fence_api = new FenceApi();

        $java_result = $fence_api->meta([
            'name' => $data['fence_name'],
            'points' => $data['points'],
            'validTime' => $data['valid_end_time'],
            'enable' => $data['is_deny'] == 0 ? true : false,
        ]);

        if ($java_result === false) {
            $_error = $fence_api->getError();
            if ($_error) {
                return $_error;
            }
            return 10000;
        }
        $fence_info->gid = $java_result;
        try {
            $result = $fence_info->save(false);
            if ($result) {
                return true;
            }
            return 10000;
        } catch (\Exception $e) {
            // 写入数据库失败则删除java端的围栏
            $result = $fence_api->delete($java_result['gid']);
            return 10000;
        }
    }

    public function update($id, $data)
    {
        $model = new Validate($data);
        $this->_validate($model, __FUNCTION__);

        if ($model->hasErrors()) {
            return $model->getFirstError();
        }
        
        // 更改状态
        $info = $this->info($id);
        if (!$info) {
            return 10009;
        }

        switch ($info['is_deny']) {
            case 0:
                $update_data = [
                    'is_deny' => $data['is_deny']
                ];
                $fence_info = [
                    'name' => $info['fence_name'],
                    'points' => $info['points'],
                    'validTime' => date('Y-m-d', strtotime($info['validTime'])),
                ];
                break;
            case 1:
                $update_data = [
                    'fence_name' => $data['fence_name'],
                    'valid_start_time' => $data['valid_start_time'],
                    'valid_end_time' => $data['valid_end_time'],
                    'is_deny' => $data['is_deny']
                ];
                $fence_info = [
                    'name' => $data['fence_name'],
                    'points' => $data['points'],
                    'validTime' => date('Y-m-d', strtotime($data['valid_end_time']))
                ];
                break;
        }

        $fence = new FenceInfo();
        $fence_api = new FenceApi();

        $translation = FenceInfo::getDb()->beginTransaction();
        try {
            $fence->updateAll($update_data, ['id' => $data['id']]);
        } catch (\Exception $e) {
            $translation->rollBack();
            return 10000;
        }
        
        if ($info['is_deny'] != $data['is_deny']) {
            $result = $this->changeStatus($info['id'], $data['is_deny']);
            if ($result !== true) {
                $translation->rollBack();
                return 10000;
            }
        }

        $fence_info['gid'] = $info['gid'];

        $update_result = $fence_api->meta($fence_info);
            
        if ($update_result === true) {
            $translation->commit();
            return true;
        } elseif ($update_result === false) {
            $translation->rollBack();
            $_error = $fence_api->getError();
            
            if ($_error) {
                return $_error;
            }
            return 10000;
        }
    }

    public function delete($id)
    {
        if (empty($id)) {
            return 10010;
        }
        $info = FenceInfo::find()->where(['id' => $id])
            ->select(['gid', 'is_deny'])->asArray()->one();

        if (!$info) {
            return 10009;
        }
        if ($info['is_deny'] == 0) {
            return 10011;
        }
        $translation = FenceInfo::getDb()->beginTransaction();

        $fence_api = new FenceApi();
        $fence = new FenceInfo();

        try {
            $fence->updateAll(['is_delete' => 1], ['id' => $id]);
        } catch (\Exception $e) {
            $translation->rollBack();
            return 10000;
        }

        $update_result = $fence_api->delete($info['gid']);

        if ($update_result === true) {
            $translation->commit();
            return true;
        } elseif ($update_data === false) {
            $translation->rollBack();
            $_error = $fence_api->getError();
            if ($_error) {
                return $_error;
            }
            return 10000;
        }
    }

    public function changeStatus($id, $is_deny = 0)
    {
        if (empty($id)) {
            return 10010;
        }
        $info = FenceInfo::find()->where(['id' => $id])
            ->select(['gid'])->asArray()->one();

        if (!$info) {
            return 10009;
        }

        $fence_api = new FenceApi();
        $fence = new FenceInfo();

        $translation = FenceInfo::getDb()->beginTransaction();
        try {
            $fence->updateAll(['is_deny' => $is_deny], ['id' => $id]);
        } catch (\Exception $e) {
            $translation->rollBack();
            return 10000;
        }
        $enable = $is_deny == 0 ? true : false;
        $update_result = $fence_api->changeStatus($info['gid'], $enable);
        if ($update_result === true) {
            $translation->commit();
            return true;
        } elseif ($update_data === false) {
            $translation->rollBack();
            $_error = $fence_api->getError();
            if ($_error) {
                return $_error;
            }
            return 10000;
        }
    }

    public function lists($request_data, $is_page = 1)
    {
        $fence_name = isset($request_data['fence_name']) ? $request_data['fence_name'] : '';
        $city_code = isset($request_data['city_code']) ? $request_data['city_code'] : '';
        $valid_start_time = isset($request_data['valid_start_time']) ? $request_data['valid_start_time'] : '';
        $valid_end_time = isset($request_data['valid_end_time']) ? $request_data['valid_end_time'] : '';
        $is_deny = isset($request_data['is_deny']) ? $request_data['is_deny'] : '';

        $query = FenceInfo::find();
        $query = $query->where(['is_delete' => 0]);
        if ($fence_name) {
            $query = $query->andWhere(['fence_name' => $fence_name]);
        }
        if ($city_code) {
            $query = $query->andWhere(['city_code' => $city_code]);
        }
        if ($valid_start_time) {
            $query = $query->andWhere(['>=', 'valid_start_time', $valid_start_time]);
        }
        if ($valid_end_time) {
            $query = $query->andWhere(['<=', 'valid_end_time', $valid_start_time]);
        }
        if ($is_deny !== '') {
            $query = $query->andWhere(['is_deny' => $is_deny]);
        }

        $query = $query->select([
            'id', 'gid', 'fence_name', 'city_code', 'valid_start_time', 'valid_end_time', 'is_deny'
        ]);

        if ($is_page) {
            $lists = static::getPagingData($query, ['field' => 'city_code', 'type' => 'asc'], true);
        } else {
            $_lists = $query->orderBy(['city_code' => SORT_ASC])->asArray()->all();
            $lists['data']['list'] = $_lists;
        }
        $lists['data']['list'] = $this->_autoChangeStatus($lists['data']['list']);

        $fence_api = new FenceApi();

        $fence_lists = $fence_api->searchByGids(array_column($lists['data']['list'], 'gid'));

        if (!is_array($fence_lists)) {
            return 10009;
        }
        $fence_lists = array_column($fence_lists, null, 'gid');
        foreach ($lists['data']['list'] as $_k => $_v) {
            if (!isset($fence_lists[$_v['gid']])) {
                unset($lists['data']['list'][$_k]);
            } else {
                $lists['data']['list'][$_k] = array_merge($_v, [
                    'validTime' => $fence_lists[$_v['gid']]['validTime'],
                    'center' => $fence_lists[$_v['gid']]['center'],
                    'points' => $fence_lists[$_v['gid']]['points'],
                    'radius' => $fence_lists[$_v['gid']]['radius'],
                    'repeat' => $fence_lists[$_v['gid']]['repeat']
                ]);
            }
        }

        if ($lists['data']['list']) {
            $city_codes = array_unique(array_filter(array_column($lists['data']['list'], 'city_code')));
            if ($city_codes) {
                $city_lists = City::find()
                    ->where(['city_code' => $city_codes])
                    ->select(['city_code', 'city_name', 'city_longitude_latitude'])->asArray()->all();
                if ($city_lists) {
                    $city_lists = array_column($city_lists, null, 'city_code');
                    foreach ($lists['data']['list'] as $_k => $_v) {
                        if (isset($city_lists[$_v['city_code']])) {
                            $lists['data']['list'][$_k]['city_name'] = $city_lists[$_v['city_code']]['city_name'];
                            $lists['data']['list'][$_k]['city_longitude_latitude'] = $city_lists[$_v['city_code']]['city_longitude_latitude'];
                        }
                    }
                }
            }
        }
        $lists['data']['list'] = array_values($lists['data']['list']);
         return $lists['data'];
    }

    public function info($id)
    {
        $info = FenceInfo::find()->where(['id' => $id, 'is_delete'=>0])->select([
            'id', 'gid', 'fence_name', 'city_code',
            'valid_start_time', 'valid_end_time', 'is_deny'
        ])->asArray()->one();

        if (empty($info)) {
            return 10009;
        }
        $city_info = City::find()
            ->where(['city_code' => $info['city_code']])
            ->select(['city_name', 'city_longitude_latitude'])->asArray()->one();
        $info = array_merge($city_info, $info);

        $fence_api = new FenceApi();

        $fence_lists = $fence_api->searchByGids([$info['gid']]);
        
        if (!is_array($fence_lists)) {
            return 10009;
        }
        
        $fence_info = $fence_lists[0];

        $info = array_merge($info, [
            'validTime' => $fence_info['validTime'],
            'center' => $fence_info['center'],
            'points' => $fence_info['points'],
            'radius' => $fence_info['radius'],
            'repeat' => $fence_info['repeat'],
        ]);

        return $info;
    }

    /**
     * 自动修正围栏状态
     */
    private function _autoChangeStatus($data)
    {
        $now_date = date('Y-m-d H:i:s');
        foreach ($data as $_k => $_v) {
            if ($_v['valid_end_time'] <= $now_date && $_v['is_deny'] != 1) {
                $_result = $this->changeStatus($_v['id'], 1);
                if ($_result === true) {
                    $data[$_k]['is_deny'] = 1;
                }
            }
        }
        return $data;
    }

    private function _validate($model, $scenario = 'default')
    {
        $rules = [
            ['fence_name', 'required', 'message' => 10001],
            ['fence_name', function ($attribute, $params) use ($model, $scenario) {
                $query = FenceInfo::find();
                $query = $query->where(['fence_name' => $model->fence_name, 'is_delete' => 0]);
                if ($scenario == 'update') {
                    $query = $query->andWhere(['!=', 'id', $model->id]);
                }
                $_info = $query->select(['id'])->asArray()->one();
                if ($_info) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10005]],
            ['city_code', 'required', 'message' => 10002],
            ['valid_start_time', 'required', 'message' => 10003],
            ['valid_end_time', 'required', 'message' => 10004],
            ['valid_start_time', function ($attribute, $params) use ($model) {
                if ($model->valid_end_time <= $model->valid_start_time) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10006]],
            ['points', 'required', 'message' => 10007],
            ['points', function ($attribute, $params) use ($model) {
                if (count(explode(';', $model->points)) <= 2) {
                    $model->addError($attribute, $params['message']);
                }
            }, 'params' => ['message' => 10008]],
        ];
        switch ($scenario) {
            case 'add':
                break;
            case 'update':
                $rules = array_merge($rules, [
                    ['id', 'required', 'message' => 10010],
                ]);
                break;
        }
        foreach ($rules as $_k => $_v) {
            $model->addRule($_v[0], $_v[1], array_slice($_v, 2));
        }
        $model->validate();
    }
}