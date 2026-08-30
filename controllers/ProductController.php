<?php

namespace app\controllers;

use app\models\Product;
use yii\data\ActiveDataProvider;
use yii\filters\Cors;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;

class ProductController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        
        $behaviors['contentNegotiator'] = [
            'class'   => 'yii\filters\ContentNegotiator',
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];

        $behaviors['corsFilter'] = [
            'class' => Cors::class,
            'cors'  => [
                'Origin'                           => ['*'],
                'Access-Control-Request-Method'    => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
                'Access-Control-Request-Headers'   => ['*'],
                'Access-Control-Max-Age'           => 3600,
            ],
        ];

        return $behaviors;
    }

    // =========================================================
    // METHOD GET (READ)
    // =========================================================

        // GET /api/products
    public function actionIndex()
    {
        $query = Product::find();

        // Filter berdasarkan nama
        if ($name = \Yii::$app->request->get('name')) {
            $query->andFilterWhere(['ilike', 'name', $name]);
        }

        // Filter berdasarkan kategori
        if ($category = \Yii::$app->request->get('category')) {
            $query->andFilterWhere(['category' => $category]);
        }

        // Inisialisasi Pagination
        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'defaultPageSize' => 10, // Default tampilkan 10 data per halaman
                'pageSizeLimit'   => [1, 100], // Batasan: User maksimal request 100 data per halaman
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ]
        ]);

        // Kita rapikan data items dan info pagination-nya
        $pagination = $dataProvider->getPagination();

        return [
            'data' => $dataProvider->getModels(), // Data produk actual
            'meta'  => [
                'total_items'  => $dataProvider->getTotalCount(), // Total seluruh data di DB
                'total_pages'  => $pagination->getPageCount(),    // Total halaman tersedia
                'current_page' => $pagination->getPage() + 1,     // Halaman saat ini (Yii mulai dari 0, jadi +1)
                'per_page'     => $pagination->getPageSize(),     // Jumlah data per halaman
            ]
        ];
    }

    // GET /api/products/1
    public function actionView($id)
    {
        // Cukup return model, on beforeSend akan membungkusnya
        return $this->findModel($id);
    }

    // =========================================================
    // METHOD POST (CREATE)
    // =========================================================

    // POST /api/products
    public function actionCreate()
    {
        $model = new Product();
        $model->load(\Yii::$app->request->getBodyParams(), '');

        if ($model->validate() && $model->save()) {
            \Yii::$app->response->setStatusCode(201);
            
            // Cukup return model, on beforeSend yang bungkus
            return $model; 
        }

        // Jika gagal validasi, set status 422 dan return error.
        // on beforeSend akan menerjemahkan ini sebagai error dan membungkusnya otomatis
        \Yii::$app->response->setStatusCode(422, 'Data Validation Failed.');
        return $model->errors;
    }

    // =========================================================
    // METHOD PUT/PATCH (UPDATE)
    // =========================================================

    // PUT /api/products/1
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->load(\Yii::$app->request->getBodyParams(), '');

        if ($model->validate() && $model->save()) {
            // Cukup return model
            return $model;
        }

        \Yii::$app->response->setStatusCode(422, 'Data Validation Failed.');
        return $model->errors;
    }

    // =========================================================
    // METHOD DELETE (DELETE)
    // =========================================================

    // DELETE /api/products/1
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->delete()) {
            \Yii::$app->response->setStatusCode(204);
            // Karena 204 No Content, return null agar on beforeSend tau ini kosong
            return null; 
        }

        return null;
    }

    // =========================================================
    // HELPER METHOD
    // =========================================================

    private function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException("Product with ID $id not found.");
    }
}