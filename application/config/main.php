<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-boss',
    'basePath' => dirname(__DIR__),
    'homeUrl' => '/',
    'controllerNamespace' => 'application\controllers',
    'language' => 'zh-CN',
    'timeZone' => 'PRC',
    'bootstrap' => ['log'],
    'modules' => [
//        'user' => [
//            'class' => 'backend\modules\user\Module',
//        ],
        'auth' => [
            'class' => 'application\modules\auth\Module'
        ],
        'basic' => [
            'class' => 'application\modules\basic\Module'
        ],
        'car' => [
            'class' => 'application\modules\car\Module'
        ],
        'charge' => [
            'class' => 'application\modules\charge\Module'
        ],
        'crm' => [
            'class' => 'application\modules\crm\Module'
        ],
        'coupon' => [
            'class' => 'application\modules\coupon\Module'
        ],
        'driver' => [
            'class' => 'application\modules\driver\Module'
        ],
        'order'=>[
            'class' => 'application\modules\order\Module'
        ],
        'dispatch' => [
            'class' => 'application\modules\dispatch\Module',
        ],
		'ad' => [
            'class' => 'application\modules\ad\Module',
        ],
		'finance' => [
            'class' => 'application\modules\finance\Module',
        ],
		'notice' => [
            'class' => 'application\modules\notice\Module',
        ],
        'permission' => [
            'class' => 'application\modules\permission\Module',
        ],
        'sysuser' => [
            'class' => 'application\modules\user\Module',
        ],
        'user' => [
            'class' => 'driver\modules\user\Module',
        ],
        'statistics' => [
            'class' => 'application\modules\statistics\Module',
        ],
        'ucenter' => [
            'class' => 'driver\modules\ucenter\Module',
        ],
        'activities' => [
            'class' => 'application\modules\activities\Module'
        ],
    ],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-backend',
            'enableCsrfValidation' => false, // 不开启csrf
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'text/json' => 'yii\web\JsonParser',
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@application/runtime/logs/' . date('Ymd') . '.log',
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
            ],
        ],
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                ],
            ],
        ],
    ],
    'params' => $params,
];
