<?php
/**
 * 黑名单解禁脚本
 * 
 * Created by Zend Studio
 * User: sunliang
 * Date: 2018年9月4日
 * Time: 上午11:29:55
 */
namespace console\controllers;

use yii\console\Controller;
//use common\services\traits\PublicMethodTrait;
use common\models\PassengerBlacklist;
use yii\helpers\ArrayHelper;
//use yii\base\UserException;
use common\logic\blacklist\BlacklistDashboard;


class BlacklistController extends Controller{
    //use PublicMethodTrait;

    /**
     * 解除乘客临时黑名单
     */
    public function actionIndex(){
        $time = date("Y-m-d H:i:s", time());//当前时间
        $result = PassengerBlacklist::find()->select(["id","phone"])->andFilterWhere(["<", "release_time", $time])
        ->andFilterWhere(["category"=>1])//临时黑名单
        ->andFilterWhere(["is_release"=>0])//未解禁
        ->asArray()->all();
        if(!empty($result)){
            $phoneArr = ArrayHelper::getColumn($result, 'phone');
            if(!empty($phoneArr)){
                $PassengerBlacklist = new PassengerBlacklist();
                $updata=[];
                $updata['is_release']=1;
                $condition=['in','phone',$phoneArr];
                $jg = $PassengerBlacklist->updateAll($updata, $condition);
                if($jg!==false){
                    $this->relieve($phoneArr);
                }
            }
        }
    }

    /**
     * 清除redis中的记录信息
     */
    private function relieve($phoneArr){
        if(!empty($phoneArr)){
            foreach ($phoneArr as $k => $phone){
                //BlacklistDashboard::
                BlacklistDashboard::delCacheRecord($phone);
            }
            BlacklistDashboard::ulist();
        }
    }

}