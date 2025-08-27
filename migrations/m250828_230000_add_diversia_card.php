<?php
use yii\db\Migration;

class m250828_230000_add_diversia_card extends Migration
{
    public function safeUp()
    {
        $typeId = (new \yii\db\Query())
            ->from('{{%card_type}}')
            ->select('id')
            ->where(['code' => 'SPECIAL'])
            ->scalar();
        if ($typeId) {
            $this->insert('{{%card}}', [
                'type_id'   => $typeId,
                'text'      => 'Особое условие: "Диверсия" — если вас изгоняют, случайная открытая карта бункера исчезает.',
                'action'    => 'diversia',
                'weight'    => 1,
                'status'    => 'active',
                'created_at'=> time(),
                'updated_at'=> time(),
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%card}}', ['action' => 'diversia']);
    }
}
