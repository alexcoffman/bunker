<?php
use yii\db\Migration;

/**
 * Таблица событий для SSE.
 */
class m250826_170000_game_events extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%game_event}}', [
            'id'         => $this->primaryKey(),
            'game_id'    => $this->integer()->notNull(),
            'type'       => $this->string(32)->notNull(),
            'payload'    => $this->text()->null(), // JSON
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_game_event_game_id', '{{%game_event}}', 'game_id');
        $this->createIndex('idx_game_event_created', '{{%game_event}}', 'created_at');
    }

    public function safeDown()
    {
        $this->dropTable('{{%game_event}}');
    }
}
