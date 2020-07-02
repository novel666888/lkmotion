<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id'                  => 'app-passenger',
    'basePath'            => dirname(__DIR__),
    'controllerNamespace' => 'passenger\controllers',
    'language'            => 'zh-CN',
    'timeZone'            => 'PRC',
    'bootstrap'           => ['log'],
    'modules'             => [
        'activities' => 'passenger\modules\activities\Module',
        'user'    => 'passenger\modules\user\Module',
        'service' => 'passenger\modules\service\Module',
        'order'   => [
            'class' => 'passenger\modules\order\Module',
        ]
    ],
    'components'          => [
        'request'      => [
            'csrfParam'            => '_csrf-passenger',
            'enableCsrfValidation' => false, // 不开启csrf
            'parsers'              => [
                'application/json' => 'yii\web\JsonParser',
                'text/json'        => 'yii\web\JsonParser',
            ],
        ],
        'user'         => [
            'identityClass'   => 'common\models\User',
            'enableAutoLogin' => true,
            'identityCookie'  => ['name' => '_identity-backend', 'httpOnly' => true],
        ],
        'session'      => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-passenger',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logFile' => '@passenger/runtime/logs/' . date('Ymd') . '.log',
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager'   => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
            ],
        ],
        'i18n'         => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                ],
            ],
        ],
    ],
    'params'              => $params,
];
