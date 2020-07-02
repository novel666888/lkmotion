<?php
/**
 * This view is used by console/controllers/MigrateController.php
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}

$class_name_arr = explode("_", $className);
array_shift($class_name_arr);
array_shift($class_name_arr);
array_shift($class_name_arr);
$pure_class_name = implode("_", $class_name_arr);

?>

use \console\migrations\base\MigrationExt;

class <?= $className ?> extends MigrationExt
{
    private $_table = '{{%<?= $pure_class_name ?>}}';

    public function up()
    {
        $tableOptions = NULL;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable($this->_table, [
            'id'         => $this->primaryKey(),
            'status'     => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer(10)->notNull()->defaultValue(0),
            'updated_at' => $this->integer(10)->notNull()->defaultValue(0),
        ], $tableOptions);

        $this->addColumn($this->_table, "name", $this->string(64)->notNull()->after('id')->defaultValue(''));
    }

    public function down()
    {
        $this->dropTable($this->_table);

        $this->dropColumn($this->_table, "name");
    }
}