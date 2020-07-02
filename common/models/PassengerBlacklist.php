<?php

namespace common\models;

use Yii;
use common\services\traits\ModelTrait;
use yii\helpers\ArrayHelper;
use common\util\Common;
use common\models\PassengerInfo;
use common\logic\blacklist\BlacklistDashboard;
/**
 * This is the model class for table "tbl_passenger_blacklist".
 *
 * @property string $id
 * @property string $phone 电话号码
 * @property string $reason 1 个人用户1小时内连续取消订单3次
 2 24小时内取消20次派车成功单
 * @property string $category 1 临时黑名单 2 永久黑名单
 * @property string $is_release 是否解禁
 * @property string $release_time 解禁时间
 * @property string $create_time create
 * @property string $update_time update
 */
class PassengerBlacklist extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_passenger_blacklist';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['phone'], 'required'],
            [['release_time', 'create_time', 'update_time'], 'safe'],
            [['phone'], 'string', 'max' => 64],
            [['reason', 'category', 'is_release'], 'string', 'max' => 1],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'phone' => 'Phone',
            'reason' => 'Reason',
            'category' => 'Category',
            'is_release' => 'Is Release',
            'release_time' => 'Release Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 插入一条黑名数据
     */
    public static function add($phoneEncrypt, $reason=1, $category=1, $release_time=0){
        if(empty($phoneEncrypt)){
            return false;
        }
        $model = new PassengerBlacklist();
        $model->phone = (string)$phoneEncrypt;
        $model->reason = (string)$reason;
        $model->category = (string)$category;
        $model->is_release = (string)0;
        $model->release_time = $release_time;
        if (!$model->validate()){
            //echo $model->getFirstError();
            //exit;
            return false;
        }else{
            if($model->save()){
                BlacklistDashboard::ulist();
                return true;
            }else{
                //return Json::message($model->getErrors());
                return false;
            }
        }
    }


    /**
     * 返回指定手机号下的黑名单信息
     */
    public static function query($data){
        $result = PassengerBlacklist::find()->select(["*"])
            ->andFilterWhere(['phone'=>$data['phone']])
            ->andFilterWhere(['is_release'=>$data['is_release']])
            ->orderby("id DESC")
            ->asArray()->one();
        if($result){
            return $result;
        }
        return false;
    }


    /**
     * 获取乘客黑名单列表，分页
     * @param  array $condition  字段条件
     * @param  array $field      查询字段
     * @return array
     */
    public static function getBlacklist($condition, $field=['*']){
        $model = self::find()->select($field);
        //临时/永久
        if(!empty($condition['category'])){
            $model->andFilterWhere(['category'=>$condition['category']]);
        }
        //是否解禁
        if(!empty($condition['is_release'])){
            $model->andFilterWhere(['is_release'=>$condition['is_release']]);
        }
        //手机号
        if(!empty($condition['phone'])){
            $model->andFilterWhere(['phone'=>$condition['phone']]);
        }
        $data = self::getPagingData($model, ['type'=>'update_time ASC','field'=>'is_release ASC,'], true);
        if(isset($data['data']['list']) && !empty($data['data']['list'])){
            $cipher = ArrayHelper::getColumn($data['data']['list'], 'phone');
            //获取用户ID
            $passengerIds = PassengerInfo::getPhoneToPassengerId($cipher);
            foreach ($data['data']['list'] as $key => &$value) {
                $value['passengerId'] = $passengerIds[$value['phone']];
            }
            //明文手机号
            $cipher = Common::decryptCipherText($cipher);
            if(!is_array($cipher)){
                foreach ($data['data']['list'] as $key => &$value) {
                    $value['phone'] = $cipher;
                }                
            }
            else{
                foreach ($data['data']['list'] as $key => &$value) {
                    $value['phone'] = $cipher[$value['phone']];
                }
            }
        }
        return $data['data'];
    }

    /**
     * 解禁乘客黑名单
     * @param
     * @return array
     */
    public static function releaseBlacklist($condition){
        if(!is_numeric($condition['blacklistId'])){
            return ["code"=>1, "message"=>"Parameter error"];
        }
        if(!is_numeric($condition['isRelease'])){
            return ["code"=>1, "message"=>"Parameter error"];
        }

        $model = self::findOne($condition['blacklistId']);
        if(!empty($model)){
            //$model->is_release =(string)$condition['isRelease'];//1解禁
            $model->is_release      =   "1";
            $model->release_time    =   date("Y-m-d H:i:s", time());
            if($model->save()){
                //解除redis中的不良记录
                if($model->phone){
                    $phone = Common::decryptCipherText($model->phone, true);
                    if(!empty($phone)){
                        BlacklistDashboard::delCacheRecord($phone);
                    }
                }
                //更新
                BlacklistDashboard::ulist();
                return ["code"=>0];
            }else{
                $mes = $model->getErrors();
                return ["code"=>1, "message"=>$mes];
            }
        }else{
            return ["code"=>1, "message"=>"No record"];
        }
    }



}
