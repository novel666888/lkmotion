<?php

namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "admin_user".
 *
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string $password_hash
 * @property string $email
 * @property string $mobile
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class AdminUser extends ActiveRecord implements IdentityInterface {

	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;

	/**
	 * @inheritdoc
	 */
	public static function tableName() {
		return 'admin_user';
	}

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['status', 'created_at', 'updated_at'], 'integer'],
			[['username', 'password_hash', 'email'], 'string', 'max' => 255],
			[['auth_key'], 'string', 'max' => 32],
			[['mobile'], 'string', 'max' => 64],
			[['username'], 'unique'],
			[['email'], 'unique'],
			[['mobile'], 'unique'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels() {
		return [
			'id'            => 'ID',
			'username'      => 'Username',
			'auth_key'      => 'Auth Key',
			'password_hash' => 'Password Hash',
			'email'         => 'Email',
			'mobile'        => 'Mobile',
			'status'        => 'Status',
			'created_at'    => 'Created At',
			'updated_at'    => 'Updated At',
		];
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentity($id) {
		return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
	}

	/**
	 * @inheritdoc
	 */
	public static function findIdentityByAccessToken($token, $type = NULL) {
		throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
	}

	/**
	 * Finds user by username
	 *
	 * @param string $username
	 * @return static|null
	 */
	public static function findByUsername($username) {
		return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
	}

	/**
	 * @inheritdoc
	 */
	public function getId() {
		return $this->getPrimaryKey();
	}

	/**
	 * @inheritdoc
	 */
	public function getAuthKey() {
		return $this->auth_key;
	}

	/**
	 * @inheritdoc
	 */
	public function validateAuthKey($authKey) {
		return $this->getAuthKey() === $authKey;
	}

	/**
	 * Validates password
	 *
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password) {
		return Yii::$app->security->validatePassword($password, $this->password_hash);
	}

	/**
	 * Generates password hash from password and sets it to the model
	 *
	 * @param string $password
	 */
	public function setPassword($password) {
		$this->password_hash = Yii::$app->security->generatePasswordHash($password);
	}

	/**
	 * Generates "remember me" authentication key
	 */
	public function generateAuthKey() {
		$this->auth_key = Yii::$app->security->generateRandomString();
	}

}
