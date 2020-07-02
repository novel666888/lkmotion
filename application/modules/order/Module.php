<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/14
 * Time: 11:01
 */
namespace application\modules\order;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'application\modules\order\controllers';

    public function init()
    {
        parent::init();
        $this->params = require __DIR__.'/config/params.php';
    }
}
