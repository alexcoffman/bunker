<?php
return [
    'peregolosovanie' => function(
        \app\controllers\GameController $gc,
        \app\models\Game $game,
        \app\models\GameCard $card,
        \app\models\GamePlayer $me = null,
        ?int $targetId = null
    ) {
        if ($game->phase !== 'VOTE') return false;
        $gc->pushEvent($game->id, 'vote_restart', ['round' => $game->round_no]);
        return true;
    },
    'mne-nuzhnee' => function(
        \app\controllers\GameController $gc,
        \app\models\Game $game,
        \app\models\GameCard $card,
        \app\models\GamePlayer $me,
        ?int $targetId = null
    ) {
        if (!$targetId) return false;
        $target = \app\models\GamePlayer::findOne([
            'game_id' => $game->id,
            'id'      => $targetId,
        ]);
        if (!$target || (int)$target->id === (int)$me->id) return false;
        $bag = \app\models\GameCard::findOne([
            'game_id'   => $game->id,
            'player_id' => $target->id,
            'type_code' => 'BAGGAGE',
        ]);
        if (!$bag) return false;
        $bag->player_id = $me->id;
        $bag->save(false);
        $gc->pushEvent($game->id, 'baggage_stolen', ['from' => $target->id, 'to' => $me->id]);
        return true;
    },
];
