<?php
/**
 * Created by PhpStorm.
 * User: lijin
 * Date: 2018/10/15
 * Time: 13:43
 */
namespace common\logic;

use common\models\MessageShow;
use common\models\PeopleTag;
use common\models\SmsAppTemplate;
use common\models\SmsSendApp;
use common\services\traits\ModelTrait;
use common\util\Cache;

class BossMessageLogic
{
    use ModelTrait;
    /**
     * app文案模板列表
     *
     * @param string $templateName
     * @param string $templateType
     * @return array
     */
    public static function getAppTemplateList($templateName = '', $templateType = ''){
        $query = SmsAppTemplate::find();
        $query->select('*');
        $query->andFilterWhere(['LIKE','template_name',$templateName]);
        $query->andFilterWhere(['template_type'=>$templateType]);
        $appTemplateList = self::getPagingData($query, ['type'=>'desc', 'field'=>'create_time'], true);
        $list = $appTemplateList['data']['list'];
        if (!empty($list)){
            $appTemplateIds = array_column($list,'id');
            //取发送记录模板id
            $sendSms = SmsSendApp::find()->select('distinct(app_template_id)')->where(['app_template_id'=>$appTemplateIds])->asArray()->all();
            $sendAppTemplateIds = array_column($sendSms,'app_template_id');
            foreach ($list as $key=>$value){
                //检查该模板有无发送记录
                if (in_array($value['id'],$sendAppTemplateIds)){
                    $list[$key]['is_send'] = 1;
                }else{
                    $list[$key]['is_send'] = 0;
                }
                //匹配发送类型
                $list[$key]['send_type'] = str_replace(['1','2','3'],['文案','图片','语音'], $value['send_type']);
            }
        }
        $appTemplateList['data']['list'] = $list;
        return $appTemplateList['data'];
    }

    /**
     * 获取app文案详情
     *
     * @param $templateId
     * @param $appTemplateDetail
     * @return array|bool|\yii\db\ActiveRecord[]
     */
    public static function  getAppTemplateDetail($templateId){
        if(empty($templateId)){
            return false;
        }
        $appTemplateDetail = Cache::get('sms_app_template', $templateId);
        if (empty($appTemplateDetail['sms_app_template_'.$templateId])){
            $appTemplateDetail = SmsAppTemplate::find()->where(['id'=>$templateId])->indexBy('id')->asArray()->all();
            Cache::set('sms_app_template', $appTemplateDetail, 0);
        }
        if (!empty($appTemplateDetail)){
            foreach ($appTemplateDetail as $key=>$value){
                $appTemplateDetail[$key]['send_type'] = explode(",", $value['send_type']);
            }
        }
        return $appTemplateDetail;
    }

    /**
     * 获取消息推送列表
     *
     * @param array $requestData
     * @return array
     */
    public static function getPushList($requestData){
        $query = SmsSendApp::find();
        $query->select(['id','send_number','send_status','show_type','start_time','app_template_id','update_time','status','operator_user']);
        if (!empty($requestData['name'])){
            $templateIds = SmsAppTemplate::find()->select(['id'])->where(['LIKE','template_name',$requestData['name']])->asArray()->all();
            $templateIdsArr = array_column($templateIds,'id');
            $query->andFilterWhere(['IN', 'app_template_id', $templateIdsArr]);
        }
        if (isset($requestData['status']) && ($requestData['status'] === "0" || $requestData['status'] === "1")){
            $query->andFilterWhere(['status'=>$requestData['status']]);
        }
        if (isset($requestData['send_status'])){
            $query->andFilterWhere(['send_status'=>$requestData['send_status']]);
        }
        $pushList = self::getPagingData($query, ['type'=>'desc','field'=>'create_time'], true);
        $list = $pushList['data']['list'];
        if (!empty($list)){
            $appTemplateIds = array_unique(array_column($list,'app_template_id'));
            $appTemplateInfo = SmsAppTemplate::find()->select(['id','template_name'])->where(['id'=>$appTemplateIds])->asArray()->all();
            $appTemplateNames = array_column($appTemplateInfo,'template_name','id');
            //取对应记录发送条数
            $sendCount = MessageShow::find()->select('count(*) as count,sms_send_app_id')->where(['>','sms_send_app_id','0'])->groupBy('sms_send_app_id')->indexBy('sms_send_app_id')->asArray()->all();
            foreach ($list as $key=>$value){
                $list[$key]['send_count'] = $sendCount[$value['id']]['count'] ?? 0;
                $list[$key]['template_name'] = $appTemplateNames[$value['app_template_id']] ?? '';
            }
        }
        $pushList['data']['list'] = $list;
        return $pushList['data'];
    }

    /**
     * 消息推送详情
     *
     * @param $sendAppId
     * @param $sendAppDetail
     * @return array|bool|\yii\db\ActiveRecord[]
     */
    public static function getSendAppDetail($sendAppId){
        if(empty($sendAppId)){
            return false;
        }
        $sendAppDetail = Cache::get('sms_send_app', $sendAppId);
        if (empty($sendAppDetail['sms_send_app_'.$sendAppId])){
            $sendAppDetail = SmsSendApp::find()->select(['id','show_type','sms_type','start_time','people_tag_id','app_template_id','sms_level'])
                ->where(['id'=>$sendAppId])->indexBy('id')->asArray()->all();
            Cache::set('sms_send_app', $sendAppDetail, 0);
        }
        if (!empty($sendAppDetail)){
            foreach ($sendAppDetail as $key=>$value) {
                $templateInfo = SmsAppTemplate::find()->select(['template_name','template_type','send_type','content','sms_image','sms_url'])
                    ->where(['id'=>$value['app_template_id']])->asArray()->one();
                $peopleTagName = PeopleTag::find()->select(['tag_name'])->where(['id'=>$value['people_tag_id']])->scalar();
                $templateInfo['send_type'] = explode(",", $templateInfo['send_type']) ?? '';
                $sendAppDetail[$key]['template_info'] = $templateInfo ?? '';
                $sendAppDetail[$key]['people_tag_name'] = $peopleTagName ?? '';
            }
        }
        return $sendAppDetail;
    }

    /**
     * 检查app文案模板是否存在
     *
     * @param int $appTemplateId
     * @return boolean
     */
    public static function checkAppTemplate($appTemplateId){
        $isHave = SmsAppTemplate::fetchOne(['id'=>$appTemplateId]);
        $isSend = SmsSendApp::fetchOne(['app_template_id'=>$appTemplateId]);
        if(empty($isHave) || !empty($isSend)){
            return false;
        }
        return true;
    }


    /**
     * 检查app文案模板名称是否存在
     *
     * @param string $appTemplateName
     * @param int $appTemplateId
     * @return boolean
     */
    public static function checkAppTemplateName($appTemplateName, $appTemplateId = 0){
        $query = SmsAppTemplate::find()->where(['template_name'=>$appTemplateName]);
        if ($appTemplateId > 0){
            $query->andWhere(['<>','id',$appTemplateId]);
        }
        $isHave = $query->asArray()->one();
        if(empty($isHave)){
            return true;
        }
        return false;
    }


    /**
     * 检查消息推送是否存在
     *
     * @param int $sendAppId
     * @return boolean
     */
    public static function checkSendApp($sendAppId){
        $isHave = SmsSendApp::fetchOne(['id'=>$sendAppId]);
        if(empty($isHave)){
            return false;
        }
        return true;
    }
}