<?php
/** @var app\models\Game $game */
/** @var app\models\GamePlayer[] $players */
/** @var string|null $nickname */
/** @var app\models\GamePlayer|null $current */
/** @var app\models\GameCard[] $publicCards */
/** @var app\models\GameCard[] $myCards */
/** @var int|null $onMoveId */
/** @var array<string, array{title:string,sort_order:int}> $types */
/** @var array<int, app\models\GameCard[]> $cardsByPlayer */
/** @var app\models\GamePlayer[] $alivePlayers */
/** @var int[] $votingRounds */

use yii\helpers\Html;
use yii\helpers\Url;

$getTitle = function(string $code) use ($types): string { return (string)($types[$code]['title'] ?? $code); };
$stripBunkerPrefix = function(string $text): string { return preg_replace('/^Карта бункера #\d+:\s*/u', '', $text); };
?>
<!-- Шапка -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <?php if ($nickname): ?><?= Html::encode($nickname) ?>, игра <?= Html::encode($game->code) ?>
        <?php else: ?>Игра <?= Html::encode($game->code) ?><?php endif; ?>
        <?php if ($game->status === 'LIVE'): ?>
            <span class="badge bg-info text-dark ms-2">Раунд: <?= (int)$game->round_no ?> / <?= (int)$game->total_rounds ?></span>
        <?php endif; ?>
        <?php if (!empty($votingRounds)): ?>
            <span class="badge bg-light text-dark ms-1">Голосования: <?= Html::encode(implode(', ', $votingRounds)) ?></span>
        <?php endif; ?>
    </h5>
    <div class="d-flex align-items-center">
        <form method="get" action="<?= Url::to(['/game/' . $game->code]) ?>" class="me-2">
            <button class="btn btn-outline-secondary btn-sm">Обновить</button>
        </form>
        <span class="text-muted small" id="autorefresh-indicator">Живое обновление (каждые 2с)</span>
    </div>
</div>
<?php if ($game->status === 'FINISHED' && $current): ?>
    <?php if ((int)$current->is_alive === 1): ?>
        <div class="alert alert-success">Вы победили и попали в бункер!</div>
    <?php else: ?>
        <div class="alert alert-danger">Вы проиграли и не попали в бункер!</div>
    <?php endif; ?>
<?php endif; ?>
<?php if (!$nickname): ?>
    <div class="alert alert-info">Вы ещё не в игре. Введите ник, чтобы присоединиться.</div>
    <form method="post" action="<?= Url::to(['/game/join']) ?>" class="row g-2 mb-4">
        <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
        <input type="hidden" name="code" value="<?= Html::encode($game->code) ?>">
        <div class="col-md-6">
            <label class="form-label">Ник</label>
            <input class="form-control" name="nickname" required minlength="2" maxlength="32" placeholder="Ваш ник">
        </div>
        <div class="col-md-6 align-self-end">
            <button class="btn btn-success">Войти в игру</button>
        </div>
    </form>
<?php endif; ?>

