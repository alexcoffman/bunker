<?php
$shuffle = function(string $typeCode, string $event) {
    return function(
        \app\controllers\GameController $gc,
        \app\models\Game $game,
        \app\models\GameCard $card,
        \app\models\GamePlayer $me,
        ?int $targetId = null
    ) use ($typeCode, $event) {
        $cards = \app\models\GameCard::find()->where([
            'game_id'   => $game->id,
            'type_code' => $typeCode,
            'is_public' => 1,
        ])->all();
        if (count($cards) < 2) return false;
        $counts = [];
        foreach ($cards as $c) {
            $pid = (int)$c->player_id;
            $counts[$pid] = ($counts[$pid] ?? 0) + 1;
        }
        $players = array_keys($counts);
        shuffle($cards);
        $i = 0;
        foreach ($players as $pid) {
            for ($j = 0; $j < $counts[$pid]; $j++) {
                $cards[$i]->player_id = $pid;
                $cards[$i]->save(false);
                $i++;
            }
        }
        $gc->pushEvent($game->id, $event, []);
        return true;
    };
};
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
    'davaite-nachistotu-bagazh'   => $shuffle('BAGGAGE', 'baggage_shuffle'),
    'davaite-nachistotu-fakty'    => $shuffle('FACTS', 'facts_shuffle'),
    'davaite-nachistotu-hobbi'    => $shuffle('HOBBY', 'hobby_shuffle'),
    'davaite-nachistotu-zdorovie' => $shuffle('health', 'health_shuffle'),
    'davaite-nachistotu-biologia' => $shuffle('BIOLOGY', 'biology_shuffle'),
    'davaite-nachistotu-fobia'    => $shuffle('PHOBIA', 'phobia_shuffle'),
];
