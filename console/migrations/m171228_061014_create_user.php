<?php

use \console\migrations\base\MigrationExt;

class m171228_061014_create_user extends MigrationExt {
	private $_table = '{{%user}}';

	public function up() {
		$tableOptions = NULL;
		if ($this->db->driverName === 'mysql') {
			// http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
			$tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
		}

		$this->createTable($this->_table, [
			'id'                   => $this->primaryKey(),
			'username'             => $this->string()->notNull()->unique()->defaultValue(''),
			'auth_key'             => $this->string(32)->notNull()->defaultValue(''),
			'password_hash'        => $this->string()->notNull()->defaultValue(''),
			'password_reset_token' => $this->string()->notNull()->unique()->defaultValue(''),
			'email'                => $this->string()->notNull()->unique()->defaultValue(''),
			'status'               => $this->smallInteger()->notNull()->defaultValue(1),
			'created_at'           => $this->integer(10)->notNull()->defaultValue(0),
			'updated_at'           => $this->integer(10)->notNull()->defaultValue(0),
		], $tableOptions);
	}

	public function down() {
		$this->dropTable($this->_table);
	}
}