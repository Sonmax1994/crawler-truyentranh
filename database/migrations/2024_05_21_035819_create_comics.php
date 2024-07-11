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
        Schema::create('comics', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('api_id')->index();
            $table->string('name');
            $table->string('slug');
            $table->tinyInteger('status')->index();
            $table->text('thumb_url')->nullable();
            $table->tinyInteger('sub_docquyen')->index();
            $table->text('content')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comics');
    }
};
