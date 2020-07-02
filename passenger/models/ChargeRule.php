<?php
/**
 * ChargeRule ActiveRecord Class
 *
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/8/28
 * Time: 10:21
 */
namespace passenger\models;

use common\models\ChargeRule as ChargeRulePassenger;
use common\services\traits\ModelTrait;

class ChargeRule extends  ChargeRulePassenger
{
    use ModelTrait;
}
