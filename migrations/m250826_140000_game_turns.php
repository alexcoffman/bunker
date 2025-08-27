<?php
use yii\db\Migration;

/**
 * Добавляем поля очередей ходов в таблицу game.
 * - turn_order: JSON-массив ID игроков в порядке ходов текущего раунда
 * - current_turn_index: индекс текущего игрока в turn_order (0-базный)
 */
class m250826_140000_game_turns extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'turn_order', $this->text()->null());
        $this->addColumn('{{%game}}', 'current_turn_index', $this->smallInteger()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game}}', 'current_turn_index');
        $this->dropColumn('{{%game}}', 'turn_order');
    }
}
