<?php
/**
 * Created by PhpStorm.
 * User: lijin
 * Date: 2018/10/18
 * Time: 14:24
 */
namespace common\logic;

use common\models\AdPosition;
use common\models\Ads;
use common\services\traits\ModelTrait;
use common\util\Cache;
use yii\helpers\ArrayHelper;

class BossAdLogic
{
    use ModelTrait;
    /**
     * 获取广告位列表
     *
     * @param array $requestData
     * @return array
     */
    public static function getAdPositionList($requestData){
        $query = AdPosition::find();
        $query->select(['id','position_id as ad_location','position_name','position_desc',
            'position_type','status','most_count','content_type','operator_user','create_time','update_time']);
        $query->andFilterWhere(['like','position_name', $requestData['position_name']]);
        //广告位类型
        if (!empty($requestData['position_type'])){
            $query->andFilterWhere(['position_type'=>intval($requestData['position_type'])]);
        }
        //广告位启用状态
        if (isset($requestData['status']) && ($requestData['status'] === "0" || $requestData['status'] === "1")){
            $query->andFilterWhere(['status'=>intval($requestData['status'])]);
        }
        //广告位内容类型
        if (!empty($requestData['content_type'])){
            $query->andFilterWhere(['content_type'=>intval($requestData['content_type'])]);
        }
        $adPosition = self::getPagingData($query, ['type'=>'desc','field'=>'create_time'], true);
        return $adPosition['data'];
    }

    /**
     * 广告位详情
     *
     * @param $adPositionId
     * @param $adPositionDetail
     * @return array|bool|\yii\db\ActiveRecord[]
     */
    public static function getAdPositionDetail($adPositionId){
        if(!$adPositionId){
            return false;
        }
        $adPositionDetail = Cache::get('ad_position', $adPositionId);
        if (empty($adPositionDetail['ad_position_'.$adPositionId])){
            $adPositionDetail = AdPosition::find()->select(['id','position_id','position_name','position_desc',
                'position_type','status','most_count','content_type','operator_user'])->where(['id'=>$adPositionId])->indexBy('id')->asArray()->all();
            Cache::set('ad_position', $adPositionDetail, 0);
        }
        foreach ($adPositionDetail as $key=>$value){
            $adPositionDetail[$key]['ad_location'] = $value['position_id'];
        }
        return $adPositionDetail;
    }

