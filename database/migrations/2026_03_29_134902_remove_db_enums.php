<?php

use App\Enums\AttachmentType;
use App\Enums\LogEventTypes;
use App\Enums\ProjectState;
use App\Enums\PublishState;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (config('database.default') !== 'sqlite') {
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('type_id');
            });
            Schema::table('attachments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('publish_state_id');
            });
        }
        Schema::table('attachments', function (Blueprint $table) {
            $table->string('type')->default(AttachmentType::ZIP->value);
            $table->string('publish_state')->default(PublishState::PRIVATE->value);
        });

        if (config('database.default') !== 'sqlite') {
            Schema::table('log_events', function (Blueprint $table) {
                $table->dropConstrainedForeignId('type_id');
            });
        }
        Schema::table('log_events', function (Blueprint $table) {
            $table->string('type')->default(LogEventTypes::INFO->value);
        });

        if (config('database.default') !== 'sqlite') {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('state_id');
            });
            Schema::table('projects', function (Blueprint $table) {
                $table->dropConstrainedForeignId('publish_state_id');
            });
        }
        Schema::table('projects', function (Blueprint $table) {
            $table->string('state')->default(ProjectState::DRAFT->value);
            $table->string('publish_state')->default(PublishState::PRIVATE->value);
        });

        if (config('database.default') !== 'sqlite') {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('publish_state_id');
            });
        }
        Schema::table('pages', function (Blueprint $table) {
            $table->string('publish_state')->default(PublishState::PRIVATE->value);
        });

        Schema::dropIfExists('lu_log_event_types');
        Schema::dropIfExists('lu_project_states');
        Schema::dropIfExists('lu_publish_states');
        Schema::dropIfExists('lu_attachment_types');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::create('lu_attachment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('lu_publish_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('lu_project_states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('lu_log_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('publish_state');
            $table->foreignId('publish_state_id')->constrained('lu_publish_states');
        });
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('publish_state');
            $table->dropColumn('state');
            $table->foreignId('publish_state_id')->constrained('lu_publish_states');
            $table->foreignId('state_id')->constrained('lu_project_states');
        });
        Schema::table('log_events', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignId('type_id')->constrained('lu_log_event_types');
        });
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('publish_state');
            $table->dropColumn('type');
            $table->foreignId('publish_state_id')->constrained('lu_publish_states');
            $table->foreignId('type_id')->constrained('lu_attachment_types');
        });
    }
};
