<?php
namespace app\controllers;

use app\models\Game;
use app\models\GamePlayer;
use app\models\GameCard;
use app\models\GameEvent;
use app\models\Card;
use app\models\CardType;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class GameController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create'     => ['POST'],
                    'join'       => ['POST'],
                    'start'      => ['POST'],
                    'finish'     => ['POST'],
                    'reveal'     => ['POST'],
                      'eliminate'  => ['POST'],
                      'special'    => ['POST'],
                      'board'      => ['GET'],
                      'ping'       => ['GET'],
                  ],
              ],
          ];
    }

    /* ===== Служебные: события ===== */
    public function pushEvent(int $gameId, string $type, array $payload = []): void
    {
        $e = new GameEvent([
            'game_id'    => $gameId,
            'type'       => $type,
            'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'created_at' => time(),
        ]);
        $e->save(false);
    }

    /* ===== Создание/вступление/экраны ===== */
    public function actionCreate()
    {
        $code = Game::generateCode();
        $game = new Game([
            'code'        => $code,
            'status'      => 'PREP',
            'max_players' => 12,
            'phase'       => 'PREP',
            'round_no'    => 0,
            'created_at'  => time(),
            'total_rounds'=> 7,
        ]);
        if (!$game->save()) return $this->redirect(['/']);
        Yii::$app->session->set("game.$code.isCreator", true);
        Yii::$app->session->setFlash('gameCreatedCode', $code);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionJoin()
    {
        $req  = Yii::$app->request;
        $code = strtoupper(trim($req->post('code', '')));
        $nick = trim($req->post('nickname', ''));

        if (!preg_match('/^[A-Z0-9]{4}$/', $code) || mb_strlen($nick) < 2) return $this->redirect(['/']);

        $game = Game::find()->where(['code' => $code])->one();
        if (!$game || $game->status !== 'PREP') return $this->redirect(['/']);

        $exists = GamePlayer::find()->where(['game_id' => $game->id, 'nickname' => $nick])->exists();
        if ($exists) return $this->redirect(['/']);

        $count = (int) GamePlayer::find()->where(['game_id' => $game->id, 'left_at' => null])->count();
        if ($count >= (int) $game->max_players) return $this->redirect(['/']);

        $role = 'PLAYER';
        $hostExists = GamePlayer::find()->where(['game_id' => $game->id, 'role' => 'HOST'])->exists();
        $isCreator  = (bool) Yii::$app->session->get("game.$code.isCreator", false);
        $wantsHost  = strtolower($req->post('as', $req->get('as', ''))) === 'host';
        if (!$hostExists && ($isCreator || $wantsHost)) $role = 'HOST';

        $gp = new GamePlayer([
            'game_id'   => $game->id,
            'nickname'  => $nick,
            'role'      => $role,
            'is_alive'  => 1,
            'joined_at' => time(),
        ]);
        if ($gp->save()) $this->pushEvent($game->id, 'player_join', ['nickname'=>$nick]);

        Yii::$app->session->set("game.$code.nickname", $nick);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionView($code)
    {
        $code = strtoupper($code);
        $game = Game::find()->where(['code' => $code])->one();
        if (!$game) throw new NotFoundHttpException('Игра не найдена.');
        $this->loadBoardData($game);
        return $this->render('view', ['game'=>$game]);
    }

    // HTML-фрагмент доски (AJAX)
    public function actionBoard($code)
    {
        $this->enableCsrfValidation = false;
        $this->layout = false;
        Yii::$app->response->format = Response::FORMAT_HTML;

        $code = strtoupper($code);
        $game = Game::find()->where(['code' => $code])->one();
        if (!$game) throw new NotFoundHttpException('Игра не найдена.');

        $this->loadBoardData($game);
        return $this->render('_board', $this->view->params);
    }

    // Лёгкий ping: отдаём lastId, чтобы клиент понял — есть ли обновления
    public function actionPing($code, $after = 0)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $code = strtoupper($code);
        $game = Game::findOne(['code'=>$code]);
        if (!$game) return ['ok'=>false,'error'=>'not_found'];

        $after = (int)$after;
        $maxId = GameEvent::find()->where(['game_id'=>$game->id])->max('id');
        $maxId = $maxId ? (int)$maxId : 0;

        return ['ok'=>true, 'lastId'=>$maxId, 'changed'=>($maxId > $after)];
    }

    private function loadBoardData(Game $game): void
    {
        $players  = GamePlayer::find()->where(['game_id' => $game->id])->orderBy(['id' => SORT_ASC])->all();
        $nickname = Yii::$app->session->get("game.$game->code.nickname");
        $current  = $nickname ? GamePlayer::find()->where(['game_id'=>$game->id,'nickname'=>$nickname])->one() : null;

        $types = (new \yii\db\Query())
            ->from('{{%card_type}}')->select(['code','title','sort_order'])->indexBy('code')->all();

        $publicCards = GameCard::find()
            ->where(['game_id' => $game->id, 'is_public' => 1])
            ->andWhere(['type_code' => ['CATASTROPHE','BUNKER','THREAT']])
            ->orderBy(['id' => SORT_ASC])->all();

        $myCards = $current ? GameCard::find()
            ->where(['game_id' => $game->id, 'player_id' => $current->id])
            ->orderBy(['id' => SORT_ASC])->all() : [];

        $playerCardsAll = GameCard::find()
            ->where(['game_id' => $game->id])->andWhere(['not', ['player_id' => null]])->all();

        $cardsByPlayer = [];
        foreach ($playerCardsAll as $gc) { $cardsByPlayer[(int)$gc->player_id][] = $gc; }
        foreach ($cardsByPlayer as $pid => &$arr) {
            usort($arr, function(GameCard $a, GameCard $b) use ($types) {
                $sa = (int)($types[$a->type_code]['sort_order'] ?? 1000);
                $sb = (int)($types[$b->type_code]['sort_order'] ?? 1000);
                return $sa === $sb ? strcmp($a->type_code, $b->type_code) : ($sa <=> $sb);
            });
        } unset($arr);

        $turnOrder = $this->getTurnOrder($game);
        $turnIdx   = (int)$game->current_turn_index;
        $onMoveId  = $turnOrder[$turnIdx] ?? null;

        $alivePlayers = GamePlayer::find()->where(['game_id'=>$game->id, 'is_alive'=>1])->orderBy(['id'=>SORT_ASC])->all();
        $votingRounds = $this->getVotingRounds($game);
        $specialUsed = GameCard::find()->where(['game_id'=>$game->id, 'type_code'=>'SPECIAL', 'is_revealed'=>1])->exists();

        $this->view->params = array_merge($this->view->params, [
            'game'          => $game,
            'players'       => $players,
            'nickname'      => $nickname,
            'current'       => $current,
            'publicCards'   => $publicCards,
            'myCards'       => $myCards,
            'onMoveId'      => $onMoveId,
            'turnOrder'     => $turnOrder,
            'cardsByPlayer' => $cardsByPlayer,
            'types'         => $types,
            'alivePlayers'  => $alivePlayers,
            'votingRounds'  => $votingRounds,
            'specialUsed'   => $specialUsed,
        ]);
    }

    /* ===== Игровые действия ===== */
    public function actionStart($code)
    {
        $code = strtoupper($code);
        $game = Game::find()->where(['code' => $code])->one();
        if (!$game || $game->status !== 'PREP') return $this->redirect(['/game/'.$code]);

        $me = $this->findCurrentPlayer($game);
        if (!$me || $me->role !== 'HOST') return $this->redirect(['/game/' . $code]);

        $aliveCount = (int) GamePlayer::find()->where(['game_id'=>$game->id, 'is_alive'=>1])->count();
        if ($aliveCount < 2 || $aliveCount > 12) return $this->redirect(['/game/' . $code]);

        [$rounds, $survivors] = $this->computeVotingConfig($aliveCount);

        $game->status               = 'LIVE';
        $game->phase                = 'DISCUSS';
        $game->round_no             = 1;
        $game->started_at           = time();
        $game->last_first_player_id = null;
        $game->total_rounds         = 7;
        $game->initial_player_count = $aliveCount;
        $game->voting_rounds        = json_encode(array_values($rounds));
        $game->survivors_target     = $survivors;
        $game->save(false);

        $this->dealInitialCardsByKinds($game->id);
        $this->resetTurnOrderRandom($game, false);

        $this->pushEvent($game->id, 'game_start', ['round'=>1]);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionFinish($code)
    {
        $code = strtoupper($code);
        $game = Game::find()->where(['code' => $code])->one();
        if (!$game || $game->status === 'FINISHED') return $this->redirect(['/game/' . $code]);

        $me = $this->findCurrentPlayer($game);
        if (!$me || $me->role !== 'HOST') return $this->redirect(['/game/' . $code]);

        // Переводим игру в конец
        $game->status      = 'FINISHED';
        $game->phase       = 'END';
        $game->finished_at = time();
        $game->save(false);

        // Раздаём угрозы всем игрокам
        $this->dealThreats($game);

        $this->pushEvent($game->id, 'game_finish', []);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionReveal($code, $card_id)
    {
        $code = strtoupper($code);
        $game = Game::find()->where(['code' => $code])->one();
        if (!$game || $game->status !== 'LIVE' || $game->phase !== 'DISCUSS') return $this->redirect(['/game/' . $code]);

        $me = $this->findCurrentPlayer($game);
        if (!$me) return $this->redirect(['/game/' . $code]);

        $turnOrder = $this->getTurnOrder($game);
        $turnIdx   = (int)$game->current_turn_index;
        $onMoveId  = $turnOrder[$turnIdx] ?? null;
        if ($onMoveId !== (int)$me->id) return $this->redirect(['/game/' . $code]);

        $card = GameCard::findOne(['id' => (int)$card_id, 'game_id' => $game->id, 'player_id' => $me->id]);
        if (!$card || (int)$card->is_revealed === 1) return $this->redirect(['/game/' . $code]);
        if ((int)$game->round_no === 1 && $card->type_code !== 'PROFESSION') return $this->redirect(['/game/' . $code]);

        $card->is_revealed = 1;
        $card->is_public   = 1;
        $card->revealed_at = time();
        $card->save(false);

        $this->pushEvent($game->id, 'card_reveal', ['player_id'=>$me->id, 'type'=>$card->type_code]);

        $this->advanceTurnOrRound($game);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionEliminate($code, $player_id)
    {
        $code = strtoupper($code);
        $game = Game::findOne(['code'=>$code]);
        if (!$game || $game->status!=='LIVE' || $game->phase!=='VOTE') return $this->redirect(['/game/' . $code]);

        $me = $this->findCurrentPlayer($game);
        if (!$me || $me->role!=='HOST') return $this->redirect(['/game/' . $code]);

        $target = GamePlayer::findOne(['id'=>(int)$player_id, 'game_id'=>$game->id]);
        if (!$target || (int)$target->is_alive !== 1) return $this->redirect(['/game/' . $code]);

        $target->is_alive = 0;
        $target->left_at  = time();
        $target->save(false);

        $now = time();
        GameCard::updateAll(
            ['is_public'=>1, 'is_revealed'=>1, 'revealed_at'=>$now],
            ['game_id'=>$game->id, 'player_id'=>$target->id]
        );

        // If eliminated player had "tainaya-ugroza", remember extra threat for end game
        $secretCount = (int) GameCard::find()->where([
            'game_id'   => $game->id,
            'player_id' => $target->id,
            'type_code' => 'SPECIAL',
            'action'    => 'tainaya-ugroza',
        ])->count();
        if ($secretCount > 0) {
            $game->extra_threats = ((int)$game->extra_threats) + $secretCount;
            $game->save(false);
            $this->pushEvent($game->id, 'tainaya_ugroza', ['count' => $secretCount]);
        }

        // If eliminated player had "diversia", hide random public bunker cards
        $diversiaCount = (int) GameCard::find()->where([
            'game_id'   => $game->id,
            'player_id' => $target->id,
            'type_code' => 'SPECIAL',
            'action'    => 'diversia',
        ])->count();
        if ($diversiaCount > 0) {
            for ($i = 0; $i < $diversiaCount; $i++) {
                $open = GameCard::find()->where([
                    'game_id'   => $game->id,
                    'type_code' => 'BUNKER',
                    'is_public' => 1,
                ])->all();
                if (!$open) break;
                $hide = $open[random_int(0, count($open) - 1)];
                $hide->is_public = 0;
                $hide->save(false);
                $this->pushEvent($game->id, 'bunker_hide', ['card_id' => $hide->id]);
            }
        }

        $this->pushEvent($game->id, 'player_eliminate', ['player_id'=>$target->id]);
        $this->advanceRound($game);
        return $this->redirect(['/game/' . $code]);
    }

    public function actionSpecial($code, $card_id)
    {
        $code = strtoupper($code);
        $game = Game::findOne(['code'=>$code]);
        if (!$game || $game->status !== 'LIVE') return $this->redirect(['/game/' . $code]);

        $me = $this->findCurrentPlayer($game);
        if (!$me) return $this->redirect(['/game/' . $code]);

        $card = GameCard::findOne([
            'id'        => (int)$card_id,
            'game_id'   => $game->id,
            'player_id' => $me->id,
            'type_code' => 'SPECIAL',
        ]);
        if (!$card || (int)$card->is_revealed === 1) return $this->redirect(['/game/' . $code]);

        // Глобально особую карту можно использовать только один раз за игру.
        $specialUsed = GameCard::find()
            ->where(['game_id' => $game->id, 'type_code' => 'SPECIAL', 'is_revealed' => 1])
            ->exists();
        if ($specialUsed) return $this->redirect(['/game/' . $code]);

        $targetId = (int)Yii::$app->request->post('target_id', 0);

        $actions = require Yii::getAlias('@app/actions/special_actions.php');
        $handler = $actions[$card->action] ?? null;
        if (is_callable($handler)) {
            $res = $handler($this, $game, $card, $me, $targetId);
            if ($res !== false) {
                $card->is_public = 1;
                $card->is_revealed = 1;
                $card->revealed_at = time();
                $card->save(false);
            }
        }

        return $this->redirect(['/game/' . $code]);
    }

    /* ===== Ход игры ===== */
    private function findCurrentPlayer(Game $game): ?GamePlayer
    {
        $nick = Yii::$app->session->get("game.$game->code.nickname");
        if (!$nick) return null;
        return GamePlayer::find()->where(['game_id' => $game->id, 'nickname' => $nick])->one();
    }

    private function getTurnOrder(Game $game): array
    {
        if (!$game->turn_order) return [];
        $arr = json_decode($game->turn_order, true);
        return is_array($arr) ? array_map('intval', $arr) : [];
    }

    private function setTurnOrder(Game $game, array $ids): void
    {
        $ids = array_values(array_map('intval', $ids));
        $game->turn_order = json_encode($ids);
        $game->current_turn_index = 0;
        $game->last_first_player_id = $ids[0] ?? null;
        $game->save(false);
        $this->pushEvent($game->id, 'turn_order', ['first'=>$ids[0] ?? null]);
    }

    private function resetTurnOrderRandom(Game $game, bool $forNextRound): void
    {
        $ids = GamePlayer::find()->select('id')
            ->where(['game_id' => $game->id, 'is_alive'=>1])
            ->column();

        if (count($ids) <= 1) { $this->setTurnOrder($game, $ids); return; }

        $prevFirst = $forNextRound ? (int)$game->last_first_player_id : null;

        $attempts = 0;
        do {
            shuffle($ids);
            $attempts++;
            if ($attempts > 20) {
                if ($prevFirst) {
                    $idx = array_search($prevFirst, $ids, true);
                    if ($idx !== false) { array_splice($ids, $idx, 1); $ids[] = $prevFirst; }
                }
                break;
            }
        } while ($forNextRound && $prevFirst && ((int)$ids[0] === $prevFirst));

        $this->setTurnOrder($game, $ids);
    }

    private function advanceTurnOrRound(Game $game): void
    {
        if ($game->phase === 'VOTE') return;

        $order = $this->getTurnOrder($game);
        $count = count($order);
        $idx   = (int)$game->current_turn_index;

        if ($count === 0) {
            $this->resetTurnOrderRandom($game, (int)$game->round_no >= 1);
            return;
        }

        $idx++;
        if ($idx < $count) {
            $game->current_turn_index = $idx;
            $game->save(false);
            $this->pushEvent($game->id, 'turn_next', ['onMove'=>$order[$idx]]);
            return;
        }
        $this->endOfRound($game);
    }

    private function endOfRound(Game $game): void
    {
        if ($this->roundRequiresVote($game)) {
            $game->phase = 'VOTE';
            $game->save(false);
            $this->pushEvent($game->id, 'phase_vote', ['round'=>$game->round_no]);
            return;
        }
        $this->advanceRound($game);
    }

    private function advanceRound(Game $game): void
    {
        if ($game->phase === 'VOTE') {
            $game->phase = 'DISCUSS';
            $game->save(false);
        }

        if ((int)$game->round_no >= (int)$game->total_rounds) {
            // Игра завершается — выдаём угрозы, затем закрываем
            $game->status      = 'FINISHED';
            $game->phase       = 'END';
            $game->finished_at = time();
            $game->save(false);

            $this->dealThreats($game); // <<< ВЫДАЧА УГРОЗ

            $this->pushEvent($game->id, 'game_finish', []);
            return;
        }

        $game->round_no++;
        $game->save(false);

        // Открыть следующую карту Бункера (раунды 2..6)
        $nextBunker = GameCard::find()
            ->where(['game_id' => $game->id, 'type_code' => 'BUNKER', 'is_revealed' => 0])
            ->orderBy(['id' => SORT_ASC])->one();
        if ($nextBunker) {
            $nextBunker->is_public   = 1;
            $nextBunker->is_revealed = 1;
            $nextBunker->revealed_at = time();
            $nextBunker->save(false);
            $this->pushEvent($game->id, 'bunker_open', ['card_id'=>$nextBunker->id, 'round'=>$game->round_no]);
        }

        $this->resetTurnOrderRandom($game, true);
        $this->pushEvent($game->id, 'round_start', ['round'=>$game->round_no]);
    }

    private function computeVotingConfig(int $count): array
    {
        switch ($count) {
            case 2:  return [[7], 1];
            case 3:  return [[6,7], 1];
            case 4:  return [[6,7], 2];
            case 5:  return [[5,6,7], 2];
            case 6:  return [[5,6,7], 3];
            case 7:  return [[4,5,6,7], 3];
            case 8:  return [[4,5,6,7], 4];
            case 9:  return [[3,4,5,6,7], 4];
            case 10: return [[3,4,5,6,7], 5];
            case 11: return [[2,3,4,5,6,7], 5];
            case 12: return [[2,3,4,5,6,7], 6];
            default: return [[], null];
        }
    }

    private function getVotingRounds(Game $game): array
    {
        if (!$game->voting_rounds) return [];
        $arr = json_decode($game->voting_rounds, true);
        return is_array($arr) ? array_map('intval', $arr) : [];
    }

    private function roundRequiresVote(Game $game): bool
    {
        $rounds = $this->getVotingRounds($game);
        if (!in_array((int)$game->round_no, $rounds, true)) return false;

        $alive = (int) GamePlayer::find()->where(['game_id'=>$game->id,'is_alive'=>1])->count();
        if ($game->survivors_target !== null && $alive <= (int)$game->survivors_target) return false;

        return true;
    }

    private function dealInitialCardsByKinds(int $gameId): void
    {
        $now = time();

        if ($cat = Card::pickTextByTypeCode('CATASTROPHE')) {
            (new GameCard([
                'game_id'     => $gameId,
                'player_id'   => null,
                'type_code'   => 'CATASTROPHE',
                'card_text'   => $cat,
                'is_public'   => 1,
                'is_revealed' => 1,
                'created_at'  => $now,
                'revealed_at' => $now,
            ]))->save(false);
        }

        for ($i = 1; $i <= 5; $i++) {
            if ($b = Card::pickTextByTypeCode('BUNKER')) {
                $revealed = ($i === 1) ? 1 : 0;
                $public   = ($i === 1) ? 1 : 0;
                (new GameCard([
                    'game_id'     => $gameId,
                    'player_id'   => null,
                    'type_code'   => 'BUNKER',
                    'card_text'   => "Карта бункера #$i: " . $b,
                    'is_public'   => $public,
                    'is_revealed' => $revealed,
                    'created_at'  => $now,
                    'revealed_at' => $revealed ? $now : null,
                ]))->save(false);
            }
        }

        $playerTypes = (new \yii\db\Query())
            ->from('{{%card_type}}')
            ->where(['status'=>'active','kind'=>CardType::KIND_PLAYER])
            ->orderBy(['sort_order'=>SORT_ASC, 'id'=>SORT_ASC])
            ->all();

        $players = GamePlayer::find()->where(['game_id' => $gameId])->all();
        foreach ($players as $p) {
            foreach ($playerTypes as $t) {
                $code = $t['code'];
                if ($row = Card::pickOneByTypeCode($code)) {
                    (new GameCard([
                        'game_id'     => $gameId,
                        'player_id'   => $p->id,
                        'type_code'   => $code,
                        'card_text'   => $row['text'],
                        'action'      => $row['action'] ?? null,
                        'is_public'   => 0,
                        'is_revealed' => 0,
                        'created_at'  => $now,
                    ]))->save(false);
                }
            }
            // Раздаём карту особого условия каждому игроку
            if ($row = Card::pickOneByTypeCode('SPECIAL')) {
                (new GameCard([
                    'game_id'     => $gameId,
                    'player_id'   => $p->id,
                    'type_code'   => 'SPECIAL',
                    'card_text'   => $row['text'],
                    'action'      => $row['action'] ?? null,
                    'is_public'   => 0,
                    'is_revealed' => 0,
                    'created_at'  => $now,
                ]))->save(false);
            }
        }
    }

    /** Выдаёт каждому игроку открытую карту THREAT в конце игры. */
    private function dealThreats(Game $game): void
    {
        $now = time();
        $players = GamePlayer::find()->where(['game_id'=>$game->id])->all();
        foreach ($players as $p) {
            if ($t = Card::pickTextByTypeCode('THREAT')) {
                (new GameCard([
                    'game_id'     => $game->id,
                    'player_id'   => $p->id,
                    'type_code'   => 'THREAT',
                    'card_text'   => $t,
                    'is_public'   => 1,
                    'is_revealed' => 1,
                    'created_at'  => $now,
                    'revealed_at' => $now,
                ]))->save(false);
            }
        }
        $this->pushEvent($game->id, 'threat_deal', []);

        // Extra threats from "tainaya-ugroza" special cards
        $extra = (int)$game->extra_threats;
        if ($extra > 0) {
            $alive = GamePlayer::find()->where(['game_id'=>$game->id, 'is_alive'=>1])->all();
            if ($alive) {
                for ($i = 0; $i < $extra; $i++) {
                    $target = $alive[random_int(0, count($alive)-1)];
                    if ($t = Card::pickTextByTypeCode('THREAT')) {
                        (new GameCard([
                            'game_id'     => $game->id,
                            'player_id'   => $target->id,
                            'type_code'   => 'THREAT',
                            'card_text'   => $t,
                            'is_public'   => 1,
                            'is_revealed' => 1,
                            'created_at'  => $now,
                            'revealed_at' => $now,
                        ]))->save(false);
                        $this->pushEvent($game->id, 'threat_extra', ['player_id'=>$target->id]);
                    }
                }
            }
            $game->extra_threats = 0;
            $game->save(false);
        }
    }
}
