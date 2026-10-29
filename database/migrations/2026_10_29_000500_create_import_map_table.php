<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** v0.2 (Import module): which local record each imported item became, so re-runs update instead of duplicating. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_map', function (Blueprint $table) {
            $table->id();
            $table->string('source', 190);
            $table->string('kind', 20);
            $table->string('remote_id', 64);
            $table->unsignedBigInteger('local_id');
            $table->string('remote_modified', 40)->nullable();
            $table->timestamps();
            $table->unique(['source', 'kind', 'remote_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_map');
    }
};
