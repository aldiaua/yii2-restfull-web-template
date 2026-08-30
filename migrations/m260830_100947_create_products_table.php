<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%products}}`.
 */
class m260830_100947_create_products_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'pgsql') {
            $tableOptions = null; // PostgreSQL tidak memerlukan charset di tabel
        }

        $this->createTable('{{%products}}', [
            'id'          => $this->primaryKey(),
            'name'        => $this->string(150)->notNull(),
            'description' => $this->text()->null(),
            'price'       => $this->decimal(12, 2)->notNull()->defaultValue(0),
            'stock'       => $this->integer()->notNull()->defaultValue(0),
            'category'    => $this->string(100)->null(),
            'status'      => $this->smallInteger()->notNull()->defaultValue(1), // 1=active, 0=inactive
            'created_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at'  => $this->timestamp()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $tableOptions);

        // Index untuk pencarian
        $this->createIndex('idx-products-category', '{{%products}}', 'category');
        $this->createIndex('idx-products-status', '{{%products}}', 'status');

        // Insert contoh data
        $this->batchInsert('{{%products}}', ['name', 'description', 'price', 'stock', 'category', 'status'], [
            ['Laptop Asus ROG', 'Laptop gaming 16GB RAM', 18500000, 10, 'electronics', 1],
            ['Mouse Logitech', 'Wireless mouse', 250000, 50, 'accessories', 1],
            ['Keyboard Mechanical', 'RGB switch blue', 750000, 25, 'accessories', 1],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-products-status', '{{%products}}');
        $this->dropIndex('idx-products-category', '{{%products}}');
        $this->dropTable('{{%products}}');
    }
}
