<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->integer('subscribe')->nullable()->after('channel_id');
            $table->integer('duration')->nullable()->after('channel_id');
            $table->integer('diffDay')->nullable()->after('channel_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('channels', function (Blueprint $table) {
            if(Schema::hasColumn('channels', 'subscribe')){
                $table->dropColumn('subscribe');
            }
            if(Schema::hasColumn('channels', 'duration')){
                $table->dropColumn('duration');
            }
            if(Schema::hasColumn('channels', 'diffDay')){
                $table->dropColumn('diffDay');
            }
        });
    }
};
