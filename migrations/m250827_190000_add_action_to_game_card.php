<?php
use yii\db\Migration;

class m250827_190000_add_action_to_game_card extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game_card}}', 'action', $this->string(32)->null()->after('card_text'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game_card}}', 'action');
    }
}
