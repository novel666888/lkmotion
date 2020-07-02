<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=rm-bp1fq9rcpw501z9fw.mysql.rds.aliyuncs.com;port=3306;dbname=lkmotion',
            'username' => 'lkmdb',
            'password' => '3quGNRitx3ieJLgc',
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
            'hostname' => 'yesincar-test.redis.rds.aliyuncs.com',
//            'password'=> 'iSPV0xcRxLsy',
            'port'     => 6379,
            'database' => 0,
        ],
    ],
];
