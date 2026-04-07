<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDailyForecastTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true],
            'location_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'date'            => ['type' => 'DATE', 'null' => false],
            'min_temperature' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'max_temperature' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'humidity'        => ['type' => 'INT', 'constraint' => 3, 'null' => false],
            'wind_speed'      => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'weather'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('location_id', 'locations', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['location_id', 'date']);
        $this->forge->createTable('daily_forecast');
    }

    public function down()
    {
        $this->forge->dropTable('daily_forecast');
    }
}
