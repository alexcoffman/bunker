<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var \app\models\CardType $type */
/** @var \app\models\Card $model */
/** @var bool $isNew */

$this->title = ($isNew ? 'Новая' : 'Редактирование') . ' карты — ' . $type->title;
?>
<div class="container" style="max-width:820px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= Html::encode($this->title) ?></h3>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['admin/cards','type_id'=>$type->id]) ?>">Назад</a>
    </div>

    <?php $form = ActiveForm::begin(); ?>
    <?= $form->field($model, 'text')->textarea(['rows'=>6]) ?>
    <?php if ($type->code === 'SPECIAL'): ?>
        <?= $form->field($model, 'action')->textInput(['maxlength'=>32]) ?>
    <?php endif; ?>
    <?= $form->field($model, 'weight')->input('number', ['min'=>1, 'step'=>1]) ?>
    <?= $form->field($model, 'status')->dropDownList(['active'=>'active','hidden'=>'hidden','archived'=>'archived']) ?>

    <button class="btn btn-primary">Сохранить</button>
    <?php ActiveForm::end(); ?>
</div>
