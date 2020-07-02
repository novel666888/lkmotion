<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>
<div>

    <div class="title">
        <?= Html::encode($this->title) ?>
        <?= nl2br(Html::encode($message)) ?>
    </div>

</div>
