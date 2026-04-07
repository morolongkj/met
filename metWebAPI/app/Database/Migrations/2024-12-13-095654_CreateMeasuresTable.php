<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMeasuresTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => [
                'type'     => 'INT',
                'unsigned' => true,
                'unique'   => true,
            ],
            'name'       => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'short_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'unit'       => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'decimals'   => [
                'type' => 'INT',
                'null' => true,
            ],
            'validation' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'thresholds' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('measures');
    }

    public function down()
    {
        $this->forge->dropTable('measures');
    }
}
