<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;

use common\models\PassengerWallet;
use common\models\PassengerRegisterSource;
use common\models\PassengerContact;
use common\util\Common;

/**
 * This is the model class for table "tbl_passenger_info".
 *
 * @property int $id
 * @property string $phone 电话
 * @property string $educatioan 学历
 * @property string $birthday 生日
 * @property string $passenger_name 乘客名称
 * @property string $register_time 注册时间
 * @property string $balance 余额
 * @property int $gender 0：女，1：男
 * @property string $head_img 头像
 * @property int $passenger_type 用户类型，1：个人用户，2：企业用户
 * @property int $user_level 会员等级
 * @property int $register_type 注册渠道 1 安卓 2 ios
 * @property int $is_contact 0不启用紧急联系人，1启用紧急联系人
 * @property int $is_share 0不自动分享，1自动分享行程
 * @property string $sharing_time 自动分享行程 - 分享时段
 * @property string $last_login_time 最后一次登录时间
 * @property int $last_login_method 上次登陆方式:1,验证码,2密码
 * @property string $last_login_screen_time 上次登录大屏时间
 * @property string $last_login_screen_method 上次登录大屏方式
 * @property string $last_order_time 最后一次下单时间
 * @property string $create_time 创建时间
 * @property string $update_time 修改时间
 */
