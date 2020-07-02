<?php

return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.68.123;port=3306;dbname=lkmotion_1101',
            'username' => 'dev1101',
            'password' => 'dev1101',
            'charset' => 'utf8',
            'tablePrefix' => 'tbl_',
            'attributes' => [
                // use a smaller connection timeout
//                \PDO::ATTR_STRINGIFY_FETCHES=>false,
//                \PDO::ATTR_EMULATE_PREPARES=>false
            ],
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'useFileTransport' => false,
            'transport'=>[
                'class' => 'Swift_SmtpTransport',
                'host' =>'smtp.mxhichina.com',              //aliyun邮箱的SMTP服务器
                'username' => 'fapiao@yesincar.com',
                'password' => 'JUULa#mRY#',          //aliyun邮箱的客户端授权密码
                'port' => '465',
                'encryption' => 'ssl',
            ],
            'messageConfig'    => [
                'charset' => 'UTF-8',
                'from'    => ['fapiao@yesincar.com' => '逸品出行'],
            ],
        ],
        'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => '192.168.68.123',
            'port' => 6379,
            'database' => 0,
        ],
    ],
];
