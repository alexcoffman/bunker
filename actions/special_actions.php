<?php
return [
    'peregolosovanie' => function(\app\controllers\GameController $gc, \app\models\Game $game, \app\models\GameCard $card) {
        if ($game->phase !== 'VOTE') return false;
        $gc->pushEvent($game->id, 'vote_restart', ['round'=>$game->round_no]);
        return true;
    },
];