    /**
     * 获取广告列表
     *
     * @param array $requestData
     * @return array
     */
    public static function getAdList($requestData){
        $query = Ads::find();
        $nowTime = date("Y-m-d H:i:s",time());
        $query->select(['id','down_load_url','link_url','video_img','name','position_id','start_time','end_time','city as city_code','platform','type','status','operator_user','create_time']);
        $query->andFilterWhere(['like', 'name', $requestData['name']])
            ->andFilterWhere(['platform'=>$requestData['platform']]);

        if (!empty($requestData['start_time'])){
            $query->andFilterWhere(['>=', 'start_time', $requestData['start_time']]);
        }
        if (!empty($requestData['end_time'])){
            $requestData['end_time'] = date("Y-m-d 23:59:59", strtotime($requestData['end_time']));
            $query->andFilterWhere(['<=', 'start_time', $requestData['end_time']]);
        }
        //广告启用状态
        if (isset($requestData['status']) && ($requestData['status'] === "0" || $requestData['status'] == "1")){
            $query ->andFilterWhere(['status'=>$requestData['status']]);
        }
        //广告进行状态
        if ($requestData['is_using'] == 1){
            $query->andFilterWhere(['>', 'start_time', $nowTime]);
        }elseif ($requestData['is_using'] == 2){
            $query->andFilterWhere(['<', 'start_time', $nowTime]);
            $query->andFilterWhere(['>', 'end_time', $nowTime]);
        }elseif ($requestData['is_using'] == 3){
            $query->andFilterWhere(['<', 'end_time', $nowTime]);
        }
        //广告城市筛选(待定)
        if (isset($requestData['city'])){
            $query->andWhere('FIND_IN_SET ("'.$requestData['city'].'",city)');
        }
        //广告平台区分
        if (isset($requestData['position_type'])){
            $positionId = AdPosition::fetchArray(['position_type'=>$requestData['position_type']],'position_id');
            $positionId_arr = array_column($positionId, 'position_id');
            $query->andWhere(['IN','position_id', $positionId_arr]);
        }
        //广告位id
        if ($requestData['position_id']){
            $query->andFilterWhere(['position_id'=>$requestData['position_id']]);
        }

        //获取分页广告列表
        $ads = self::getPagingData($query, ['type'=>'desc','field'=>'create_time'], true);
        //获取对应平台广告位名称
        $adPositionName = AdPosition::fetchArray(['position_type'=>$requestData['position_type']],['position_id','position_name']);
        $positionNameList = array_unique(array_column($adPositionName, 'position_name', 'position_id'));
        //城市字段转数组
        $list = [];
        if (!empty($ads['data']['list'])){
            $list = $ads['data']['list'];
            $ossFileUrl = ArrayHelper::getValue(\Yii::$app->params,'ossFileUrl');
            foreach ($list as $key=>$value){
                $list[$key]['position_name'] = $positionNameList[$value['position_id']] ?? '';
                //区分图片、视频链接
                if ($value['type'] == 1){
                    $list[$key]['video_url'] = !empty($value['down_load_url']) ? $ossFileUrl.$value['down_load_url'] : '';
                }elseif ($value['type'] == 2){
                    $list[$key]['image_url'] = !empty($value['down_load_url']) ? $ossFileUrl.$value['down_load_url'] : '';
                }
                $list[$key]['video_img'] = !empty($value['video_img']) ? $ossFileUrl.$value['video_img'] : '';
                if ($value['start_time'] > $nowTime){
                    $list[$key]['use_status'] = 1;//未开始
                }elseif ($value['start_time'] < $nowTime && $value['end_time'] > $nowTime){
                    $list[$key]['use_status'] = 2;//进行中
                }elseif ($value['end_time'] < $nowTime){
                    $list[$key]['use_status'] = 3;//已结束
                }
                unset($list[$key]['down_load_url']);
            }
        }
        $ads['data']['list'] = $list;
        return $ads['data'];
    }

    /**
     * 广告详情
     *
     * @param $adId
     * @param $adDetail
     * @return array|bool|\yii\db\ActiveRecord[]
     */
    public static function getAdDetail($adId){
        if(!$adId){
            return false;
        }
        $adDetail = Cache::get('ads', $adId);
        if (empty($adDetail['ads_'.$adId])){
            $adDetail = Ads::find()->select(['id','down_load_url','link_url','video_img','position_id','start_time',
                'end_time','city','name','type','people_tag_id','status','operator_user'])
                ->where(['id'=>$adId])->indexBy('id')->asArray()->all();
            Cache::set('ads', $adDetail, 0);
        }
        foreach ($adDetail as $key=>$value){
            if ($value['type'] == 1){
                $adDetail[$key]['video_url'] = $value['down_load_url'];
            }elseif ($value['type'] == 2){
                $adDetail[$key]['image_url'] = $value['down_load_url'];
            }
            unset($adDetail[$key]['down_load_url']);
        }
        return $adDetail;
    }

    /**
     * 获取大屏广告展示内容
     *
     * @param $requestData
     * @return array|\common\models\BaseModel[]
     */
    public static function getAdShow($requestData){
        $adPosition = AdPosition::fetchArray(['status'=>1,'position_type'=>$requestData['position_type']],['position_id', 'most_count']);
        if (!empty($adPosition)){
            foreach ($adPosition as $key=>$value){
                $query = Ads::find()->select(['id','video_img','down_load_url'])->where(['status'=>1])
                    ->andFilterWhere(['>', 'start_time', $requestData['start_time']])
                    ->andFilterWhere(['<', 'start_time', $requestData['end_time']])
                    ->andFilterWhere(['position_id'=>$value['position_id']]);
                //城市筛选
                if (!empty($requestData['city'])){
                    $query->andWhere('FIND_IN_SET ("'.$requestData['city'].'",city)');
                }
                //人群
                if ($requestData['people_tag_id'] > 0){
                    $query->andFilterWhere(['people_tag_id'=>$requestData['people_tag_id']]);
                }
                $adPosition[$key]['adList'] = $query->asArray()->orderBy('create_time DESC')->limit($value['most_count'])->all();
            }
        }
        return $adPosition;
    }

