<?php
// database/migrations/xxxx_xx_xx_create_contacts_and_messages_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel Contacts
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique(); // Nomor telepon unik
            $table->string('name')->nullable(); // Nama kontak (opsional)
            $table->timestamp('last_message_at')->nullable(); // Waktu pesan terakhir
            $table->timestamps();

            $table->index('phone');
        });

        // Tabel Messages
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('wablas_message_id')->unique()->nullable(); // ID dari Wablas untuk mencegah duplikasi
            $table->text('message');
            $table->enum('type', ['incoming', 'outgoing'])->default('incoming');
            $table->string('status')->default('received'); // sent, failed, received, etc
            $table->timestamp('message_timestamp'); // Waktu pesan dari API
            $table->timestamps();

            $table->index(['contact_id', 'message_timestamp']);
            $table->index('wablas_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('contacts');
    }
};
