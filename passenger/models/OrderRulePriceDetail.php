<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/4
 * Time: 13:23
 */
namespace passenger\models;

use common\services\traits\ModelTrait;

class OrderRulePriceDetail extends \common\models\OrderRulePriceDetail
{
    use ModelTrait;

    const CATEGORY_FORECAST = 0;
    const CATEGORY_ACTUAL = 1;
}