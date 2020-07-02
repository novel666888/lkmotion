<?php

namespace driver\modules\basic\controllers;

use common\controllers\BaseController;
use common\models\Jpush;
use common\models\ListArray;
use common\util\Common;
use common\util\Json;

/**
 * Class ListController
 * @package driver\modules\basic\controllers
 */
class ListController extends BaseController
{

    /**
     * 城市列表
     *
     * @return array
     */
    public function actionCity()
    {
        $listModel = new ListArray();
        $list = $listModel->getCityList(0);
        foreach ($list as &$item) {
            if (isset($item['city_longitude_latitude'])) {
                unset($item['city_longitude_latitude']);
            }
        }
        return Json::success(['list' => Common::key2lowerCamel($list)]);
    }

    /**
     * 生成极光别名
     * @return array
     */
    public function actionJpushAlias()
    {
        return Json::success(['alias' => Jpush::genAlias()]);
    }

    private function getId($key)
    {
        $request = \Yii::$app->request;
        $value = intval($request->get($key));
        if (!$value) {
            $value = intval($request->post($key));
        }
        return $value;
    }

}
