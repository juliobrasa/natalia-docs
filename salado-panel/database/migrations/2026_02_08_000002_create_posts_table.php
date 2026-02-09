<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('day_number')->nullable();
            $table->string('title');
            $table->text('caption');
            $table->text('hashtags')->nullable();
            $table->string('post_type')->default('imagen_unica');
            $table->string('platform')->default('ambos');
            $table->enum('status', ['draft', 'approved', 'scheduled', 'published', 'rejected'])->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->string('meta_post_id')->nullable();
            $table->boolean('ai_generated')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
