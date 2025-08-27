<?php
use yii\db\Migration;

/**
 * Добавляем поле kind в card_type и нормализуем стартовые типы.
 * kind:
 *  - CATASTROPHE — одна общая карта в начале
 *  - BUNKER      — 5 карт, 1 сразу открыта, остальные по раундам 2..5
 *  - SPECIAL     — особые карты “сыграйте когда угодно”, логика позже
 *  - PLAYER_CARD — карты игрока (профессия, биология, багаж, и т.д.)
 *  - THREAT      — угроза, раздаётся всем в самом конце (открыто)
 */
class m250826_130000_card_types_kind extends Migration
{
    public function safeUp()
    {
        // Добавляем колонку kind
        $this->addColumn('{{%card_type}}', 'kind', $this->string(16)->notNull()->defaultValue('PLAYER_CARD'));
        $this->createIndex('idx_card_type_kind', '{{%card_type}}', 'kind');

        // Помечаем существующие коды, если есть
        $setKind = function(string $code, string $kind) {
            $this->update('{{%card_type}}', ['kind' => $kind, 'updated_at' => time()], ['code' => $code]);
        };

        // Проставим для стартовых, если они уже есть из прошлой миграции
        $setKind('CATASTROPHE', 'CATASTROPHE');
        $setKind('BUNKER',      'BUNKER');
        $setKind('BIOLOGY',     'PLAYER_CARD');
        $setKind('BAGGAGE',     'PLAYER_CARD');

        // Обеспечим наличие ключевых типов: PROFESSION (PLAYER_CARD), SPECIAL, THREAT.
        // Создаём недостающие записи безопасно (если их нет).
        $exists = fn(string $code) =>
        (new \yii\db\Query())->from('{{%card_type}}')->where(['code'=>$code])->exists();

        $insertType = function(string $code, string $title, string $kind, int $sort) {
            $this->insert('{{%card_type}}', [
                'code' => $code,
                'title'=> $title,
                'kind' => $kind,
                'status'=>'active',
                'sort_order'=>$sort,
                'created_at'=>time(),
                'updated_at'=>time(),
            ]);
        };

        if (!$exists('PROFESSION')) $insertType('PROFESSION', 'Профессия', 'PLAYER_CARD', 5);
        if (!$exists('SPECIAL'))    $insertType('SPECIAL',    'Особое условие', 'SPECIAL', 30);
        if (!$exists('THREAT'))     $insertType('THREAT',     'Угроза', 'THREAT', 40);

        // Базовые карты для новых типов (чтоб старт работал даже без ручного наполнения)
        $typeId = fn(string $code) => (new \yii\db\Query())
            ->from('{{%card_type}}')->select('id')->where(['code'=>$code])->scalar();

        $rows = [];

        if (!(new \yii\db\Query())->from('{{%card}}')->where(['type_id'=>$typeId('PROFESSION')])->exists()) {
            $rows[] = [$typeId('PROFESSION'), 'Инженер-механик.', 1, 'active', time(), time()];
            $rows[] = [$typeId('PROFESSION'), 'Врач-терапевт.',   1, 'active', time(), time()];
        }
        if (!(new \yii\db\Query())->from('{{%card}}')->where(['type_id'=>$typeId('SPECIAL')])->exists()) {
            $rows[] = [$typeId('SPECIAL'), 'Особая карта: сыграйте в любой момент (пример).', 1, 'active', time(), time()];
        }
        if (!(new \yii\db\Query())->from('{{%card}}')->where(['type_id'=>$typeId('THREAT')])->exists()) {
            $rows[] = [$typeId('THREAT'), 'Угроза: радиационное облучение (пример).', 1, 'active', time(), time()];
            $rows[] = [$typeId('THREAT'), 'Угроза: заражение вирусом (пример).',      1, 'active', time(), time()];
        }

        if (!empty($rows)) {
            $this->batchInsert('{{%card}}', ['type_id','text','weight','status','created_at','updated_at'], $rows);
        }
    }

    public function safeDown()
    {
        $this->dropIndex('idx_card_type_kind', '{{%card_type}}');
        $this->dropColumn('{{%card_type}}', 'kind');
        // Карты и типы, добавленные здесь, специально не удаляем в down (чтобы не потерять контент).
    }
}
