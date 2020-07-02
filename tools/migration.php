<?php
$cmd = 'php yii migrate/create --interactive=0 -F="@console/migrations/template/migration.php" %s';

if ($argc == 1) {
    echo "CMD FORMAT: php migration.php xxxx";
    exit;
}

$cmd = sprintf($cmd, $argv[1]);
system($cmd, $return);

echo implode("\n", $return);
echo "\n";
