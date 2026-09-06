<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->text('message');
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
            $table->index('handled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
