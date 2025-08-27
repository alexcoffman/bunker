<?php
use yii\db\Migration;

/**
 * Храним, кто ходил первым в прошлом раунде.
 * Ограничение: один и тот же игрок не может быть первым два раунда подряд.
 */
class m250826_150000_game_last_first extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'last_first_player_id', $this->integer()->null());
        $this->createIndex('idx_game_last_first_player', '{{%game}}', 'last_first_player_id');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_game_last_first_player', '{{%game}}');
        $this->dropColumn('{{%game}}', 'last_first_player_id');
    }
}
