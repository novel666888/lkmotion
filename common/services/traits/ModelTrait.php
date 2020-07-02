<?php
/**
 * ActiveRecord trait
 *
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/6/27
 * Time: 15:30
 */

namespace common\services\traits;

use common\services\CConstant;
use common\util\Cache;
use common\util\Common;
use yii\base\UserException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

trait  ModelTrait
{
    public static $defaultPageSize = 15;

    /**
     * 获取分页数据
     *
     * @param ActiveQuery $query
     * @param null $sort 排序
     * @param bool $returnPageInfo 是否返回分页数据
     * @return array
     */

    public static function getPagingData(ActiveQuery $query, $sort = null, $returnPageInfo = true)
    {
        $result = $query;
        $total = $query->count();
        $page = (int)\Yii::$app->getRequest()->get('page');
        if (empty($page)) {
            $page = (int)\Yii::$app->getRequest()->post('page', 1);
        }
        if ($page < 1) {
            $page = 1;
        }
        $pageSize = (int)\Yii::$app->getRequest()->get('pageSize');
        if (empty($pageSize)) {
            $pageSize = (int)\Yii::$app->getRequest()->post('pageSize', self::$defaultPageSize);
        }
        if ($pageSize < 1) {
            $pageSize = self::$defaultPageSize;
        }
        $offset = $pageSize * ($page - 1);
        $result = $result->limit((int)$pageSize)->offset($offset);
        if ($sort) {
            if(is_array($sort) && (array_key_exists('type', $sort) || array_key_exists('field', $sort))) {
                $sort = "{$sort['field']} {$sort['type']}";
            }
            $result = $result->orderBy($sort);
        }
        if (!$returnPageInfo) {
            return [
                'data' => $result->asArray()->all(),
            ];
        }
        return [
            'code' => CConstant::SUCCESS_CODE,
            'message' => empty($total) ? 'data empty!' : 'ok',
            'data' => [
                'list' => $result->asArray()->all(),
                'pageInfo' => [
                    'page' => $page,
                    'pageCount' => ceil($total / $pageSize),
                    'pageSize' => $pageSize,
                    'total' => (int)$total
                ]
            ]
        ];
    }


    /**
     * 获取匹配条件的记录数
     *
     * @param $condition
     * @return int|string
     */
    public static function countMatched($condition)
    {
        return static::find()->where($condition)->count();

    }

    /**
     * 获取匹配条件的某一列
     *
     * @param $condition
     * @param $column_name
     * @return mixed
     */
    public static function pluck($condition, $column_name)
    {
        return static::find()->where($condition)->select($column_name)->column();

    }

    /**
     * showBatch --
     * @author JerryZhang
     * @param $id
     * @param int $from_cache_type (0:缓存-不读不写1:缓存-不读只写 2:缓存-即读又写)
     * @return array
     * @cache Yes
     */
    public static function showBatch($id, $from_cache_type = 0)
    {

        if (empty($id)) {
            return [];
        }

        $data = [];
        $data_cache = [];
        $data_db = [];
        !is_array($id) && $id = [$id];
        $from_cache_type == 2 && $data_cache = ArrayHelper::index(Cache::get(self::getTableSchema()->fullName, $id), 'id');
        $ids_diff = array_diff($id, array_keys($data_cache));
        if (!empty($ids_diff)) {
            $query = self::find();
            $query->select('*');
            $query->andWhere(['id' => $ids_diff]);
            $data_db = $query->asArray()->all();
            $data_db = ArrayHelper::index($data_db, 'id');

            if ($from_cache_type > 0 && !empty($data_db)) {
                Cache::set(self::getTableSchema()->fullName, $data_db);
            }
        }

        $data_merge = $data_cache + $data_db;
        foreach ($id as $v) {
            $data[$v] = isset($data_merge[$v]) ? $data_merge[$v] : [];
        }

        return $data;
    }

    /**
     * add --
     * @author JerryZhang
     * @param $data
     * @param bool $use_common_cache
     * @return bool
     * @cache Yes
     */
    public static function add($data, $use_common_cache = false)
    {
        $query = new self();
        $query->setAttributes($data);

        $res = $query->save();

        if ($res) {
            self::showBatch($query->id, $use_common_cache);
        }

        return $query->id;
    }

    /**
     * edit --
     * @author JerryZhang
     * @param $id
     * @param $data
     * @param bool $use_common_cache
     * @return bool
     * @cache Yes
     */
    public static function edit($id, $data, $use_common_cache = false)
    {

        $query = self::find()->where(['id' => $id])->one();
        $query->setAttributes($data);

        $res = $query->save();

        if ($res) {
            self::showBatch($id, $use_common_cache);
        }

        return $res;
    }

    /**
     * remove --
     * @author JerryZhang
     * @param $id
     * @return false|int
     * @cache Yes
     */
    public static function remove($id)
    {
        $query = self::find()->where(['id' => $id])->one();

        $res = $query->delete();

        if ($res) {
            Cache::delete(self::getTableSchema()->fullName, $id);
        }

        return $res;
    }

    /**
     * lists --
     * @author JerryZhang
     * @param $params
     * @param array $pager
     * @param int $from_cache_type
     * @return array|\yii\db\ActiveRecord[]
     * @cache Yes
     */
    public static function lists($params, $pager = [], $from_cache_type = 0)
    {
        $query = self::get_query($params);
        if (!empty($pager['page']) && !empty($pager['page_size'])) {
            $query->limit($pager['page_size']);
            $query->offset(($pager['page'] - 1) * $pager['page_size']);
        }

        $query->select('id');
        $ids = $query->asArray()->column();

        $list = self::showBatch($ids, $from_cache_type);

        return $list;
    }

    /**
     * get_total_count --
     * @author JerryZhang
     * @param $params
     * @return int|string
     * @cache No
     */
    public static function get_total_count($params)
    {
        $query = self::get_query($params);

        return intval($query->count());
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = self::find();
        if (isset($params['is_delete'])) {
            $query->andWhere(['is_delete' => $params['is_delete']]);
        }
        if (!empty($params['city_code'])) {
            $query->andWhere(['city_code' => $params['city_code']]);
        }
        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }

        $query->orderBy(['id' => SORT_DESC]);

        return $query;
    }

    /**
     * * ensure get a record or throw exception
     *
     * @param $condition
     * @param bool $throwException
     * @return mixed
     * @throws UserException
     */

    public static function getOne($condition, $throwException = true)
    {
        $model = static::findOne($condition);
        if (!$model && $throwException) {
            throw new UserException('Cannot find active record!',1001);
        }
        return $model;
    }

    /**
     * get last record according conditions
     *
     * @param $condition
     * @param bool $throwException
     * @return mixed | \yii\db\ActiveRecord
     * @throws UserException
     */

    public static function getLastOne($condition, $throwException = true)
    {
        /**@var */
        $model = static::find()->where($condition)->limit(1)->orderBy('id desc')->one();
        if (!$model && $throwException) {
            throw new UserException('Cannot find active record!');
        }
        return $model;
    }

}