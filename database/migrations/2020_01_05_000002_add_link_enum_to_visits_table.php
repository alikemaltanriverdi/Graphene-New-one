<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLinkEnumToVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("ALTER TABLE visits MODIFY resource_type ENUM('page','app','workflow','link') NOT NULL DEFAULT 'page';");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE visits MODIFY resource_type ENUM('page','app','flow') NOT NULL DEFAULT 'page';");
    }
}