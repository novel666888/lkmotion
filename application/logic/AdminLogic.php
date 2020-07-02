<?php
/**
 * AdminLogic.php
 * @author: JerryZhang
 * 下午2:56
 */

namespace common\logic;

use common\models\SysUser;


class AdminLogic
{

    const I18N_CATEGORY = '';

    public static function showBatch($ids){
        return SysUser::showBatch($ids);
    }

}