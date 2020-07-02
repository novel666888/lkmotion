<?php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.33.252;port=3307;dbname=lkmotion_php',
            'username' => 'lkmotion',
            'password' => 'v4EY5rf89zVm',
            'charset' => 'utf8',
            'tablePrefix' => 'tbl_',
            'attributes' => [
                // use a smaller connection timeout
                \PDO::ATTR_STRINGIFY_FETCHES=>false,
                \PDO::ATTR_EMULATE_PREPARES=>false
            ],
        ],
        'mailer'     => [
	        'class'            => 'yii\swiftmailer\Mailer',
	        'viewPath'         => '@common/mail',
	        'useFileTransport' => FALSE,
	        'transport' => [
		        'class' => 'Swift_SmtpTransport',
		        'host' => 'smtp.163.com',
		        'username' => 'test112345@163.com',
		        'password' => 'zhd1123581321',
		        'port' => '25',
		        'encryption' => 'tls',

	        ],
	        'messageConfig'=>[
		        'charset'=>'UTF-8',
		        'from'=>['test112345@163.com'=>'admin']
	        ],
        ],
        'cache' => [
	        'class' => 'yii\redis\Cache',
        ],
        'redis' => [
	        'class' => 'yii\redis\Connection',
	        'hostname' => 'redis',
	        'port'     => 6379,
	        'database' => 0,
        ],
    ],
];
