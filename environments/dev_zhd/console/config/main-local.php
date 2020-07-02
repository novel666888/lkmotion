<?php
return [
    'bootstrap' => ['gii'],
    'modules' => [
        'gii' => 'yii\gii\Module',
    ],
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.33.252;port=3307;dbname=lkmotion_php',
            'username' => 'lkmotion',
            'password' => 'v4EY5rf89zVm',
            'charset' => 'utf8',
            'tablePrefix' => 'tbl_'
        ],
    ],
];
