<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $game_id
 * @property string $type
 * @property string|null $payload
 * @property int $created_at
 */
class GameEvent extends ActiveRecord
{
    public static function tableName(): string { return '{{%game_event}}'; }
    public function rules(): array {
        return [
            [['game_id','type','created_at'],'required'],
            [['game_id','created_at'],'integer'],
            [['payload'],'string'],
            [['type'],'string','max'=>32],
        ];
    }
}
