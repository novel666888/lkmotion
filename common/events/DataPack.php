<?php
/**
 * Created by PhpStorm.
 * User: xujie
 * Date: 18-8-24
 * Time: 下午2:26
 */

namespace common\events;

use yii\base\Event;

class DataPack extends Event
{
    public $identity = null;
    public $orderId = null;
    public $extInfo = null;
}