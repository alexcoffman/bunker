<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use app\models\CardType;

/** @var \app\models\CardType $model */
/** @var bool $isNew */

$this->title = $isNew ? 'Новый тип' : 'Редактирование типа';
$kinds = CardType::kinds();
?>
<div class="container" style="max-width:720px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= Html::encode($this->title) ?></h3>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['admin/card-types']) ?>">Назад</a>
    </div>

    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'title')->textInput(['maxlength'=>64]) ?>
    <?= $form->field($model, 'code')->textInput(['maxlength'=>32])->hint('A-Z, 0-9, без пробелов') ?>
    <?= $form->field($model, 'kind')->dropDownList($kinds) ?>
    <?= $form->field($model, 'description')->textarea(['rows'=>3]) ?>
    <?= $form->field($model, 'status')->dropDownList(['active'=>'active','hidden'=>'hidden','archived'=>'archived']) ?>
    <?= $form->field($model, 'sort_order')->input('number') ?>

    <button class="btn btn-primary">Сохранить</button>
    <?php ActiveForm::end(); ?>
</div>
