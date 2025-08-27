<?php
use yii\db\Migration;

class m250828_210000_add_extra_threats_to_game extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%game}}', 'extra_threats', $this->smallInteger()->notNull()->defaultValue(0));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%game}}', 'extra_threats');
    }
}