    /**
     * 检查广告位是否存在
     *
     * @param int $adPositionId
     * @return boolean
     */
    public static function checkAdPosition($adPositionId){
        $isHave = AdPosition::fetchOne(['id'=>$adPositionId]);
        if(empty($isHave)){
            return false;
        }
        return true;
    }

    /**
     * 检查广告位名称是否存在
     *
     * @param int $positionName
     * @param int $adPositionId
     * @return boolean
     */
    public static function checkAdPositionName($positionName, $adPositionId = 0){
        $query = AdPosition::find()->where(['position_name'=>$positionName]);
        if ($adPositionId > 0){
            $query->andWhere(['<>','id',$adPositionId]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return true;
        }
        return false;
    }

    /**
     * 检查广告位置是否存在
     *
     * @param int $positionId
     * @param int $adPositionId
     * @return bool
     */
    public static function checkPositionId($positionId, $adPositionId = 0){
        $query = AdPosition::find()->where(['position_id'=>$positionId]);
        if ($positionId > 0){
            $query->andWhere(['<>','id',$adPositionId]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return true;
        }
        return false;
    }

    /**
     * 检查广告人群是否上限
     *
     * @param int $positionId
     * @param int $peopleTagId
     * @return boolean
     */
    public static function checkPeopleLimit($positionId, $peopleTagId){
        $mostCount = AdPosition::find()->select(['most_count'])->where(['position_id'=>$positionId])->scalar();
        $adCount = Ads::find()->select(['COUNT(*)'])->where(['position_id'=>$positionId,'people_tag_id'=>$peopleTagId])->scalar();
        if ($adCount >= $mostCount){
            return true;
        }
        return false;
    }

    /**
     * 检查广告位下面的广告是否已达上限
     * @param string $start_time
     * @param string $end_time
     * @param int $positionId
     * @param array $cityList
     * @return boolean
     */
    public static function checkAdLimit($start_time, $end_time, $positionId, $cityList){
        $mostCount = AdPosition::find()->select(['most_count'])->where(['position_id'=>$positionId])->scalar();
        if ($cityList !=1){//非全部广告
            $adList = Ads::find()->select(['city'])
                ->where(['and',
                    ['=','position_id',$positionId],
                    ['or',
                        ['and',['>','start_time',$start_time], ['<','start_time',$end_time]],
                        ['and',['<=','start_time',$start_time], ['>=','end_time',$end_time]],
                        ['and',['>','end_time',$start_time], ['<','end_time',$end_time]],
                        ['and',['>=','start_time',$start_time], ['<=','end_time',$end_time]],
                    ]
                ])->asArray()->all();
            if (!empty($adList)){
                $count = [];
                foreach ($adList as $k=>$v){
                    $cityArr = explode(",", $v['city']);
                    foreach ($cityList as $kk=>$vv){
                        if (in_array($vv,$cityArr)){
                            $count[$vv][] = 1;
                        }
                    }
                }
            }
            //判断是否有城市达到上限
            if (!empty($count)){
                foreach ($count as $key=>$value){
                    if (count($value) >= $mostCount){
                        return true;
                    }
                }
            }
        }else{
            $adCount = Ads::find()->select(['COUNT(*)'])->where(['position_id'=>$positionId,'city'=>1])->scalar();
            if ($adCount >= $mostCount){
                return true;
            }
        }
        return false;
    }

    /**
     * 检查广告是否存在
     *
     * @param int $adId
     * @return boolean
     */
    public static function checkAds($adId){
        $isHave = Ads::fetchOne(['id'=>$adId]);
        if(empty($isHave)){
            return false;
        }
        return true;
    }

    /**
     * 检查广告名称是否存在
     *
     * @param string $adName
     * @param int $adId
     * @return boolean
     */
    public static function checkAdName($adName, $adId = 0){
        $query = Ads::find()->where(['name'=>$adName]);
        if ($adId > 0){
            $query->andWhere(['<>','id',$adId]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return true;
        }
        return false;
    }
}