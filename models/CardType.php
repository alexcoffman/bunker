<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $code
 * @property string $title
 * @property string|null $description
 * @property string $status
 * @property int $sort_order
 * @property int $created_at
 * @property int $updated_at
 * @property string $kind
 */
class CardType extends ActiveRecord
{
    public const KIND_CATASTROPHE = 'CATASTROPHE';
    public const KIND_BUNKER      = 'BUNKER';
    public const KIND_SPECIAL     = 'SPECIAL';
    public const KIND_PLAYER      = 'PLAYER_CARD';
    public const KIND_THREAT      = 'THREAT';

    public static function tableName(): string { return '{{%card_type}}'; }

    public static function kinds(): array
    {
        return [
            self::KIND_CATASTROPHE => 'Катастрофа',
            self::KIND_BUNKER      => 'Бункер',
            self::KIND_SPECIAL     => 'Особое условие',
            self::KIND_PLAYER      => 'Карты игрока',
            self::KIND_THREAT      => 'Угроза',
        ];
    }

    public function rules(): array
    {
        return [
            [['code','title','status','kind'], 'required'],
            [['description'], 'string'],
            [['sort_order','created_at','updated_at'], 'integer'],
            [['code'], 'string', 'max' => 32],
            [['title'], 'string', 'max' => 64],
            [['status'], 'in', 'range' => ['active','hidden','archived']],
            [['kind'], 'in', 'range' => array_keys(self::kinds())],
            [['code'], 'unique'],
        ];
    }
}
