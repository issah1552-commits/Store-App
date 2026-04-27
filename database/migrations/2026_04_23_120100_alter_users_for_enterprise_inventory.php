<?php

use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('default_location_id')->nullable()->after('role_id')->constrained('locations')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->string('status')->default(UserStatus::Active->value)->after('password')->index();
            $table->boolean('is_active')->default(true)->after('status')->index();
            $table->timestamp('last_activity_at')->nullable()->after('remember_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_location_id');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['phone', 'status', 'is_active', 'last_activity_at']);
        });
    }
};
