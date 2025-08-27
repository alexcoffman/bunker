<?php
use yii\helpers\Html;
use yii\helpers\Url;

/** @var string|null $error */
$this->title = 'Вход в админку';
?>
<div class="container" style="max-width:520px">
    <h3 class="mb-3"><?= Html::encode($this->title) ?></h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= Html::encode($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= Url::to(['admin/login']) ?>" class="card card-body">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <div class="mb-3">
            <label class="form-label">Пароль администратора</label>
            <input type="password" name="password" class="form-control" autofocus required>
        </div>
        <button class="btn btn-primary">Войти</button>
    </form>
</div>
