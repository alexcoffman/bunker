<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Снимок карты в контексте игры (публичная или приватная).
 *
 * @property int $id
 * @property int $game_id
 * @property int|null $player_id
 * @property string $type_code
 * @property string $card_text
 * @property string|null $action
 * @property int $is_public
 * @property int $is_revealed
 * @property int $created_at
 * @property int|null $revealed_at
 */
class GameCard extends ActiveRecord
{
    public static function tableName(): string { return '{{%game_card}}'; }

    public function rules(): array
    {
        return [
            [['game_id','type_code','card_text','created_at'], 'required'],
            [['game_id','player_id','created_at','revealed_at'], 'integer'],
            [['is_public','is_revealed'], 'boolean'],
            [['card_text'], 'string'],
            [['action'], 'string', 'max' => 32],
            [['type_code'], 'string', 'max' => 32],
        ];
    }
}
