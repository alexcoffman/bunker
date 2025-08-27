<?php
/** @var yii\web\View $this */
/** @var app\models\Game $game */
$this->title = "Стол {$game->code}";
?>
<div class="container" id="boardContainer">
    <?= $this->render('_board', $this->params); ?>
</div>

<script>
    (function() {
        var code = "<?= \yii\helpers\Html::encode($game->code) ?>";
        var container = document.getElementById('boardContainer');
        var lastSeenId = 0;
        var timer = null;

        async function fetchBoard() {
            try {
                const res = await fetch("/game/board?code=" + encodeURIComponent(code), {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                if (res.ok) {
                    const html = await res.text();
                    container.innerHTML = html;
                }
            } catch(e) { /* молча */ }
        }

        async function ping() {
            try {
                const res = await fetch("/game/ping?code=" + encodeURIComponent(code) + "&after=" + lastSeenId, {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.ok) {
                    if (typeof data.lastId === 'number' && data.lastId > lastSeenId) {
                        lastSeenId = data.lastId;
                        fetchBoard();
                    }
                }
            } catch(e) { /* молча */ }
        }

        async function init() {
            // первичная подгрузка и базовый lastId
            await fetchBoard();
            try {
                const res = await fetch("/game/ping?code=" + encodeURIComponent(code) + "&after=0", {
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                if (res.ok) {
                    const data = await res.json();
                    if (data && data.ok && typeof data.lastId === 'number') lastSeenId = data.lastId;
                }
            } catch(e) {}
            // короткий опрос раз в 2 сек
            timer = setInterval(ping, 2000);
            document.getElementById('autorefresh-indicator')?.replaceChildren(document.createTextNode('Живое обновление (каждые 2с)'));
        }

        init();
    })();
</script>
