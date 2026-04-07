<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLocationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true],
            'place'      => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'latitude'   => ['type' => 'DECIMAL', 'constraint' => '9,6', 'null' => false],
            'longitude'  => ['type' => 'DECIMAL', 'constraint' => '9,6', 'null' => false],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('locations');
    }

    public function down()
    {
        $this->forge->dropTable('locations');
    }
}
