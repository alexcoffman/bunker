<?php
use yii\db\Migration;

/**
 * Инициализация таблиц для столов и игроков.
 */
class m250826_101500_init_game_tables extends Migration
{
    public function safeUp()
    {
        // game
        $this->createTable('{{%game}}', [
            'id' => $this->primaryKey(),
            'code' => $this->char(4)->notNull()->unique(),
            'status' => $this->string(16)->notNull()->defaultValue('PREP'),
            'max_players' => $this->smallInteger()->notNull()->defaultValue(12),
            'phase' => $this->string(16)->notNull()->defaultValue('PREP'),
            'round_no' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'started_at' => $this->integer()->null(),
            'finished_at' => $this->integer()->null(),
        ]);

        // game_player
        $this->createTable('{{%game_player}}', [
            'id' => $this->primaryKey(),
            'game_id' => $this->integer()->notNull(),
            'nickname' => $this->string(32)->notNull(),
            'role' => $this->string(8)->notNull()->defaultValue('PLAYER'),
            'is_alive' => $this->boolean()->notNull()->defaultValue(true),
            'joined_at' => $this->integer()->notNull(),
            'left_at' => $this->integer()->null(),
        ]);

        $this->createIndex('idx_game_player_game', '{{%game_player}}', 'game_id');
        $this->createIndex('uq_game_player_unique_nick_per_game', '{{%game_player}}', ['game_id', 'nickname'], true);

        // В SQLite addForeignKey не поддерживается -> пропускаем.
        // В MySQL/PG — ставим FK с каскадом.
        if (!in_array($this->db->driverName, ['sqlite'], true)) {
            $this->addForeignKey(
                'fk_game_player_game',
                '{{%game_player}}',
                'game_id',
                '{{%game}}',
                'id',
                'CASCADE',
                'CASCADE'
            );
        }
    }

    public function safeDown()
    {
        if (!in_array($this->db->driverName, ['sqlite'], true)) {
            $this->dropForeignKey('fk_game_player_game', '{{%game_player}}');
        }
        $this->dropTable('{{%game_player}}');
        $this->dropTable('{{%game}}');
    }
}
