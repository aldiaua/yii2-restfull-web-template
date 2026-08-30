<?php

namespace app\controllers\api;

use app\models\User;
use yii\rest\ActiveController;

class UserController extends ActiveController
{
    public $modelClass = User::class;

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        // Add any custom behaviors here if needed
        return $behaviors;
    }
}
