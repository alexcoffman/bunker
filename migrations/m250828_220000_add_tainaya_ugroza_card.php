<?php
use yii\db\Migration;

class m250828_220000_add_tainaya_ugroza_card extends Migration
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
                'text'      => 'Особое условие: "Тайная угроза" — если вас изгоняют, в конце игры случайный игрок в бункере получит две карты угрозы.',
                'action'    => 'tainaya-ugroza',
                'weight'    => 1,
                'status'    => 'active',
                'created_at'=> time(),
                'updated_at'=> time(),
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%card}}', ['action' => 'tainaya-ugroza']);
    }
}
