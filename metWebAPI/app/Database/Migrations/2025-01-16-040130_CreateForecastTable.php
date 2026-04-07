<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateForecastTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'district_id'       => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'date_for_forecast' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'current_temp'      => [
                'type'    => 'FLOAT',
                'default' => 0,
                'null'    => false,
            ],
            'max_temp'          => [
                'type'    => 'FLOAT',
                'default' => 0,
                'null'    => false,
            ],
            'min_temp'          => [
                'type'    => 'FLOAT',
                'default' => 0,
                'null'    => false,
            ],
            'condition'         => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('district_id', 'districts', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('forecast');
    }

    public function down()
    {
        $this->forge->dropTable('forecast');
    }
}
