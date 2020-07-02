<?php
namespace common\api;

use common\services\YesinCarHttpClient;
use yii\helpers\ArrayHelper;
use PHPUnit\Framework\Constraint\ArrayHasKeyTest;
use yii\httpclient\Client;

class FenceApi
{
    private $http_client;
    private $_error = '';
    public function __construct()
    {
        $server_url = ArrayHelper::getValue(\Yii::$app->params, 'api.map.serverName');
        $this->http_client = new YesinCarHttpClient([
            'serverURI' => $server_url
        ]);
    }

    /**
     * 新增/编辑围栏
     */
    public function meta($data)
    {
        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.map.method.fenceMeta');
        $result = $this->http_client->post($method_path, $data);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'fenceApi/meta return data');

        if ($result['code'] === 0) {
            if ($data['gid']) {
                return true;
            }
            return $result['data']['gid'];
        } elseif ($result['code'] === 500) {
            return false;
        }
        $this->_error = $result['message'];
        return false;
    }

    public function delete($gid)
    {
        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.map.method.fenceDelete');
        $method_path = rtrim($method_path, '/') . '/' . $gid;

        $result = $this->http_client->get($method_path, [], 2);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'fenceApi/delete return data');
        if ($result['code'] === 0) {
            return true;
        } elseif ($result['code'] === 500) {
            return false;
        }
        $this->_error = $result['message'];
        return false;
    }

    /**
     * 搜索围栏列表
     */
    public function search($data = [])
    {
        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.map.method.fenceSearch');

        $result = $this->http_client->get($method_path, $data, 2);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'fenceApi/search return data');
        if ($result['code'] === 0) {
            return $result['data'];
        } elseif ($result['code'] === 500) {
            return false;
        }
        $this->_error = $result['message'];
        return false;
    }

    public function searchByGids($gids)
    {
        $data['gids'] = is_array($gids) ? implode(',', $gids) : implode(',', [$gids]);
        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.map.method.fenceSearchByGids');

        $result = $this->http_client->get($method_path, $data, 2);
        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'fenceApi/searchBygids return data');
        if ($result['code'] === 0) {
            return $result['data'];
        } elseif ($result['code'] === 500) {
            return false;
        }
        $this->_error = $result['message'];
        return false;
    }

    public function changeStatus($gid, $enable = true)
    {
        if (empty($gid)) {
            return true;
        }
        $method_path = ArrayHelper::getValue(\Yii::$app->params, 'api.map.method.fenceChangeStatus');

        $result = $this->http_client->post($method_path, ['gid' => $gid, 'enable' => $enable]);

        \Yii::info(json_encode($result, JSON_UNESCAPED_UNICODE), 'fenceApi/changeStatus return data');
        if ($result['code'] === 0) {
            return true;
        } elseif ($result['code'] === 500) {
            return false;
        }
        $this->_error = $result['message'];
        return false;
    }

    public function getError()
    {
        return $this->_error;
    }
}