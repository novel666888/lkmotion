<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/27
 * Time: 17:31
 */
namespace passenger\models;
use common\models\OrderCancelRecord as OrderCancelRecordPassenger;
use common\services\traits\ModelTrait;

class OrderCancelRecord extends OrderCancelRecordPassenger
{
    use  ModelTrait;
}