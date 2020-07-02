<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/11/24
 * Time: 15:39
 */
namespace passenger\services;

use common\models\FenceInfo;
use common\services\CConstant;

trait FenceTrait
{
    /**
     * 检测围栏是否可用 false 不启用检测围栏  true 启用检测围栏
     *
     * @param $cityCode
     * @return bool
     */
    protected function checkFenceStatus($cityCode)
    {

        $fenceCnt = FenceInfo::find()->where(['city_code'=>$cityCode])
            ->andWhere(['is_delete'=>CConstant::DEL_NO])
            ->select('id')
            ->count();
        //无围栏,不检测
        if(empty($fenceCnt)){
            return false;
        }
        $disabledFenceCnt= FenceInfo::find()->where(['city_code'=>$cityCode])
            ->andWhere(['is_deny'=>1,'is_delete'=>CConstant::DEL_NO])
            ->count();
        //所有围栏禁用,不检测
        if($disabledFenceCnt == $fenceCnt){
            return false;
        }
        $enabledFenceCnt =FenceInfo::find()->where(['city_code'=>$cityCode])
            ->andWhere(['is_delete'=>CConstant::DEL_NO])
            ->andWhere(['is_deny'=>0])
            ->count();
        $invalidFenceCnt = FenceInfo::find()->where(['city_code'=>$cityCode])
            ->andWhere(['<','valid_end_time',date('Y-m-d H:i:s')])
            ->andWhere(['is_delete'=>CConstant::DEL_NO])
            ->andWhere(['is_deny'=>0])
            ->count();
        //启用的围栏全部过期,不检测
        if($invalidFenceCnt == $enabledFenceCnt){
            return false;
        }

        return true;
    }

}