<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '',
        ],
    ],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $debugOffUrl = [ //高频请求接口
        '/order/work/add-points',
        '/order/work/notify',
    ];
    // 高频请求接口不记录日志和debug
    if (in_array($_SERVER['REQUEST_URI'], $debugOffUrl)) {
        $config['bootstrap'] = [];
    } else {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => 'yii\debug\Module',
            'allowedIPs' => ['127.0.0.1', '::1', '192.168.*.*', '*.*.*.*'],
        ];
    }
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
