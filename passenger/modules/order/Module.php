<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/2
 * Time: 10:55
 */
namespace passenger\modules\order;

class Module extends \yii\base\Module
{
    public $controllerNamespace = 'passenger\modules\order\controllers';

    public function init()
    {
        parent::init();
        $this->params = require __DIR__.'/config/params.php';


        // custom initialization code goes here
    }
}
