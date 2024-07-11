<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->renameColumn('infor_views', 'info_views');
        });

        Schema::table('rank_comics', function (Blueprint $table) {
            $table->renameColumn('rank_infor', 'rank_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->renameColumn('info_views', 'infor_views');
        });

        Schema::table('rank_comics', function (Blueprint $table) {
            $table->renameColumn('rank_info', 'rank_infor');
        });
    }
};
