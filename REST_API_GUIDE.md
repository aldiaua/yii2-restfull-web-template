***

# 🚀 Panduan Setup RESTful API Yii2 + PostgreSQL dari Nol

Panduan minimalis dan to-the-point untuk membuat RESTful API menggunakan Yii2 Framework dengan database PostgreSQL.

## 📋 Prasyarat
- PHP 8.0+ (Pastikan ekstensi `pdo_pgsql` aktif di `php.ini`)
- Composer
- PostgreSQL 12+

---

## 1️⃣ Instalasi & Persiapan Database

**1. Install Yii2 Basic Template:**
```bash
composer create-project --prefer-dist yiisoft/yii2-app-basic rest-api-yii2
cd rest-api-yii2
```

**2. Buat Database PostgreSQL:**
```sql
CREATE DATABASE db_yii2_api;
CREATE USER yii2_user WITH PASSWORD 'yii2_password';
GRANT ALL PRIVILEGES ON DATABASE db_yii2_api TO yii2_user;
```

---

## 2️⃣ Konfigurasi Minimal Yii2

### A. Koneksi Database (`config/db.php`)
```php
<?php
return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=localhost;port=5432;dbname=db_yii2_api',
    'username' => 'yii2_user',
    'password' => 'yii2_password',
    'charset' => 'utf8',
];
```

### B. Pretty URL (`web/.htaccess`)
Buat file `.htaccess` di dalam folder `web/`:
```apache
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php
```

### C. Konfigurasi Utama API (`config/web.php`)
Ubah bagian `components` di dalam `config/web.php` menjadi seperti ini. Ini mencakup parsing JSON, routing eksplisit, dan standarisasi response.

```php
'components' => [
    'request' => [
        'cookieValidationKey' => 'ganti-dengan-string-acak-anda',
        'parsers' => [
            'application/json' => 'yii\web\JsonParser',
        ],
    ],
    
    'urlManager' => [
        'enablePrettyUrl' => true,
        'enableStrictParsing' => false, // false agar Gii/Debug bisa diakses
        'showScriptName' => false,
        'rules' => [
            // Routing Eksplisit API
            'GET api/products'                  => 'product/index',
            'POST api/products'                 => 'product/create',
            'GET api/products/<id:\d+>'         => 'product/view',
            'PUT,PATCH api/products/<id:\d+>'   => 'product/update',
            'DELETE api/products/<id:\d+>'      => 'product/delete',
        ],
    ],

    'response' => [
        'format'  => yii\web\Response::FORMAT_JSON,
        'on beforeSend' => function ($event) {
            $response = $event->sender;
            if ($response->format === yii\web\Response::FORMAT_JSON) {
                if ($response->data === null) $response->data = [];

                // Standarisasi Error
                if (!$response->isSuccessful) {
                    $response->data = [
                        'success' => false,
                        'status'  => $response->statusCode,
                        'error'   => $response->data,
                    ];
                } 
                // Standarisasi Sukses
                else {
                    // Jika ada pagination (data + meta)
                    if (is_array($response->data) && isset($response->data['data'], $response->data['meta'])) {
                        $response->data = array_merge([
                            'success' => true,
                            'status'  => $response->statusCode,
                        ], $response->data);
                    } 
                    // Response biasa
                    else if (!isset($response->data['success'])) {
                        $response->data = [
                            'success' => true,
                            'status'  => $response->statusCode,
                            'data'    => $response->data,
                        ];
                    }
                }
            }
        },
    ],
    
    // ... komponen lainnya (cache, user, dll) biarkan default
],
```

---

## 3️⃣ Membuat Migration & Model

