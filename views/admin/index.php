<?php
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Админка';
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= Html::encode($this->title) ?></h3>
        <form method="post" action="<?= Url::to(['admin/logout']) ?>">
            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
            <button class="btn btn-outline-danger btn-sm">Выйти</button>
        </form>
    </div>

    <div class="list-group">
        <a class="list-group-item list-group-item-action" href="<?= Url::to(['admin/card-types']) ?>">
            Типы карт
        </a>
    </div>
</div>
