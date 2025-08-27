<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $game_id
 * @property string $nickname
 * @property string $role
 * @property int $is_alive
 * @property int $joined_at
 * @property int|null $left_at
 */
class GamePlayer extends ActiveRecord
{
    public static function tableName(): string { return '{{%game_player}}'; }

    public function rules(): array {
        return [
            [['game_id','nickname','role','joined_at'], 'required'],
            [['game_id','joined_at','left_at'], 'integer'],
            [['is_alive'], 'boolean'],
            [['nickname'], 'string', 'max' => 32],
            [['role'], 'in', 'range' => ['HOST','PLAYER']],
        ];
    }
}
