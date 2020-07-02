<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/29
 * Time: 10:47
 */
namespace application\modules\order\models;

use common\services\traits\ModelTrait;
use common\models\OrderUseCoupon as OrderUseCouponBoss;

class OrderUseCoupon extends  OrderUseCouponBoss
{
    use ModelTrait;
}