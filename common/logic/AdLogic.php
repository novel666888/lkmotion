<?php
namespace common\logic;

use common\models\AdPosition;
use common\models\Ads;
use common\models\CarInfo;
use common\models\PeopleTag;
use yii\base\UserException;
use yii\helpers\ArrayHelper;
use common\models\City;

class AdLogic
{
    /**
     * 获取大屏广告列表
     * 
     * @param array $positionId  广告位id数组
     * @param string $city  城市码
     * @param string $device_code  大屏唯一码
     */
    public static function getLargeScreenAdList($positionId, $device_code, $city = ''){
        if (empty($positionId) || empty($device_code)){
            throw new UserException('params error');
        }
        $nowTime = date("Y-m-d H:i:s",time());
        $positionInfo = AdPosition::find()->select(['id','position_id','content_type','most_count'])->where(['status'=>1,'position_type'=>2,'position_id'=>$positionId])->asArray()->all();

       if (!empty($positionInfo)){

            $peopleTagInfo = PeopleTag::find()->select(['tag_conditions','id'])->where(['tag_type'=>2])->indexBy('id')->asArray()->all();
            
            $plate_number = CarInfo::find()->select(['plate_number'])->where(['large_screen_device_code'=>$device_code])->scalar();
            //city为空取全国广告
            $query = Ads::find()->select(['down_load_url','link_url','video_img','name','people_tag_id','position_id'])->where(['position_id'=>$positionId,'status'=>1])
            ->andWhere(['<','start_time',$nowTime])->andWhere((['>','end_time',$nowTime]));

            if (!empty($city)){
                $adInfo = $query->andWhere('FIND_IN_SET ("'.$city.'",city)')->asArray()->all();
            }else{
                $adInfo = $query->andWhere(['city'=>1])->asArray()->all();
            }
            foreach ($positionInfo as $k=>$v){
                $flagAd = [];
                $commonAd = [];
                if (!empty($adInfo)){
                    $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
                    foreach ($adInfo as $kk=>$vv){
                        if ($vv['position_id'] == $v['position_id']){
                            $adInfo[$kk]['down_load_url'] = !empty($vv['down_load_url']) ? $ossFileUrl.$vv['down_load_url'] : '';
                            $adInfo[$kk]['video_img'] = !empty($vv['video_img']) ? $ossFileUrl.$vv['video_img'] : '';
                            if ($vv['people_tag_id'] > 0){
                                $carPlateNumber = json_decode($peopleTagInfo[$vv['people_tag_id']]['tag_conditions']);
                                if (empty($carPlateNumber)){
                                    throw new UserException('people_tag_error');
                                }
                                if (in_array($plate_number, $carPlateNumber)){
                                    $flagAd[] = $adInfo[$kk];
                                }
                            }else{
                                $commonAd[] = $adInfo[$kk];
                            }
                        }
                    }
                }
                if (!empty($flagAd) && !empty($commonAd)){
                    $positionInfo[$k]['list'] = array_merge($flagAd,$commonAd);
                }elseif (!empty($flagAd) && empty($commonAd)){
                    $positionInfo[$k]['list'] = $flagAd;
                }elseif (!empty($commonAd) && empty($flagAd)){
                    $positionInfo[$k]['list'] = $commonAd;
                }else{
                    $positionInfo[$k]['list'] = [];
                }
            }
       }
        \Yii::info($positionInfo, 'positionInfo1');
        if (!empty($positionInfo)){
            $list['list'] = $positionInfo;
        }else{
            $list = [];
        }
        return $list;
    }
    
    /**
     * 取乘客端广告位列表
     * 
     * @param array $positionId 一维数组
     * @param string $city
     * @return array
     */
    public static function getPassengerAdList($positionId, $city = ''){
        if (empty($positionId)){
            throw new UserException('params error');
        }
        $nowTime = date("Y-m-d H:i:s", time());
        $positionInfo = AdPosition::find()->select(['id','position_id','content_type','most_count'])->where(['status'=>1,'position_type'=>1,'position_id'=>$positionId])->asArray()->all();
        $query = Ads::find()->select(["down_load_url",'link_url',"video_img",'name','position_id'])->where(['position_id'=>$positionId,'status'=>1])
        ->andWhere(['<','start_time',$nowTime])->andWhere((['>','end_time',$nowTime]));
        if (!empty($city)){
            $adInfo = $query->andWhere('FIND_IN_SET ("'.$city.'",city)')->asArray()->all();
        }else{
            $adInfo = $query->andWhere(['city'=>1])->asArray()->all();
        }
        if(!empty($adInfo)){
            foreach ($adInfo as $k => $v){
                $qz = substr($adInfo[$k]['down_load_url'],0,1);
                if($qz=="/"){
                    $adInfo[$k]['down_load_url'] = \Yii::$app->params['ossFileUrl'].$adInfo[$k]['down_load_url'];
                }else{
                    $adInfo[$k]['down_load_url'] = \Yii::$app->params['ossFileUrl'].$adInfo[$k]['down_load_url'];
                }

                $qz = substr($adInfo[$k]['video_img'],0,1);
                if($qz=="/"){
                    $adInfo[$k]['video_img'] = \Yii::$app->params['ossFileUrl'].$adInfo[$k]['video_img'];
                }else{
                    $adInfo[$k]['video_img'] = \Yii::$app->params['ossFileUrl'].$adInfo[$k]['video_img'];
                }
            }
        }

        if (!empty($positionInfo)){
            foreach ($positionInfo as $k=>$v){
                foreach ($adInfo as $kk=>$vv){
                    if ($vv['position_id'] == $v['position_id']){
                        $positionInfo[$k]['list'][] = $adInfo[$kk];
                    }
                }
            }
        }
        if (!empty($positionInfo)){
            $list['list'] = $positionInfo;
        }else{
            $list = [];
        }
        return $list;
    }
    
    /**
     * 获取列表城市名
     *
     * @param array $data
     * @return void|unknown
     */
    public static function getCityNme($data){
        if (empty($data)){
            throw new UserException('params error');
        }
        $city = City::find()->select(['city_code','city_name'])->indexBy('city_code')->asArray()->all();
        foreach ($data as $k=>$v){
            $city_name = '';
            if ($v['city_code'] == 1){
                $data[$k]['city_name'] = '全国';
            }else{
                $city_code = explode(",", $v['city_code']);
                foreach ($city_code as $kk=>$vv){
                    $city_name .=  !empty($city[$vv]['city_name']) ? $city[$vv]['city_name']."," : '';
                }
                $data[$k]['city_name'] = substr($city_name, 0, strlen($city_name)-1);
            }
        }
        return $data;
    }

}