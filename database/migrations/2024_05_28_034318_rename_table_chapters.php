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
        Schema::rename('chapters', 'chapter_olds');
        Schema::rename('chapter_news', 'chapters');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('chapters', 'chapter_news');
        Schema::rename('chapter_olds', 'chapters');
    }
};
