<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gesture_samples', function (Blueprint $table) {
            $table->string('sample_type', 20)->default('static')->after('label')->index();
        });
    }

    public function down(): void
    {
        Schema::table('gesture_samples', function (Blueprint $table) {
            $table->dropColumn('sample_type');
        });
    }
};
