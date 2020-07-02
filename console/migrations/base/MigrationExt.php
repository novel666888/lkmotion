<?php

namespace console\migrations\base;

use yii\db\Migration;

/**
 *
 *  Migration扩展类
 * @author: JerryZhang (zhanghongdong@360.net)
 *
 */
class MigrationExt extends Migration {

	const TYPE_TINYINT = 'tinyint';

	/**
	 * tinyInteger --Creates an tinyint column.
	 * @author JerryZhang (zhanghongdong@360.net)
	 * @param int $length column size or precision definition.
	 * @return \yii\db\ColumnSchemaBuilder the column instance which can be further customized.
	 */
	public function tinyInteger($length = NULL) {
		return $this->getDb()->getSchema()->createColumnSchemaBuilder(self::TYPE_TINYINT, $length);
	}

}