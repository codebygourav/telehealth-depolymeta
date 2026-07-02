<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds critical indexes for performance optimization
     */
    public function up(): void
    {
        // Patients table indexes
        Schema::table('patients', function (Blueprint $table) {
            // Index for filtering by source and create_user_account (used frequently in queries)
            if (! $this->indexExists('patients', 'patients_source_create_user_account_index')) {
                $table->index(['source', 'create_user_account'], 'patients_source_create_user_account_index');
            }
        });

        // Availabilities table indexes
        Schema::table('availabilities', function (Blueprint $table) {
            // Index for is_available filter (used in most queries)
            if (! $this->indexExists('availabilities', 'availabilities_is_available_index')) {
                $table->index('is_available', 'availabilities_is_available_index');
            }

            // Index for is_recurring filter
            if (! $this->indexExists('availabilities', 'availabilities_is_recurring_index')) {
                $table->index('is_recurring', 'availabilities_is_recurring_index');
            }

            // Index for consultation_type filter
            if (! $this->indexExists('availabilities', 'availabilities_consultation_type_index')) {
                $table->index('consultation_type', 'availabilities_consultation_type_index');
            }

            // Composite index for date queries with is_available
            if (! $this->indexExists('availabilities', 'availabilities_date_is_available_index')) {
                $table->index(['date', 'is_available'], 'availabilities_date_is_available_index');
            }

            // Composite index for recurring queries
            if (! $this->indexExists('availabilities', 'availabilities_recurring_dates_index')) {
                $table->index(['is_recurring', 'recurring_start_date', 'recurring_end_date'], 'availabilities_recurring_dates_index');
            }
        });

        // Doctor reviews table indexes
        Schema::table('doctor_reviews', function (Blueprint $table) {
            // Composite index for doctor_id and is_active (most common query pattern)
            if (! $this->indexExists('doctor_reviews', 'doctor_reviews_doctor_active_index')) {
                $table->index(['doctor_id', 'is_active'], 'doctor_reviews_doctor_active_index');
            }

            // Composite index for featured reviews
            if (! $this->indexExists('doctor_reviews', 'doctor_reviews_active_featured_index')) {
                $table->index(['is_active', 'is_featured'], 'doctor_reviews_active_featured_index');
            }
        });

        // Advertisements table indexes
        Schema::table('advertisements', function (Blueprint $table) {
            // Index for is_active filter
            if (! $this->indexExists('advertisements', 'advertisements_is_active_index')) {
                $table->index('is_active', 'advertisements_is_active_index');
            }

            // Composite index for active and created_at (for latest queries)
            if (! $this->indexExists('advertisements', 'advertisements_active_created_index')) {
                $table->index(['is_active', 'created_at'], 'advertisements_active_created_index');
            }
        });

        // Appointments table indexes
        Schema::table('appointments', function (Blueprint $table) {
            // Index for status filter
            if (! $this->indexExists('appointments', 'appointments_status_index')) {
                $table->index('status', 'appointments_status_index');
            }

            // Composite index for doctor_id and status
            if (! $this->indexExists('appointments', 'appointments_doctor_status_index')) {
                $table->index(['doctor_id', 'status'], 'appointments_doctor_status_index');
            }

            // Composite index for patient_id and status
            if (! $this->indexExists('appointments', 'appointments_patient_status_index')) {
                $table->index(['patient_id', 'status'], 'appointments_patient_status_index');
            }
        });

        // Doctors table indexes
        Schema::table('doctors', function (Blueprint $table) {
            // Index for status filter
            if (! $this->indexExists('doctors', 'doctors_status_index')) {
                $table->index('status', 'doctors_status_index');
            }

            // Index for user_id (used in lookups)
            if (! $this->indexExists('doctors', 'doctors_user_id_index')) {
                $table->index('user_id', 'doctors_user_id_index');
            }
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            // Index for email lookups (should be unique but adding for reference)
            if (! $this->indexExists('users', 'users_email_index')) {
                $table->index('email', 'users_email_index');
            }
        });

        // Doctor Availability indexes (additional)
        Schema::table('availabilities', function (Blueprint $table) {
            // Composite index for doctor_id and is_available
            if (! $this->indexExists('availabilities', 'availabilities_doctor_available_index')) {
                $table->index(['doctor_id', 'is_available'], 'availabilities_doctor_available_index');
            }

            // Index for appointment_date lookups
            if (! $this->indexExists('availabilities', 'availabilities_date_index')) {
                $table->index('date', 'availabilities_date_index');
            }
        });

        // Medical Reports indexes
        Schema::table('medical_reports', function (Blueprint $table) {
            // Index for patient_id filtering
            if (! $this->indexExists('medical_reports', 'medical_reports_patient_id_index')) {
                $table->index('patient_id', 'medical_reports_patient_id_index');
            }

            // Index for date queries
            if (! $this->indexExists('medical_reports', 'medical_reports_report_date_index')) {
                $table->index('report_date', 'medical_reports_report_date_index');
            }
        });

        // Video Consultations indexes
        Schema::table('video_consultations', function (Blueprint $table) {
            // Index for appointment_id lookups
            if (! $this->indexExists('video_consultations', 'video_consultations_appointment_id_index')) {
                $table->index('appointment_id', 'video_consultations_appointment_id_index');
            }

            // Index for status filtering
            if (! $this->indexExists('video_consultations', 'video_consultations_status_index')) {
                $table->index('status', 'video_consultations_status_index');
            }
        });

        // Department Doctor pivot table
        Schema::table('department_doctor', function (Blueprint $table) {
            // Composite index for both foreign keys
            if (! $this->indexExists('department_doctor', 'department_doctor_foreign_index')) {
                $table->index(['department_id', 'doctor_id'], 'department_doctor_foreign_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropIndex('patients_source_create_user_account_index');
        });

        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropIndex('availabilities_is_available_index');
            $table->dropIndex('availabilities_is_recurring_index');
            $table->dropIndex('availabilities_consultation_type_index');
            $table->dropIndex('availabilities_date_is_available_index');
            $table->dropIndex('availabilities_recurring_dates_index');
        });

        Schema::table('doctor_reviews', function (Blueprint $table) {
            $table->dropIndex('doctor_reviews_doctor_active_index');
            $table->dropIndex('doctor_reviews_active_featured_index');
        });

        Schema::table('advertisements', function (Blueprint $table) {
            $table->dropIndex('advertisements_is_active_index');
            $table->dropIndex('advertisements_active_created_index');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex('appointments_status_index');
            $table->dropIndex('appointments_doctor_status_index');
            $table->dropIndex('appointments_patient_status_index');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex('doctors_status_index');

            $table->dropIndex('doctors_user_id_index');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_index');
        });

        Schema::table('availabilities', function (Blueprint $table) {
            $table->dropIndex('availabilities_doctor_available_index');
            $table->dropIndex('availabilities_date_index');
        });

        Schema::table('medical_reports', function (Blueprint $table) {
            $table->dropIndex('medical_reports_patient_id_index');
            $table->dropIndex('medical_reports_report_date_index');
        });

        Schema::table('video_consultations', function (Blueprint $table) {
            $table->dropIndex('video_consultations_appointment_id_index');
            $table->dropIndex('video_consultations_status_index');
        });

        Schema::table('department_doctor', function (Blueprint $table) {
            $table->dropIndex('department_doctor_foreign_index');
        });
    }

    /**
     * Check if an index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        $database = $connection->getDatabaseName();

        if ($driver === 'sqlite') {
            $result = $connection->select(
                "SELECT COUNT(*) as count FROM sqlite_master WHERE type = 'index' AND name = ?",
                [$indexName]
            );
            return $result[0]->count > 0;
        }

        $result = $connection->select(
            'SELECT COUNT(*) as count FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return $result[0]->count > 0;
    }
};
