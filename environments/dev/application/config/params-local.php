<?php
return [
    'BossLoginSMSCheckSwitch' => false,//boss登陆短信验证开关
    'bossSignCheckSwitch' => 1,//boss后台sign校验 0为关闭校验 1开启校验
    'secretKey' => '7g2cf84185d032de45f95q2198a57',
    'jwt-secret' =>'jwt-e8773605ba20edbd6376',
    'passwordUpdateRule' => [
        'updatePeriod' => 3600 * 24 * 90,//密码更新周期（秒）
        'startNoticeTime' => 3600 * 24 * 7,//过期前开始提醒时间（秒）
    ],
    'signCheckWhite'=>[
        'crm/feedback/export',
        'car/car/export',
        'finance/cash-active/export-charge',
        'finance/invoice/invoice-export',
        'statistics/statistics/user-statistics-export',
        'statistics/statistics/order-statistics-export',
        'statistics/statistics/coupon-statistics-export',
        'statistics/statistics/car-statistics',
        'basic/list/import',
        'notice/phone-message/add-send',
    ],
    'permissionWhiteList' => [
        'auth/auth/login',
        'auth/auth/get-login-user-info',
        'auth/auth/update',
        'site/getOssToken',
        'auth/auth/send-sms-code'
    ],
];
