<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $status
 * @property int $max_players
 * @property string $phase
 * @property int $round_no
 * @property int $created_at
 * @property int|null $started_at
 * @property int|null $finished_at
 * @property string|null $turn_order JSON (array of player IDs)
 * @property int $current_turn_index
 * @property int|null $last_first_player_id
 * @property int $total_rounds
 * @property int|null $initial_player_count
 * @property string|null $voting_rounds
 * @property int|null $survivors_target
 */
class Game extends ActiveRecord
{
    public static function tableName(): string { return '{{%game}}'; }

    public static function generateCode(): string {
        $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 4; $i++) $code .= $chars[random_int(0, strlen($chars)-1)];
        } while (self::find()->where(['code' => $code])->exists());
        return $code;
    }

    public function rules(): array {
        return [
            [['code','status','phase'], 'required'],
            [['code'], 'string', 'length' => 4],
            [['status','phase'], 'string', 'max' => 16],
            [['max_players','round_no','created_at','started_at','finished_at','current_turn_index','last_first_player_id','total_rounds','initial_player_count','survivors_target'], 'integer'],
            [['turn_order','voting_rounds'], 'string'],
        ];
    }
}
