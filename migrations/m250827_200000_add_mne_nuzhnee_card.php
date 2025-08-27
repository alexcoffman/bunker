<?php
use yii\db\Migration;

class m250827_200000_add_mne_nuzhnee_card extends Migration
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
                'text'      => 'Особое условие: "Мне нужнее" — заберите багаж другого игрока.',
                'action'    => 'mne-nuzhnee',
                'weight'    => 1,
                'status'    => 'active',
                'created_at'=> time(),
                'updated_at'=> time(),
            ]);
        }
    }

    public function safeDown()
    {
        $this->delete('{{%card}}', ['action' => 'mne-nuzhnee']);
    }
}
