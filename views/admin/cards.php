<?php
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;

/** @var \app\models\CardType $type */
/** @var ActiveDataProvider $dataProvider */
/** @var string|null $q */
/** @var string|null $status */

$this->title = 'Карты: ' . $type->title;
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= Html::encode($this->title) ?></h3>
        <div>
            <a class="btn btn-primary" href="<?= Url::to(['admin/card-create','type_id'=>$type->id]) ?>">Добавить карту</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['admin/card-types']) ?>">К типам</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get" action="">
        <input type="hidden" name="type_id" value="<?= (int)$type->id ?>">
        <div class="col-md-6">
            <input class="form-control" name="q" value="<?= Html::encode($q) ?>" placeholder="Поиск в тексте">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Все статусы</option>
                <?php foreach (['active','hidden','archived'] as $k): ?>
                    <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $k ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary">Фильтр</button>
        </div>
    </form>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            'id',
            [
                'attribute' => 'text',
                'format' => 'ntext',
            ],
            'action',
            'weight',
            'status',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{edit}',
                'buttons' => [
                    'edit' => function($url,$model){
                        return Html::a('Редактировать', ['admin/card-edit','id'=>$model->id], ['class'=>'btn btn-sm btn-primary']);
                    },
                ],
            ],
        ],
    ]) ?>
</div>
