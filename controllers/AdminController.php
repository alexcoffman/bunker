<?php
namespace app\controllers;

use app\models\Card;
use app\models\CardType;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class AdminController extends Controller
{
    public $layout = 'main';

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout'           => ['POST'],
                    'card-type-create' => ['GET','POST'],
                    'card-type-edit'   => ['GET','POST'],
                    'card-create'      => ['GET','POST'],
                    'card-edit'        => ['GET','POST'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        if (in_array($action->id, ['login'])) {
            return parent::beforeAction($action);
        }
        if (!Yii::$app->session->get('isAdmin', false)) {
            return $this->redirect(['admin/login']);
        }
        return parent::beforeAction($action);
    }

    public function actionLogin()
    {
        $error = null;
        if (Yii::$app->request->isPost) {
            $pass = (string)Yii::$app->request->post('password', '');
            $real = (string)Yii::$app->params['adminPassword'];
            if ($pass !== '' && hash_equals($real, $pass)) {
                Yii::$app->session->set('isAdmin', true);
                return $this->redirect(['admin/index']);
            }
            $error = 'Неверный пароль.';
        }
        return $this->render('login', ['error' => $error]);
    }

    public function actionLogout()
    {
        Yii::$app->session->remove('isAdmin');
        return $this->redirect(['admin/login']);
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionCardTypes(string $q = null, string $status = null, string $kind = null)
    {
        $query = CardType::find();
        if ($q) {
            $query->andFilterWhere(['or',
                ['like', 'title', $q],
                ['like', 'code', $q],
            ]);
        }
        if ($status) {
            $query->andWhere(['status' => $status]);
        }
        if ($kind) {
            $query->andWhere(['kind' => $kind]);
        }
        $query->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC]);

        $dp = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('card-types', [
            'dataProvider' => $dp,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function actionCardTypeCreate()
    {
        $model = new CardType();
        if ($model->load(Yii::$app->request->post())) {
            if ($model->save()) {
                return $this->redirect(['admin/card-types']);
            }
        } else {
            $model->status = 'active';
            $model->sort_order = 100;
            $model->kind = CardType::KIND_PLAYER;
        }
        return $this->render('card-type-form', ['model' => $model, 'isNew' => true]);
    }

    public function actionCardTypeEdit(int $id)
    {
        $model = CardType::findOne($id);
        if (!$model) throw new NotFoundHttpException('Тип не найден');

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['admin/card-types']);
        }
        return $this->render('card-type-form', ['model' => $model, 'isNew' => false]);
    }

    public function actionCards(int $type_id, string $q = null, string $status = null)
    {
        $type = CardType::findOne($type_id);
        if (!$type) throw new NotFoundHttpException('Тип не найден');

        $query = Card::find()->where(['type_id' => $type_id]);
        if ($q) $query->andFilterWhere(['like', 'text', $q]);
        if ($status) $query->andWhere(['status' => $status]);
        $query->orderBy(['id' => SORT_DESC]);

        $dp = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('cards', [
            'type' => $type,
            'dataProvider' => $dp,
            'q' => $q,
            'status' => $status,
        ]);
    }

    public function actionCardCreate(int $type_id)
    {
        $type = CardType::findOne($type_id);
        if (!$type) throw new NotFoundHttpException('Тип не найден');

        $model = new Card();
        $model->type_id = $type_id;
        $model->status = 'active';
        $model->weight = 1;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['admin/cards', 'type_id' => $type_id]);
        }

        return $this->render('card-form', [
            'type' => $type,
            'model' => $model,
            'isNew' => true,
        ]);
    }

    public function actionCardEdit(int $id)
    {
        $model = Card::findOne($id);
        if (!$model) throw new NotFoundHttpException('Карта не найдена');
        $type = CardType::findOne($model->type_id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['admin/cards', 'type_id' => $model->type_id]);
        }

        return $this->render('card-form', [
            'type' => $type,
            'model' => $model,
            'isNew' => false,
        ]);
    }
}
