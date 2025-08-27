<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $type_id
 * @property string $text
 * @property int $weight
 * @property string $status
 * @property int $created_at
 * @property int $updated_at
 */
class Card extends ActiveRecord
{
    public static function tableName(): string { return '{{%card}}'; }

    public function rules(): array
    {
        return [
            [['type_id','text'], 'required'],
            [['type_id','weight','created_at','updated_at'], 'integer'],
            [['text'], 'string'],
            [['status'], 'in', 'range' => ['active','hidden','archived']],
        ];
    }

    /**
     * Выбирает случайную активную карту по коду типа с учётом веса.
     * Возвращает текст карты (или null, если нет карт).
     */
    public static function pickTextByTypeCode(string $typeCode): ?string
    {
        $row = (new \yii\db\Query())
            ->from('{{%card}} c')
            ->innerJoin('{{%card_type}} t', 't.id = c.type_id')
            ->where(['t.code' => $typeCode, 'c.status' => 'active', 't.status' => 'active'])
            ->all();

        if (!$row) return null;

        // Взвешенный случайный выбор
        $pool = [];
        foreach ($row as $r) {
            $w = max(1, (int)$r['weight']);
            for ($i=0; $i<$w; $i++) $pool[] = $r['text'];
        }
        return $pool[random_int(0, count($pool)-1)];
    }
}
