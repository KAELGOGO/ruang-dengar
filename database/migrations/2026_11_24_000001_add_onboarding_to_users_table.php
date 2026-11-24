<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Onboarding answers ("kenalan sama Dengi"). All optional: the user may skip every question.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nickname', 40)->nullable()->after('name');
            $table->string('mood', 20)->nullable()->after('birth_date');
            $table->string('preferred_topic', 20)->nullable()->after('mood');
            $table->timestamp('onboarded_at')->nullable()->after('preferred_topic');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nickname', 'mood', 'preferred_topic', 'onboarded_at']);
        });
    }
};
