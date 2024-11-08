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
            $table->string('proxy')->nullable()->after('videos');
        });
        Schema::table('settings', function (Blueprint $table) {
            $table->string('api_proxy_get')->nullable()->after('proxy');
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
            if(Schema::hasColumn('channels', 'proxy')){
                $table->dropColumn('proxy');
            }
        });
        Schema::table('settings', function (Blueprint $table) {
            if(Schema::hasColumn('settings', 'api_proxy_get')){
                $table->dropColumn('api_proxy_get');
            }
        });
    }
};
