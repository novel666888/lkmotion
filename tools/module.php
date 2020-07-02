<?php
$cmd = 'php yii gii/module --interactive=0 --moduleClass="%s\modules\%s\Module" --moduleID=%s';

if ($argc == 1) {
    echo "CMD FORMAT: php module.php app_name module_name";
    exit;
}
$cmd = sprintf($cmd, $argv[1], $argv[2], $argv[2]);
system($cmd, $return);

echo implode("\n", $return);

echo "\n==========[ config code ]========\n";
echo <<<_CODE
        '$argv[2]' => [
            'class' => '$argv[1]\modules\\$argv[2]\Module',
        ],
_CODE;

echo "\n";
