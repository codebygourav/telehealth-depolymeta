<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id');
            $table->decimal('amount', 8, 2);
            $table->string('payment_method', 255)->nullable();
            $table->string('currency', 10)->default('INR');
            $table->string('transaction_id')->nullable()->unique();
            $table->string('razorpay_order_id', 100)->nullable();
            $table->string('razorpay_payment_id', 100)->nullable();
            $table->string('razorpay_signature', 255)->nullable();
            $table->enum('status', array_map(fn($case) => $case->value, PaymentStatus::cases()))->default(PaymentStatus::default());
            // Additional Razorpay response fields
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->string('bank')->nullable();
            $table->json('card_details')->nullable();
            $table->string('card_id')->nullable();
            $table->string('vpa')->nullable();
            $table->string('wallet')->nullable();
            $table->string('invoice_id')->nullable();
            $table->boolean('international')->default(false);
            $table->decimal('amount_refunded', 8, 2)->default(0);
            $table->string('refund_status')->nullable();
            $table->boolean('captured')->default(false);
            $table->decimal('fee', 8, 2)->default(0);
            $table->decimal('tax', 8, 2)->default(0);
            $table->string('error_code')->nullable();
            $table->string('error_description')->nullable();
            $table->string('error_source')->nullable();
            $table->string('error_step')->nullable();
            $table->string('error_reason')->nullable();
            $table->json('acquirer_data')->nullable();
            $table->json('notes')->nullable();
            $table->timestamp('razorpay_created_at')->nullable();
            $table->longText('full_response')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->index('appointment_id');
            $table->index('razorpay_order_id');
            $table->index('razorpay_payment_id');
            $table->index('status');
            $table->index(['appointment_id', 'status']);

            $table->uuid('created_by')->nullable()->index();
            $table->uuid('updated_by')->nullable()->index();
            $table->uuid('deleted_by')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');

        if (config('database.default') === 'pgsql') {
            // Drop custom types only if no longer used
            DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_method_enum') THEN DROP TYPE payment_method_enum; END IF; END $$;");
            DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_status') THEN DROP TYPE payment_status; END IF; END $$;");
        }
    }
};