<?php
/** @var yii\web\View $this */
use yii\helpers\Url;
use yii\helpers\Html;

$this->title = 'Bunker — Главная';
?>

<div class="site-index">
  <div class="row">
    <div class="col-md-6 mb-4">
      <h3>Создать игру</h3>
      <p>Правила предустановлены. Код будет из 4 символов.</p>
      <form method="post" action="<?= Url::to(['/game/create']) ?>">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <button class="btn btn-primary">Создать игру</button>
      </form>
      <?php if ($code = Yii::$app->session->getFlash('gameCreatedCode')): ?>
        <div class="alert alert-success mt-3">
          Код вашей игры: <strong><?= Html::encode($code) ?></strong>
          <div class="mt-2">
            <a class="btn btn-success btn-sm" href="<?= Url::to(['/game/'.$code, 'as'=>'host']) ?>">Перейти к столу</a>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <div class="col-md-6 mb-4">
      <h3>Присоединиться к игре</h3>
      <?php if ($msg = Yii::$app->session->getFlash('error')): ?>
        <div class="alert alert-danger"><?= Html::encode($msg) ?></div>
      <?php endif; ?>
      <form method="post" action="<?= Url::to(['/game/join']) ?>" class="row g-2">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <div class="col-12">
          <label class="form-label">Ник</label>
          <input class="form-control" name="nickname" required minlength="2" maxlength="32" placeholder="Ваш ник">
        </div>
        <div class="col-12">
          <label class="form-label">Код игры (4 символа)</label>
          <input class="form-control text-uppercase" name="code" required pattern="[A-Za-z0-9]{4}" maxlength="4" placeholder="AB3K">
        </div>
        <div class="col-12">
          <button class="btn btn-success">Войти</button>
        </div>
      </form>
    </div>
  </div>
</div>
