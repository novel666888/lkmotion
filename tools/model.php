<?php
$cmd = 'php yii gii/model --interactive=0 --baseClass="\yii\db\ActiveRecord" --ns="common\models" --overwrite=0 --tableName="*"';
system($cmd, $return);

echo implode("\n", $return);
echo "\n";