<?php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateHourlyForecastTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true, 'unsigned' => true],
            'daily_forecast_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'time'              => ['type' => 'TIME', 'null' => false],
            'temperature'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'humidity'          => ['type' => 'INT', 'constraint' => 3, 'null' => false],
            'wind_speed'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => false],
            'weather'           => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => false],
            'created_at datetime default current_timestamp',
            'updated_at datetime default current_timestamp on update current_timestamp',
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addForeignKey('daily_forecast_id', 'daily_forecast', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addUniqueKey(['daily_forecast_id', 'time']);
        $this->forge->createTable('hourly_forecast');
    }

    public function down()
    {
        $this->forge->dropTable('hourly_forecast');
    }
}
