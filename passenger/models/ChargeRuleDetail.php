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

use common\models\ChargeRuleDetail as ChargeRuleDetailPassenger;
use common\services\traits\ModelTrait;

class ChargeRuleDetail extends  ChargeRuleDetailPassenger
{
    use ModelTrait;
}
