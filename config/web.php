<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => '9tvOyp1faOeNnKBHogw5hHS_b8D4nX2B',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                // Routing eksplisit untuk /api/products
                'GET api/products'                  => 'product/index',
                'POST api/products'                 => 'product/create',
                'GET api/products/<id:\d+>'         => 'product/view',
                'PUT,PATCH api/products/<id:\d+>'   => 'product/update',
                'DELETE api/products/<id:\d+>'      => 'product/delete',
            ],
        ],

        // Response format default JSON
        'response' => [
            'format'  => yii\web\Response::FORMAT_JSON,
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                
                // Hanya format jika outputnya JSON
                if ($response->format === yii\web\Response::FORMAT_JSON) {
                    
                    if ($response->data === null) {
                        $response->data = [];
                    }

                    // 1. STANDARISASI ERROR
                    if (!$response->isSuccessful) {
                        $response->data = [
                            'success' => false,
                            'status'  => $response->statusCode,
                            'error'   => $response->data,
                        ];
                    } 
                    // 2. STANDARISASI SUKSES
                    else {
                        // Cek apakah ada key 'data' dan 'meta' dari Controller
                        if (is_array($response->data) && isset($response->data['data']) && isset($response->data['meta'])) {
                            // Gabungkan ke root level (sejajar)
                            $response->data = array_merge([
                                'success' => true,
                                'status'  => $response->statusCode,
                            ], $response->data);
                        } 
                        // Untuk response biasa (single object)
                        else {
                            // Cegah double-wrap jika controller sudah return 'success'
                            if (!isset($response->data['success'])) {
                                $response->data = [
                                    'success' => true,
                                    'status'  => $response->statusCode,
                                    'data'    => $response->data,
                                ];
                            }
                        }
                    }
                }
            },
        ],
        
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
