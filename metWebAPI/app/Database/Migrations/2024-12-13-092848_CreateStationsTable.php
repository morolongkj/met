<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStationsTable extends Migration
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
            'lat'        => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'lng'        => [
                'type' => 'DOUBLE',
                'null' => true,
            ],
            'altitude'   => [
                'type' => 'INT',
                'null' => true,
            ],
            'state'      => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'status'     => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'N',
            ],
            'extra_data' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('stations');
    }

    public function down()
    {
        $this->forge->dropTable('stations');
    }
}
