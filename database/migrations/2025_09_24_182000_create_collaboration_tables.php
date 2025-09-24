<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Resume shares table
        Schema::create('resume_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('shared_with_email')->nullable();
            $table->string('share_token', 32)->unique();
            $table->json('permissions'); // ['view', 'comment', 'download', 'suggest']
            $table->timestamp('expires_at')->nullable();
            $table->text('message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('access_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['share_token', 'is_active']);
            $table->index(['resume_id', 'shared_with_email']);
            $table->index('expires_at');
        });

        // Resume comments table
        Schema::create('resume_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('commenter_email')->nullable();
            $table->string('commenter_name')->nullable();
            $table->text('content');
            $table->enum('type', ['general', 'suggestion', 'question', 'reply'])->default('general');
            $table->string('section')->nullable(); // Which section of resume
            $table->integer('position_start')->nullable(); // Character position
            $table->integer('position_end')->nullable(); // Character position
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('resume_comments')->cascadeOnDelete();
            $table->enum('status', ['active', 'hidden', 'deleted'])->default('active');
            $table->timestamps();

            $table->index(['resume_id', 'status']);
            $table->index(['resume_id', 'is_resolved']);
            $table->index('parent_id');
        });

        // Collaboration invitations table
        Schema::create('collaboration_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inviter_id')->constrained('users')->cascadeOnDelete();
            $table->string('invitee_email');
            $table->string('invitee_name')->nullable();
            $table->string('invitation_token', 32)->unique();
            $table->json('permissions');
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['invitation_token', 'status']);
            $table->index(['invitee_email', 'status']);
            $table->index('expires_at');
        });

        // Resume collaborators table (for accepted invitations)
        Schema::create('resume_collaborators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('collaborator_email');
            $table->string('collaborator_name')->nullable();
            $table->json('permissions');
            $table->enum('role', ['viewer', 'commenter', 'editor'])->default('viewer');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['resume_id', 'collaborator_email']);
            $table->index(['resume_id', 'is_active']);
        });

        // Collaboration activity log
        Schema::create('collaboration_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_email')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('action'); // 'shared', 'commented', 'viewed', 'downloaded', etc.
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        // Resume feedback/suggestions table
        Schema::create('resume_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reviewer_email')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->enum('type', ['improvement', 'grammar', 'structure', 'content', 'formatting']);
            $table->string('section')->nullable();
            $table->text('original_text')->nullable();
            $table->text('suggested_text')->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'implemented'])->default('pending');
            $table->integer('priority')->default(3); // 1 = high, 5 = low
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'status']);
            $table->index(['resume_id', 'type']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resume_feedback');
        Schema::dropIfExists('collaboration_activities');
        Schema::dropIfExists('resume_collaborators');
        Schema::dropIfExists('collaboration_invitations');
        Schema::dropIfExists('resume_comments');
        Schema::dropIfExists('resume_shares');
    }
};