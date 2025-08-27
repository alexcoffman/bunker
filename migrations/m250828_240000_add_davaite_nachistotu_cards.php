<?php
use yii\db\Migration;

class m250828_240000_add_davaite_nachistotu_cards extends Migration
{
    public function safeUp()
    {
        $typeId = (new \yii\db\Query())
            ->from('{{%card_type}}')
            ->select('id')
            ->where(['code' => 'SPECIAL'])
            ->scalar();
        if ($typeId) {
            $now = time();
            $this->batchInsert('{{%card}}', ['type_id','text','action','weight','status','created_at','updated_at'], [
                [$typeId, 'Особое условие: "Давайте начистоту — багаж" — перемешайте все открытые карты багажа между игроками.', 'davaite-nachistotu-bagazh', 1, 'active', $now, $now],
                [$typeId, 'Особое условие: "Давайте начистоту — факты" — перемешайте все открытые карты фактов между игроками.', 'davaite-nachistotu-fakty', 1, 'active', $now, $now],
                [$typeId, 'Особое условие: "Давайте начистоту — хобби" — перемешайте все открытые карты хобби между игроками.', 'davaite-nachistotu-hobbi', 1, 'active', $now, $now],
                [$typeId, 'Особое условие: "Давайте начистоту — здоровье" — перемешайте все открытые карты здоровья между игроками.', 'davaite-nachistotu-zdorovie', 1, 'active', $now, $now],
                [$typeId, 'Особое условие: "Давайте начистоту — биология" — перемешайте все открытые карты биологии между игроками.', 'davaite-nachistotu-biologia', 1, 'active', $now, $now],
                [$typeId, 'Особое условие: "Давайте начистоту — фобия" — перемешайте все открытые карты фобии между игроками.', 'davaite-nachistotu-fobia', 1, 'active', $now, $now],
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%card}}', ['action' => [
            'davaite-nachistotu-bagazh',
            'davaite-nachistotu-fakty',
            'davaite-nachistotu-hobbi',
            'davaite-nachistotu-zdorovie',
            'davaite-nachistotu-biologia',
            'davaite-nachistotu-fobia',
        ]]);
    }
}
