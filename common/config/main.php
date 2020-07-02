<?php
return [
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        'i18n'         => [
            'translations' => [
                '*' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath'=>'@message'
                ],
            ],
        ],
        'cache' => [
            'class' => 'yii\redis\Cache',
        ],
        'queue' => [
            'class' => \yii\queue\redis\Queue::class,
            'redis' => 'redis', // Redis connection component or its config
            'channel' => 'queue', // Queue channel key
            'as log' => \yii\queue\LogBehavior::class,
        ],
    ],
    'as demoBehaviors'=>'common\behaviors\EventBehavior',
//    'on beforeRequest'=>function($event){
//        $app=$event->sender;
//        /*** @var \yii\base\Application $app*/
//        $app->params['begin'] = microtime(1);
//    },
//    'on afterRequest'=>function($event){
//        $app = $event->sender;
//        /**@var \yii\base\Application $app */
//        $pathInfo = $app->getRequest()->pathInfo;
//        $params = $app->getRequest()->post();
//        \Yii::trace(microtime());
//        if($app->controller instanceof \common\controllers\BaseController)
//        {
//            //var_dump($app->controller->userInfo['phone']);
//        }
//        \Yii::trace(microtime(1)-$app->params['begin']);
//    }//
];
