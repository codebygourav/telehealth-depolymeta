<?php

namespace Tests\Feature;

use App\Models\{Patient, User};
use App\Mail\PatientCredentialsMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WordPressPatientRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Spatie\Permission\Models\Role::class)) {
            \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'patient',
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_new_patient_registration_creates_both_and_sends_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v2/patient', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'age' => 30,
            'marital_status' => 'single',
            'mobile' => '9999988888',
            'email' => 'john.doe@example.com',
            'address' => '123 Test St',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'patient',
            'user',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
        ]);

        $this->assertDatabaseHas('patients', [
            'email' => 'john.doe@example.com',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $patient = Patient::where('email', 'john.doe@example.com')->first();

        $this->assertEquals($user->id, $patient->user_id);

        Mail::assertSent(PatientCredentialsMail::class, function ($mail) use ($user) {
            return $mail->hasTo('john.doe@example.com') &&
                   $mail->patientName === 'John Doe' &&
                   $mail->email === 'john.doe@example.com';
        });

        $this->assertDatabaseHas('email_logs', [
            'type' => PatientCredentialsMail::class,
            'to_email' => 'john.doe@example.com',
            'status' => 'sent',
            'patient_id' => $patient->id,
        ]);
    }

    public function test_existing_patient_and_user_returns_directly_without_sending_email(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => bcrypt('existing-password'),
            'mobile' => '9999977777',
        ]);

        $patient = Patient::create([
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'female',
            'age' => 28,
            'marital_status' => 'single',
            'mobile_no' => '9999977777',
            'email' => 'jane.doe@example.com',
            'address' => '456 Sample St',
        ]);

        $response = $this->postJson('/api/v2/patient', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'gender' => 'female',
            'age' => 28,
            'marital_status' => 'single',
            'mobile' => '9999977777',
            'email' => 'jane.doe@example.com',
            'address' => '456 Sample St',
        ]);

        $response->assertStatus(200);
        Mail::assertNotSent(PatientCredentialsMail::class);
    }

    public function test_only_user_exists_creates_patient_and_does_not_send_email(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Sam Smith',
            'email' => 'sam.smith@example.com',
            'password' => bcrypt('user-password'),
            'mobile' => '9999966666',
        ]);

        $response = $this->postJson('/api/v2/patient', [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'gender' => 'male',
            'age' => 45,
            'marital_status' => 'married',
            'father_name' => 'Bob Smith',
            'mobile' => '9999966666',
            'email' => 'sam.smith@example.com',
            'address' => '789 Road St',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('patients', [
            'email' => 'sam.smith@example.com',
            'user_id' => $user->id,
        ]);

        Mail::assertNotSent(PatientCredentialsMail::class);
    }

    public function test_only_patient_exists_creates_user_and_sends_email(): void
    {
        Mail::fake();

        $patient = Patient::create([
            'first_name' => 'Alice',
            'last_name' => 'Wonder',
            'gender' => 'female',
            'age' => 25,
            'marital_status' => 'single',
            'mobile_no' => '9999955555',
            'email' => 'alice.wonder@example.com',
            'address' => 'Underland',
        ]);

        $response = $this->postJson('/api/v2/patient', [
            'first_name' => 'Alice',
            'last_name' => 'Wonder',
            'gender' => 'female',
            'age' => 25,
            'marital_status' => 'single',
            'mobile' => '9999955555',
            'email' => 'alice.wonder@example.com',
            'address' => 'Underland',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => 'alice.wonder@example.com',
        ]);

        $user = User::where('email', 'alice.wonder@example.com')->first();
        $patient->refresh();

        $this->assertEquals($user->id, $patient->user_id);

        Mail::assertSent(PatientCredentialsMail::class, function ($mail) {
            return $mail->hasTo('alice.wonder@example.com');
        });

        $this->assertDatabaseHas('email_logs', [
            'type' => PatientCredentialsMail::class,
            'to_email' => 'alice.wonder@example.com',
            'status' => 'sent',
            'patient_id' => $patient->id,
        ]);
    }
}