class PassengerInfo extends \common\models\BaseModel
{
    use ModelTrait;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%passenger_info}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['birthday', 'register_time', 'last_login_time', 'last_login_screen_time', 'last_order_time', 'create_time', 'update_time'], 'safe'],
            [['balance'], 'number'],
            [['gender', 'passenger_type', 'user_level', 'register_type', 'is_contact', 'is_share', 'last_login_method'], 'integer'],
            [['phone'], 'string', 'max' => 64],
            [['educatioan'], 'string', 'max' => 255],
            [['passenger_name'], 'string', 'max' => 16],
            [['head_img', 'sharing_time'], 'string', 'max' => 256],
            [['last_login_screen_method'], 'string', 'max' => 1],
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
            'educatioan' => 'Educatioan',
            'birthday' => 'Birthday',
            'passenger_name' => 'Passenger Name',
            'register_time' => 'Register Time',
            'balance' => 'Balance',
            'gender' => 'Gender',
            'head_img' => 'Head Img',
            'passenger_type' => 'Passenger Type',
            'user_level' => 'User Level',
            'register_type' => 'Register Type',
            'is_contact' => 'Is Contact',
            'is_share' => 'Is Share',
            'sharing_time' => 'Sharing Time',
            'last_login_time' => 'Last Login Time',
            'last_login_method' => 'Last Login Method',
            'last_login_screen_time' => 'Last Login Screen Time',
            'last_login_screen_method' => 'Last Login Screen Method',
            'last_order_time' => 'Last Order Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    /**
     * 通过一组密文手机号，获取一组用户ID
     * @return array key密文手机号。value用户ID
     */
    public static function getPhoneToPassengerId($phoneArr){
        if(empty($phoneArr)){
            return false;
        }
        if(!is_array($phoneArr)){
            $phoneArr = [$phoneArr];
        }
        $model = self::find()->select(['id', 'phone']);
        $model->andFilterWhere(['in', 'phone', $phoneArr])->indexBy('phone');
        $data = $model->asArray()->all();
        if(!empty($data)){
            foreach ($data as &$value) {
                $value = $value['id'];
            }
        }
        return $data;
    }

    /**
     * 通过ID或手机号获取用户基本信息
     * @param array $condition
     * @return array
     */
    public static function getUserInfo($condition, $select=['*']){
        if(empty($condition['id']) && empty($condition['phone'])){
            return [];
        }
        $model = self::find()->select($select);
        if(!empty($condition['id'])){
            $model->andFilterWhere(['id'=>intval($condition['id'])]);
        }
        if(!empty($condition['phone'])){
            $model->andFilterWhere(['phone'=>$condition['phone']]);
        }
        return $model->asArray()->one();
    }

    /**
     * 通过ID或手机号获取用户详细信息/钱包信息/紧急联系人信息
     * @param array $condition
     * @return array
     */
    public static function getUserDetailInfo($condition, $field=['*']){
        if(empty($condition['id']) && empty($condition['phone'])){
            return [];
        }
        $model = self::find()
            ->from("tbl_passenger_info AS i")
            ->select($field);
        if(!empty($condition['id'])){
            $model->andFilterWhere(['i.id'=>intval($condition['id'])]);
        }
        if(!empty($condition['phone'])){
            $model->andFilterWhere(['i.phone'=>$condition['phone']]);
        }
        $rs = $model->asArray()->one();
        if($rs){
            try{
                $rs['phone'] = Common::decryptCipherText($rs['phone'], true);
            }catch (yii\base\Exception $e){
                $rs['phone'] = "(error)";
            }
            if(!empty($rs['head_img'])){
                $rs['head_img'] = \Yii::$app->params['ossFileUrl'].$rs['head_img'];
            }

            $rs2 = PassengerRegisterSource::find()->select(['register_source'])->where(['passenger_info_id'=>$rs['id']])->asArray()->one();
            if(empty($rs2)){
                $rs2['register_source'] = "";
            }
            $rs = array_merge($rs, $rs2);

            $m = PassengerWallet::find()->select(['capital','give_fee','freeze_capital','freeze_give_fee'])
                ->andFilterWhere(['passenger_info_id'=>$rs['id']])->asArray()->one();
            if(empty($m)){
                $m['capital'] = '0';
                $m['give_fee'] = '0';
                $m['freeze_capital'] = '0';
                $m['freeze_give_fee'] = '0';
            }
            $rs = array_merge($rs, $m);

            $pc = self::getAllContact($rs['id']);
            if(empty($pc)){
                $_pc['contact'] = [];
            }else{
                $_pc['contact'] = $pc;
            }
            $rs = array_merge($rs, $_pc);

            return $rs;
        }
        return [];
    }

    /**
     * 启用/关闭，紧急联系人
     * @param int $passengerId 用户ID
     * @param int $flag 1启用/0不启用
     * @return
     */
    public static function setContact($passengerId, $flag){
        if(empty($passengerId) || !in_array($flag, [0,1])){
            return false;
        }
        $data = self::find()->where(["id"=>$passengerId])->one();
        if(!empty($data->id)){
            $data->is_contact = $flag;
            if($data->save()){
                return true;
            }
            \Yii::info($data->getFirstError(),"setContact");
        }
        \Yii::info($passengerId,"no user setContact");
        return false;
    }

    /**
     * 启用/关闭，自动分享行程
     * @param int $passengerId 用户ID
     * @param int $flag 1启用/0不启用
     * @return
     */
    public static function setShare($passengerId, $flag){
        if(empty($passengerId) || !in_array($flag, [0,1])){
            return false;
        }
        $data = self::find()->where(["id"=>$passengerId])->one();
        if(!empty($data->id)){
            $data->is_share = $flag;
            if($data->save()){
                return true;
            }
            \Yii::info($data->getFirstError(),"setShare");
        }
        \Yii::info($passengerId,"no user setShare");
        return false;
    }

    /**
     * 更新自动分享行程 - 分享时段
     * @param int $passengerId
     * @param str $time 时段
     */
    public static function updateSharingTime($passengerId, $time){
        if(empty($passengerId) || empty($time)){
            return false;
        }
        $data = self::find()->where(["id"=>$passengerId])->one();
        if(!empty($data->id)){
            $data->sharing_time = $time;
            if($data->save()){
                return true;
            }
            \Yii::info($data->getFirstError(),"SharingTime");
        }
        \Yii::info($passengerId,"no user SharingTime");
        return false;
    }

    /**
     * 返回第一紧急联系人
     * @return array
     */
    public static function getFirstContact($passengerId){
        if(empty($passengerId)){
            return false;
        }
        $data = PassengerContact::find()->andFilterWhere(["passenger_info_id"=>$passengerId, "is_del"=>0])
            ->orderBy('id ASC')
            ->limit(1)
            ->asArray()->one();
        if($data){
            return $data;
        }else{
            return false;
        }
    }

    /**
     * 返回所有紧急联系人
     * @return array
     */
    public static function getAllContact($passengerId, $select=['name','phone']){
        if(empty($passengerId)){
            return false;
        }
        $data = PassengerContact::find()->select($select)->andFilterWhere(["passenger_info_id"=>$passengerId, "is_del"=>0])
            ->orderBy('id ASC')
            ->asArray()->all();
        if($data){
            return $data;
        }else{
            return false;
        }
    }

}