<?php if ($game->status === 'LIVE' && $game->phase === 'DISCUSS'): ?>
    <div class="alert alert-warning py-2">
        <?php
        $who = null;
        foreach ($players as $p) { if ((int)$p->id === (int)$onMoveId) { $who = $p; break; } }
        ?>
        <?php if ($who): ?>
            Ход игрока: <strong><?= Html::encode($who->nickname) ?></strong>
            <?php if ((int)$game->round_no === 1): ?> — в этом раунде можно открыть <strong>только PROFESSION</strong>.
            <?php else: ?> — можно открыть любую свою закрытую карту.<?php endif; ?>
        <?php else: ?>Идёт подготовка очереди ходов...<?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($game->status === 'LIVE' && $game->phase === 'VOTE'): ?>
    <div class="alert alert-dark py-2">
        Итоги раунда <?= (int)$game->round_no ?> — голосование. HOST исключает одного игрока.
    </div>
    <?php if ($current && $current->role === 'HOST'): ?>
        <div class="card mb-3">
            <div class="card-header">Исключить игрока</div>
            <div class="card-body">
                <div class="row g-2">
                    <?php foreach ($alivePlayers as $p): ?>
                        <div class="col-6 col-md-4">
                            <form method="post" action="<?= Url::to(['/game/eliminate', 'code'=>$game->code, 'player_id'=>$p->id]) ?>"
                                  onsubmit="return confirm('Исключить игрока <?= Html::encode($p->nickname) ?>?');">
                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                <button class="btn w-100 btn-outline-danger"><?= Html::encode($p->nickname) ?></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text mt-2">Игроки голосуют вживую. Здесь только выбор исключаемого.</div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary py-2">HOST выбирает, кого исключить…</div>
    <?php endif; ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <h5>Игроки и их карты</h5>
        <?php foreach ($players as $p): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between">
                    <div>
                        <?= Html::encode($p->nickname) ?>
                        <?php if ($p->role === 'HOST'): ?><span class="badge bg-primary ms-2">HOST</span><?php endif; ?>
                        <?php if ((int)$p->is_alive !== 1): ?><span class="badge bg-dark ms-2">исключён</span><?php endif; ?>
                        <?php if ((int)$p->id === (int)$onMoveId && $game->phase==='DISCUSS'): ?>
                            <span class="badge bg-warning text-dark ms-2">ходит</span>
                        <?php endif; ?>
                    </div>
                    <small class="text-muted">с <?= date('H:i', $p->joined_at) ?></small>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead><tr><th style="width:35%">Тип карты</th><th>Значение карты</th></tr></thead>
                        <tbody>
                        <?php
                        $pcs = $cardsByPlayer[(int)$p->id] ?? [];
                        if (!$pcs) {
                            echo '<tr><td colspan="2" class="text-muted">Нет карт</td></tr>';
                        } else {
                            foreach ($pcs as $c) {
                                $title = Html::encode($getTitle($c->type_code));
                                $isRevealed = (int)$c->is_revealed === 1;
                                $val   = $isRevealed ? nl2br(Html::encode($c->card_text)) : '<span class="text-muted">Скрыто</span>';

                                // Угроза — выделяем красным весь ряд
                                $rowClass = ($c->type_code === 'THREAT') ? 'class="table-danger"' : '';

                                echo "<tr {$rowClass}><td>{$title}</td><td>{$val}</td></tr>";
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>

        <h5>Публичная информация</h5>
        <?php if (!$publicCards): ?>
            <div class="text-muted mb-4">Пока пусто.</div>
        <?php else: ?>
            <div class="list-group mb-4">
                <?php foreach ($publicCards as $c): ?>
                    <?php
                    $title = $getTitle($c->type_code);
                    $value = ($c->type_code === 'BUNKER') ? $stripBunkerPrefix($c->card_text) : $c->card_text;
                    ?>
                    <div class="list-group-item<?= $c->type_code==='THREAT' ? ' list-group-item-danger' : '' ?>">
                        <div><strong><?= Html::encode($title) ?></strong>: <?= nl2br(Html::encode($value)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-6">
        <?php if ($current): ?>
            <h5>Мои карты</h5>
            <?php if (!$myCards): ?>
                <div class="text-muted">Пусто.</div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($myCards as $c): ?>
                        <?php
                        $revealed = (int)$c->is_revealed === 1;
                        $titleCls = $revealed ? 'text-success' : 'text-secondary';
                        $title = Html::encode($getTitle($c->type_code));
                        ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start<?= $c->type_code==='THREAT' ? ' list-group-item-danger' : '' ?>">
                              <div class="me-3">
                                  <div class="fw-bold <?= $titleCls ?>"><?= $title ?></div>
                                  <div><?= nl2br(Html::encode($c->card_text)) ?></div>
                              </div>

                              <?php
                              $isMyTurn = ($game->phase==='DISCUSS') && $onMoveId && ((int)$onMoveId === (int)$current->id);
                              $canRevealThis =
                                  $isMyTurn
                                  && !$revealed
                                  && (
                                      ((int)$game->round_no === 1 && $c->type_code === 'PROFESSION')
                                      || ((int)$game->round_no >= 2)
                                  );
                              ?>
                                <div class="text-end">
                                    <?php if ($c->type_code === 'SPECIAL' && $c->action === 'peregolosovanie' && (int)$c->is_revealed !== 1 && $game->phase==='VOTE'): ?>
                                        <form method="post" action="<?= Url::to(['/game/special', 'code' => $game->code, 'card_id' => $c->id]) ?>"
                                              onsubmit="return confirm('Запустить новое голосование?');">
                                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                            <button class="btn btn-sm btn-outline-danger">Переголосовать</button>
                                        </form>
                                    <?php elseif ($c->type_code === 'SPECIAL' && $c->action === 'mne-nuzhnee'): ?>
                                        <?php if ((int)$c->is_revealed !== 1): ?>
                                            <form method="post" action="<?= Url::to(['/game/special', 'code' => $game->code, 'card_id' => $c->id]) ?>">
                                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                                <select name="target_id" class="form-select form-select-sm mb-1">
                                                    <?php foreach ($players as $p): if ($p->id === $current->id) continue; ?>
                                                        <option value="<?= $p->id ?>"><?= Html::encode($p->nickname) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn-sm btn-outline-danger">Забрать багаж</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Забрать багаж</button>
                                        <?php endif; ?>
                                    <?php elseif ($c->type_code === 'SPECIAL' && in_array($c->action, [
                                        'davaite-nachistotu-bagazh',
                                        'davaite-nachistotu-fakty',
                                        'davaite-nachistotu-hobbi',
                                        'davaite-nachistotu-zdorovie',
                                        'davaite-nachistotu-biologia',
                                        'davaite-nachistotu-fobia',
                                    ])): ?>
                                        <?php if ((int)$c->is_revealed !== 1): ?>
                                            <form method="post" action="<?= Url::to(['/game/special', 'code' => $game->code, 'card_id' => $c->id]) ?>">
                                                <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                                <button class="btn btn-sm btn-outline-danger">Перемешать</button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>Перемешать</button>
                                        <?php endif; ?>
                                    <?php elseif ($canRevealThis): ?>
                                        <form method="post" action="<?= Url::to(['/game/reveal', 'code' => $game->code, 'card_id' => $c->id]) ?>">
                                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                                            <button class="btn btn-sm btn-outline-primary">Открыть</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>Открыть</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
              <?php endif; ?>

            <?php if ($current->role === 'HOST'): ?>
                <div class="mt-4 d-flex gap-2">
                    <?php if ($game->status === 'PREP'): ?>
                        <form method="post" action="<?= Url::to(['/game/start', 'code' => $game->code]) ?>">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                            <button class="btn btn-primary">Начать игру</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($game->status !== 'FINISHED' && $game->status !== 'PREP'): ?>
                        <form method="post" action="<?= Url::to(['/game/finish', 'code' => $game->code]) ?>"
                              onsubmit="return confirm('Завершить игру?')">
                            <?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->getCsrfToken()) ?>
                            <button class="btn btn-danger">Завершить игру</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
