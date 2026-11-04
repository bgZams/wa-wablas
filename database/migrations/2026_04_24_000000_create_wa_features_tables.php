<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 50);
            $table->boolean('is_group')->default(false);
            $table->enum('kind', ['text', 'location', 'link'])->default('text');
            $table->text('message')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('link_url')->nullable();
            $table->timestamp('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('response')->nullable();
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->text('reply');
            $table->enum('match_type', ['exact', 'contains', 'starts_with'])->default('contains');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event')->default('message');
            $table->string('phone', 50)->nullable();
            $table->string('status')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index('phone');
            $table->index('status');
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'group_tag')) {
                $table->string('group_tag')->nullable()->after('name');
            }
            if (!Schema::hasColumn('contacts', 'is_group')) {
                $table->boolean('is_group')->default(false)->after('group_tag');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'kind')) {
                $table->string('kind')->default('text')->after('message');
            }
            if (!Schema::hasColumn('messages', 'metadata')) {
                $table->json('metadata')->nullable()->after('kind');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('auto_replies');
        Schema::dropIfExists('scheduled_messages');

        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'group_tag')) $table->dropColumn('group_tag');
            if (Schema::hasColumn('contacts', 'is_group')) $table->dropColumn('is_group');
        });
        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'kind')) $table->dropColumn('kind');
            if (Schema::hasColumn('messages', 'metadata')) $table->dropColumn('metadata');
        });
    }
};
