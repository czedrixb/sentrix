<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->unsignedInteger('vacancies')->default(1);
            $table->string('employment_type')->nullable();
            $table->longText('description')->nullable();
            $table->string('apply_email')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();

            $table->index(['is_open', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
