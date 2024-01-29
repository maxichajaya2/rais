<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void {
    Schema::create('Dependencia', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('facultad_id');
      $table->string('dependencia')->nullable(false);
      //  Fks
      $table->foreign('facultad_id')->references('id')->on('Facultad')
        ->cascadeOnUpdate()
        ->cascadeOnDelete();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void {
    Schema::down('Dependencia');
  }
};
