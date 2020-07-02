<?php
/**
 * Created by PhpStorm.
 * User: zzr
 * Date: 2018/9/5
 * Time: 17:33
 */

namespace common\services;

use yii\base\BaseObject;

class CConstant extends BaseObject
{
    const STATUS_ENABLE = 1;
    const STATUS_DISABLE = 0;
    /**
     * 返回成功或失败状态码
     */
    const ERROR_CODE = 1;
    const SUCCESS_CODE = 0;
    const ERROR_CODE_TOKEN_ERROR = 401;
    const ERROR_CODE_TOKEN_NULL = 402;
    const ERROR_CODE_SERVICE_API_METHOD_NOT_EXIST = 100001;

    /**
     * BOSS端错误码
     */


    /**
     * 订单估价状态
     */
    const TYPE_FORECAST_ORDER = 0; // 预估单类型
    const TYPE_ACTUAL_ORDER = 1; // 已经开始订单(用于区分预估单价格与最终订单产生的价格)
    /**
     * 是否被删除状态
     */
    const DEL_YES = 1;
    const DEL_NO = 0;

    /**
     * 服务类型状态定义
     */

    const SERVICE_TYPE_REAL_TIME = 1;
    const SERVICE_TYPE_RESERVE = 2;
    const SERVICE_AIRPORT_PICK_UP = 3;//接机服务
    const SERVICE_AIRPORT_DROP_OFF = 4;//送机服务
    const SERVICE_CHARTER_CAR_HALF_DAY = 5;//包车服务半日租
    const SERVICE_CHARTER_CAR_FULL_DAY = 6;//包车服务全日租

    /**
     * 渠道号
     */
    const CHANNEL_CODE_SELF = 300004;//渠道号-自营

    /**
     * 广告位position_id 定义
     */
    const SHARE_TRIP_AD_POSITION_ID = 207;
    const SERVER_EXCEPTION_TEXT = '服务器异常';

}