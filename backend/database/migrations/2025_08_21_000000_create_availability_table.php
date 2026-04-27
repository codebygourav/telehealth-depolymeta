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
        Schema::create('availabilities', function (Blueprint $table) {
            $table->uuid('id')->primary(); // ✅ UUID primary key
            $table->uuid('doctor_id');

            // Each record represents a single date’s availability
            $table->date('date')
                ->nullable()
                ->comment('Concrete date of availability for this slot');

            $table->enum('day_of_week', [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ])->nullable()
                ->comment('Day name derived from date or recurrence pattern');

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedInteger('capacity')->default(1);
            $table->string('doctor_room')->nullable()->comment('Room identifier/label assigned to doctor for this slot');
            $table->enum('consultation_type', ['in-person', 'video'])->default('in-person');
            $table->enum('opd_type', ['general', 'private'])
                ->default('general')
                ->comment('General OPD or Private OPD')->nullable();
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);

            // Recurrence metadata
            $table->boolean('is_recurring')
                ->default(false)
                ->comment('True = repeated weekly until recurrence_end_date; False = single/custom slot');

            $table->date('recurring_start_date')
                ->nullable()
                ->comment('Start date of recurrence pattern');

            $table->date('recurring_end_date')
                ->nullable()
                ->comment('End date of recurrence pattern');
            $table->unsignedInteger('recurring_months')
                ->default(3)
                ->comment('Number of months in recurrence pattern');



            $table->timestamps();
            $table->softDeletes();

            // Indexes and relationships
            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctors')
                ->onDelete('cascade');
            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();

            $table->unique(['doctor_id', 'date', 'start_time', 'end_time'], 'doctor_date_time_unique');

            // Performance indexes for API queries

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availabilities');
    }
};