**1. Buat Migration:**
```bash
php yii migrate/create create_products_table
```
Edit file migration di folder `migrations/`:
```php
public function safeUp()
{
    $this->createTable('{{%products}}', [
        'id'          => $this->primaryKey(),
        'name'        => $this->string(150)->notNull(),
        'description' => $this->text()->null(),
        'price'       => $this->decimal(12, 2)->notNull()->defaultValue(0),
        'stock'       => $this->integer()->notNull()->defaultValue(0),
        'category'    => $this->string(100)->null(),
        'status'      => $this->smallInteger()->notNull()->defaultValue(1),
        'created_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        'updated_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
    ]);
}

public function safeDown()
{
    $this->dropTable('{{%products}}');
}
```
Jalankan migrate: `php yii migrate`

**2. Buat Model (`models/Product.php`):**
```php
<?php
namespace app\models;

use yii\db\ActiveRecord;

class Product extends ActiveRecord
{
    public static function tableName()
    {
        return 'products';
    }

    public function rules()
    {
        return [
            [['name', 'price'], 'required'],
            [['description'], 'string'],
            [['price'], 'number', 'min' => 0],
            [['stock', 'status'], 'integer'],
            [['status'], 'default', 'value' => 1],
            [['name', 'category'], 'string', 'max' => 150],
        ];
    }
}
```

---

## 4️⃣ Membuat REST Controller

Buat file `controllers/ProductController.php`. Kode ini mencakup CRUD terpisah, Pagination, dan CORS.

```php
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

    // GET /api/products (List + Pagination)
    public function actionIndex()
    {
        $query = Product::find();

        if ($name = \Yii::$app->request->get('name')) {
            $query->andFilterWhere(['ilike', 'name', $name]);
        }

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => [
                'defaultPageSize' => 10,
                'pageSizeLimit'   => [1, 100],
                'pageSizeParam'   => 'per_page', // Support URL ?per_page=5
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC]
            ]
        ]);

        $pagination = $dataProvider->getPagination();

        return [
            'data' => $dataProvider->getModels(),
            'meta' => [
                'total_items'  => $dataProvider->getTotalCount(),
                'total_pages'  => $pagination->getPageCount(),
                'current_page' => $pagination->getPage() + 1,
                'per_page'     => $pagination->getPageSize(),
            ]
        ];
    }

    // GET /api/products/1
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    // POST /api/products
    public function actionCreate()
    {
        $model = new Product();
        $model->load(\Yii::$app->request->getBodyParams(), '');

        if ($model->validate() && $model->save()) {
            \Yii::$app->response->setStatusCode(201);
            return $model; 
        }

        \Yii::$app->response->setStatusCode(422, 'Data Validation Failed.');
        return $model->errors;
    }

    // PUT /api/products/1
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->load(\Yii::$app->request->getBodyParams(), '');

        if ($model->validate() && $model->save()) {
            return $model;
        }

        \Yii::$app->response->setStatusCode(422, 'Data Validation Failed.');
        return $model->errors;
    }

    // DELETE /api/products/1
    public function actionDelete($id)
    {
        $model = $this->findModel($id);

        if ($model->delete()) {
            \Yii::$app->response->setStatusCode(204);
            return null; 
        }

        return null;
    }

    private function findModel($id)
    {
        if (($model = Product::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException("Product with ID $id not found.");
    }
}
```

---

## 5️⃣ Pengujian API (cURL)

Jalankan server dengan `php yii serve` (atau pakai virtual host), lalu test endpoint berikut:

**1. Create Data (POST)**
```bash
curl -X POST http://localhost:8080/api/products \
  -H "Content-Type: application/json" \
  -d '{"name": "SSD NVMe 1TB", "price": 1500000, "stock": 20, "category": "storage"}'
```

**2. Read List with Pagination (GET)**
```bash
curl -X GET "http://localhost:8080/api/products?page=1&per_page=5"
```

**3. Read Single Data (GET)**
```bash
curl -X GET http://localhost:8080/api/products/1
```

**4. Update Data (PUT)**
```bash
curl -X PUT http://localhost:8080/api/products/1 \
  -H "Content-Type: application/json" \
  -d '{"price": 1450000, "stock": 15}'
```

**5. Delete Data (DELETE)**
```bash
curl -X DELETE http://localhost:8080/api/products/1
```