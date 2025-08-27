<?php
use yii\db\Migration;

class m250826_180000_add_action_to_card extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%card}}', 'action', $this->string(32)->null()->after('text'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%card}}', 'action');
    }
}
