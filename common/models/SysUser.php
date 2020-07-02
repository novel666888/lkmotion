<?php

namespace common\models;

use common\services\traits\ModelTrait;
use Yii;
use yii\helpers\ArrayHelper;
use common\util\Common;

/**
 * This is the model class for table "tbl_sys_user".
 *
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string $salt 密码的盐
 * @property string $create_time 创建时间
 * @property string $last_login_time 最后登录时间
 * @property int $modify_id 最后修改人ID
 */
class SysUser extends BaseModel
{
    use ModelTrait;

    const SCENARIO_INSERT = 'insert';
    const SCENARIO_UPDATE = 'update';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_sys_user';
    }

    public function scenarios()
    {
        return [
            self::SCENARIO_INSERT => ['username', 'password', 'salt', 'phone', 'city_code', 'status', 'modify_id', 'last_update_password_time'],
            self::SCENARIO_UPDATE => ['username', 'password', 'salt', 'phone', 'city_code', 'status', 'modify_id', 'id', 'last_update_password_time'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['username', 'required', 'message' => 10001],
            ['password', 'required', 'on' => 'insert', 'message' => 10004],
            ['phone', 'required', 'message' => 10005],
            ['city_code', 'required', 'message' => 10007],
            ['phone', 'checkPhone', 'params' => ['message' => 10006]],
            ['status', 'default', 'value' => 1],
            ['id', 'required', 'on' => 'update', 'message' => 10016],
            ['username', 'checkUnique', 'params' => ['message' => 10014]],
            ['phone', 'checkUniquePhone', 'params' => ['message' => 10013]]
        ];
    }

    public function afterValidate()
    {
        if ($this->getScenario() == self::SCENARIO_INSERT) {
            $this->salt = self::makeSalt();
            $this->password = self::makePasswd($this->password, $this->salt);
            $this->last_update_password_time = date('Y-m-d H:i:s');
        } elseif ($this->getScenario() == self::SCENARIO_UPDATE) {
            if (isset($this->password) && !empty($this->password)) {
                $this->salt = self::makeSalt();
                $this->password = self::makePasswd($this->password, $this->salt);
                $this->last_update_password_time = date('Y-m-d H:i:s');
            } else {
                unset($this->password, $this->salt);
            }
        }
    }

    public function checkUnique($attribute, $params)
    {
        $query = static::find()->where([
            'username' => $this->username,
            'is_deleted' => 0
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }

    public function checkUniquePhone($attribute, $params)
    {
        $query = static::find()->where([
            'phone' => static::makePhone($this->phone),
            'is_deleted' => 0
        ]);
        if ($this->getScenario() == self::SCENARIO_UPDATE) {
            $query = $query->andWhere(['!=', 'id', $this->id]);
        }
        $info = $query->select(['id'])->asArray()->one();

        if ($info) {
            $this->addError($attribute, $params['message']);
        }
    }

    public function checkPhone($attribute, $params)
    {
        if (!Common::checkPhoneNum($this->phone)) {
            $this->addError($attribute, $params['message']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'phone' => 'Phone',
            'status' => 'Status',
            'salt' => 'Salt',
            'is_deleted' => 'Is Deleted',
            'create_time' => 'Create Time',
            'last_login_time' => 'Last Login Time',
            'modify_id' => 'Modify ID',
            'last_update_password_time' => 'Last Update Password Time',
        ];
    }

    public static function attempt($username, $password)
    {
        $user = self::find()->where(['username' => $username])->limit(1)->one();
        if (!$user) {
            return false;
        }
        $pwd = self::makePasswd($password, $user->salt);

        return ($user->password === $pwd);
    }

    /**
     * @param $id
     * @param $password
     * @return bool
     */
    public static function checkPassword($id, $password)
    {
        $user = self::find()->where(['id' => $id, 'is_deleted' => 0])->limit(1)->one();
        $pwd = self::makePasswd($password, $user->salt);
        $logs = [
            'input' => compact('id', 'password'),
            'user' => $user,
            'hashPassword' => $pwd,
        ];
        \Yii::debug($logs, 'boss_check_password');


        return ($user->password === $pwd);
    }

    /**
     * @param $id
     * @param $password
     * @return bool
     */
    public static function updatePassword($id, $password)
    {
        $user = self::find()->where(['id' => $id, 'is_deleted' => 0])->limit(1)->one();
        $user->salt = self::makeSalt();
        $user->password = self::makePasswd($password, $user->salt);
        $user->last_update_password_time = date('Y-m-d H:i:s');

        return $user->save();
    }

    /**
     * @param $pwd
     * @param $salt
     * @return string
     */
    public static function makePasswd($pwd, $salt)
    {
        return hash('SHA256', $pwd . $salt);
    }

    public static function makeSalt()
    {
        return substr(uniqid(), -8);
    }

    public static function makePhone($phone)
    {
        return Common::phoneEncrypt($phone);
    }

    public static function decryptPhoneNumber($phone)
    {
        return Common::getPhoneNumberByEncrypt([['encrypt' => $phone]])[0]['phone'];
    }

    public static function showBatch($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $query = self::find();
        $query->select('id, username, phone, last_update_password_time');
        $query->andWhere(['id' => $ids, 'is_deleted' => 0]);
        $data = $query->asArray()->all();
        $data = ArrayHelper::index($data, 'id');

        return $data;
    }

    /**
     * get_query --
     * @author JerryZhang
     * @param $params
     * @return \yii\db\ActiveQuery
     * @cache No
     */
    public static function get_query($params)
    {
        $query = self::find();
        if (isset($params['is_delete'])) {
            $query->andWhere(['is_delete' => $params['is_delete']]);
        }
        if (isset($params['status'])) {
            $query->andWhere(['status' => $params['status']]);
        }
        if (!empty($params['id'])) {
            $query->andWhere(['id' => $params['id']]);
        }
        if (!empty($params['username'])) {
            $query->andWhere(['username' => $params['username']]);
        }

        $query->orderBy(['id' => SORT_DESC]);

        return $query;
    }
}
