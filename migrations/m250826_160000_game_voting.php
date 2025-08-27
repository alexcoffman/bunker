<?php
use yii\db\Migration;

/**
 * Поля для голосований и конфигурации раундов.
 */
class m250826_160000_game_voting extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'total_rounds', $this->smallInteger()->notNull()->defaultValue(7));
        $this->addColumn('{{%game}}', 'initial_player_count', $this->smallInteger()->null());
        $this->addColumn('{{%game}}', 'voting_rounds', $this->text()->null()); // JSON-массив номеров раундов
        $this->addColumn('{{%game}}', 'survivors_target', $this->smallInteger()->null()); // целевое число выживших
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game}}', 'survivors_target');
        $this->dropColumn('{{%game}}', 'voting_rounds');
        $this->dropColumn('{{%game}}', 'initial_player_count');
        $this->dropColumn('{{%game}}', 'total_rounds');
    }
}
