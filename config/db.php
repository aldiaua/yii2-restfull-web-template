<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'pgsql:host=lerd-postgres;port=5432;dbname=yii_rest_db',
    'username' => 'postgres',
    'password' => 'lerd',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
