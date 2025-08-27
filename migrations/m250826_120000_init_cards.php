<?php
use yii\db\Migration;

/**
 * Типы карт, сами карты и "снимки" карт в конкретной игре (game_card).
 * Примечание: для SQLite FK не ставим.
 */
class m250826_120000_init_cards extends Migration
{
    public function safeUp()
    {
        // ----- card_type -----
        $this->createTable('{{%card_type}}', [
            'id'          => $this->primaryKey(),
            'code'        => $this->string(32)->notNull()->unique(), // 'BIOLOGY','BAGGAGE','CATASTROPHE','BUNKER'
            'title'       => $this->string(64)->notNull(),
            'description' => $this->text()->null(),
            'status'      => $this->string(12)->notNull()->defaultValue('active'), // active|hidden|archived
            'sort_order'  => $this->integer()->notNull()->defaultValue(100),
            'created_at'  => $this->integer()->notNull()->defaultValue(time()),
            'updated_at'  => $this->integer()->notNull()->defaultValue(time()),
        ]);

        // ----- card -----
        $this->createTable('{{%card}}', [
            'id'         => $this->primaryKey(),
            'type_id'    => $this->integer()->notNull(),
            'text'       => $this->text()->notNull(),
            'weight'     => $this->integer()->notNull()->defaultValue(1), // влияет на вероятность выбора
            'status'     => $this->string(12)->notNull()->defaultValue('active'),
            'created_at' => $this->integer()->notNull()->defaultValue(time()),
            'updated_at' => $this->integer()->notNull()->defaultValue(time()),
        ]);
        $this->createIndex('idx_card_type', '{{%card}}', ['type_id', 'status']);

        // ----- game_card -----
        $this->createTable('{{%game_card}}', [
            'id'          => $this->primaryKey(),
            'game_id'     => $this->integer()->notNull(),
            'player_id'   => $this->integer()->null(), // null => публичная карта стола (катастрофа/бункер)
            'type_code'   => $this->string(32)->notNull(), // дублируем код типа для снапшота
            'card_text'   => $this->text()->notNull(),     // текст карты (снимок на момент раздачи)
            'is_public'   => $this->boolean()->notNull()->defaultValue(false),
            'is_revealed' => $this->boolean()->notNull()->defaultValue(false), // для приватных карт
            'created_at'  => $this->integer()->notNull()->defaultValue(time()),
            'revealed_at' => $this->integer()->null(),
        ]);
        $this->createIndex('idx_game_card_game', '{{%game_card}}', ['game_id', 'player_id']);
        $this->createIndex('idx_game_card_public', '{{%game_card}}', ['game_id', 'is_public']);

        // ----- начальные данные (минимум для запуска) -----
        // типы
        $types = [
            ['BIOLOGY',     'Биология',     10],
            ['BAGGAGE',     'Багаж',        20],
            ['CATASTROPHE', 'Катастрофа',   5],
            ['BUNKER',      'Бункер',       15],
        ];
        foreach ($types as $i => $t) {
            $this->insert('{{%card_type}}', [
                'code' => $t[0], 'title' => $t[1], 'sort_order' => $t[2],
                'status' => 'active', 'created_at' => time(), 'updated_at' => time(),
            ]);
        }

        // карты (по паре штук на тип, чтобы старт работал)
        $typeId = function (string $code) {
            return (new \yii\db\Query())->from('{{%card_type}}')->select('id')->where(['code'=>$code])->scalar();
        };

        $this->batchInsert('{{%card}}',
            ['type_id','text','weight','status','created_at','updated_at'], [
                [$typeId('CATASTROPHE'), 'Зомби-апокалипсис по всему миру.', 1, 'active', time(), time()],
                [$typeId('CATASTROPHE'), 'Ядерная война, глобальные пожары.', 1, 'active', time(), time()],

                [$typeId('BUNKER'), 'Бункер на 8 мест, запасы на 6 месяцев.', 1, 'active', time(), time()],
                [$typeId('BUNKER'), 'Бункер на 12 мест, лаборатория + теплица.', 1, 'active', time(), time()],

                [$typeId('BIOLOGY'), 'Аллергия на пенициллин.', 1, 'active', time(), time()],
                [$typeId('BIOLOGY'), 'Отличное здоровье, редкая группа крови.', 1, 'active', time(), time()],

                [$typeId('BAGGAGE'), 'Рюкзак с инструментами (мультитул, изолента, верёвка).', 1, 'active', time(), time()],
                [$typeId('BAGGAGE'), 'Литература по выживанию, аптечка.', 1, 'active', time(), time()],
            ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%game_card}}');
        $this->dropTable('{{%card}}');
        $this->dropTable('{{%card_type}}');
    }
}
