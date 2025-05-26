<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection = 'mysql_2';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Schema::create('cache', function (Blueprint $table) {
        //     $table->string('key')->primary();
        //     $table->mediumText('value');
        //     $table->integer('expiration');
        // });

        // Schema::create('cache_locks', function (Blueprint $table) {
        //     $table->string('key')->primary();
        //     $table->string('owner');
        //     $table->integer('expiration');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::dropIfExists('cache');
        // Schema::dropIfExists('cache_locks');
    }
};
