<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/27
 * Time: 17:31
 */
namespace passenger\models;
use common\models\Order as OrderPassenger;
use common\services\traits\ModelTrait;

class Order extends OrderPassenger
{
    use  ModelTrait;
}