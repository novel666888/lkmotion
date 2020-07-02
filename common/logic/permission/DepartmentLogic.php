<?php

/**
 * 部门Logic
 * 
 * @author guolei <guolei@lkmotion.com>
 * @category 部门的公共操作方法
 */
namespace common\logic\permission;

use common\models\SysDepartment;
use common\services\traits\ModelTrait;

/**
 * 部门Logic
 * 
 * ErrorMsg
 * 10001 => 部门名称为空
 * 10002 => 部门已存在
 * 10003 => id为空
 */
class DepartmentLogic
{
    use ModelTrait;
    /**
     * 新增部门
     *
     * @param array $data 新增的部门数据
     *
     * @return bool
     */
    public function add($data)
    {
        $department = new SysDepartment(['scenario' => 'insert']);
        $department->attributes = $data;
        if (!$department->validate()) {
            return $department->getFirstError();
        }
        try {
            $result = $department->save();
            if ($result) {
                return true;
            }
            return 10000;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'DepartmentLogic/add');
            return 10000;   // 操作失败
        }
    }

    /**
     * 更新部门信息
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public static function update($id, $data)
    {
        if ($id == 1) {
            return 10000;
        }
        $department = new SysDepartment(['scenario' => 'update']);
        $department->attributes = $data;

        if (!$department->validate()) {
            return $department->getFirstError();
        }

        try {
            $department->setOldAttribute('id', $department->id);
            $department->save();
            return true;
        } catch (\Exception $e) {
            \Yii::error($e->getMessage(), 'DepartmentLogic/update');
            return 10000;   // 操作失败
        }
    }

    /**
     * 获取单个部门的信息.
     *
     * @param [type] $requestData
     */
    public function info($requestData = [])
    {
        if (empty($requestData)) {
            return [];
        }
        $id = isset($requestData['id']) ? $requestData['id'] : '';
        if ($id == 1) {
            return [];
        }
        $department_name = isset($requestData['department_name']) ? $requestData['department_name'] : '';

        $query = SysDepartment::find();
        if ($id) {
            $query = $query->where(['id' => $id]);
        }
        if ($department_name) {
            $query = $query->where(['department_name' => $department_name]);
        }

        $info = $query->select(['id', 'department_name'])->asArray()->one();
        return $info;
    }

    /**
     * 获取部门列表.
     *
     * @param array $requestData    要查询的条件数组
     * @param int $is_page 是否分页 1: 分页 0: 不分页
     */
    public function lists($requestData = [], $is_page = 1)
    {
        $department_name = isset($requestData['department_name']) ? $requestData['department_name'] : '';

        $query = SysDepartment::find();
        $query = $query->where(['!=', 'id', 1]);
        if ($department_name) {
            $query = $query->andWhere(['department_name' => $department_name]);
        }
        $query = $query->select(['id', 'department_name']);
        if ($is_page) {
            $lists = static::getPagingData($query, ['field' => 'id', 'type' => 'desc'], true);
        } else {
            $list = $query->asArray()->orderBy(['id' => SORT_DESC])->all();
            $lists['data']['list'] = $list;
        }

        return $lists['data'];
    }
}
