<?php
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use app\models\CardType;

/** @var ActiveDataProvider $dataProvider */
/** @var string|null $q */
/** @var string|null $status */

$this->title = 'Типы карт';
$kinds = CardType::kinds();
$kindOptions = ['' => 'Все виды'] + array_combine(array_keys($kinds), array_values($kinds));
$selectedKind = Yii::$app->request->get('kind', '');
?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= Html::encode($this->title) ?></h3>
        <div>
            <a class="btn btn-primary" href="<?= Url::to(['admin/card-type-create']) ?>">Добавить тип</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['admin/index']) ?>">Назад</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= Url::to(['admin/card-types']) ?>">
        <div class="col-md-3">
            <input class="form-control" name="q" value="<?= Html::encode($q) ?>" placeholder="Поиск по названию/коду">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="status">
                <option value="">Все статусы</option>
                <?php foreach (['active'=>'active','hidden'=>'hidden','archived'=>'archived'] as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $status===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" name="kind">
                <?php foreach ($kindOptions as $k => $label): ?>
                    <option value="<?= Html::encode($k) ?>" <?= $selectedKind===$k?'selected':'' ?>>
                        <?= Html::encode($label) ?>
                    </option>
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
            'title',
            'code',
            [
                'attribute' => 'kind',
                'value' => function($m){ return CardType::kinds()[$m->kind] ?? $m->kind; }
            ],
            'status',
            'sort_order',
            [
                'label' => '#Карт',
                'value' => function($model) {
                    return (int)\app\models\Card::find()->where(['type_id'=>$model->id])->count();
                }
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{cards} {edit}',
                'buttons' => [
                    'cards' => function($url,$model){
                        return Html::a('Карты', ['admin/cards','type_id'=>$model->id], ['class'=>'btn btn-sm btn-outline-secondary']);
                    },
                    'edit' => function($url,$model){
                        return Html::a('Редактировать', ['admin/card-type-edit','id'=>$model->id], ['class'=>'btn btn-sm btn-primary ms-2']);
                    },
                ],
            ],
        ],
    ]) ?>
</div>
