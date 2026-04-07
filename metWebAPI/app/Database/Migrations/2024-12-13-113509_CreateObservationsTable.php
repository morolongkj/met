<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateObservationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'station_id'     => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'measure_id'     => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'record_date'    => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'insert_date'    => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'raw_data'       => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'val_data'       => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'min_data'       => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'max_data'       => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'min_minute'     => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'max_minute'     => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'error_code'     => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'val_code'       => [
                'type' => 'SMALLINT',
                'null' => true,
            ],
            'status'         => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => true,
            ],
            'raw_data_str'   => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'val_data_str'   => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'min_data_str'   => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'max_data_str'   => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'min_minute_str' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'max_minute_str' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['station_id', 'measure_id', 'record_date']);
        $this->forge->createTable('observations');
    }

    public function down()
    {
        $this->forge->dropTable('observations');
    }
}
