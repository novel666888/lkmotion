<?php
namespace application\controllers;

use application\models\Ads;
use common\controllers\BaseController;
use common\logic\order\OrderOutputServiceTrait;
use common\models\LoginForm;
use Yii;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    use OrderOutputServiceTrait;
    /**
     * @inheritdoc
     */
   /* public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login', 'error'],
                        'allow' => true,
                    ],
                    [
                        'actions' => ['logout', 'index'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }*/

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'getOssToken' => 'common\actions\OssSecurityTokenAction',
        ];
    }

    /**
     * Displays homepage.
     *
     * @return array
     */
    public function actionIndex()
    {

    }
}
