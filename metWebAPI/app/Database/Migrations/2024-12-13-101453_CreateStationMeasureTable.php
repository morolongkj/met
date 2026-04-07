<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStationMeasureTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'station_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'measure_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['station_id', 'measure_id']);
        $this->forge->createTable('station_measure');
    }

    public function down()
    {
        $this->forge->dropTable('station_measure');
    }
}
