<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Confirmed - CMC Telehealth</title>

    @vite(['resources/css/filament/admin/theme.css'])

    <style>
        body {
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .success-animation {
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .card-slide {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="max-w-3xl mx-auto bg-white p-6 rounded-2xl shadow-[0px_2px_8px_rgba(99,99,99,0.2)]">
        @if (isset($error) && $error)
            <!-- Error Header -->
            <div class="bg-white rounded-2xl shadow-[0px_2px_8px_rgba(99,99,99,0.2)] p-4 mb-4 text-center card-slide">
                <div class="success-animation inline-block mb-2">
                    <div class="bg-red-100 rounded-xl p-4 inline-flex">
                        <x-heroicon-o-x-circle class="w-15 h-15 text-red-600" />
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-red-600 mb-3">Booking Failed</h1>
                <p class="text-gray-600 text-lg">{{ $message ?? 'Your appointment booking could not be completed.' }}
                </p>
            </div>

            <!-- Error Message Card -->
            <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-5 mb-6 card-slide">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-red-900 mb-1">What went wrong?</h3>
                        <p class="text-sm text-red-800">
                            {{ $message ?? 'An error occurred while processing your booking. Please try again or contact support.' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-600 text-sm">
                <p>Need help? Contact us at <a href="mailto:support@cmctelehealth.com"
                        class="text-primary hover:underline font-medium">support@cmctelehealth.com</a></p>
            </div>
        @else
            @php
                $appointment = $appointment ?? null;
                $patientName =
                    $appointment && $appointment->patient
                        ? $appointment->patient->first_name . ' ' . ($appointment->patient->last_name ?? '')
                        : 'N/A';
                $doctorName =
                    $appointment && $appointment->doctor
                        ? $appointment->doctor->first_name . ' ' . ($appointment->doctor->last_name ?? '')
                        : 'N/A';
                $appointmentDate = $appointment
                    ? \Carbon\Carbon::parse($appointment->appointment_date)->format('d M Y')
                    : 'N/A';
                $appointmentTime = $appointment
                    ? \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A')
                    : 'N/A';
                $bookingReference = $appointment ? '#' . strtoupper(substr($appointment->slug, -8)) : 'N/A';
            @endphp

            <!-- Success Header -->
            <div class="bg-white rounded-2xl shadow-[0px_2px_8px_rgba(99,99,99,0.2)] p-4 mb-4 text-center card-slide">
                <div class="success-animation inline-block mb-2">
                    <div class="bg-primary/10 rounded-xl p-4 inline-flex">
                        <x-heroicon-o-check-circle class="w-15 h-15 text-primary" />
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-primary mb-3">Booking Confirmed!</h1>
                <p class="text-gray-600 text-lg">Your appointment has been successfully scheduled</p>
            </div>

            <!-- Booking Details Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-6 card-slide" style="animation-delay: 0.1s;">
                <div class="bg-primary p-4">
                    <h2 class="text-white text-xl font-semibold flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-6 h-6" />
                        Appointment Details
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Patient Info -->
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-primary/10 p-2 rounded-lg">
                                    <x-heroicon-o-user class="w-5 h-5 svg-color-primary" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 font-medium">Patient Name</p>
                                    <p class="text-gray-900 font-semibold">{{ $patientName }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-primary/10 p-2 rounded-lg">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 font-medium">Appointment Date</p>
                                    <p class="text-gray-900 font-semibold">{{ $appointmentDate }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Doctor & Time Info -->
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-primary/10 p-2 rounded-lg">
                                    <x-heroicon-o-user-circle class="w-5 h-5 svg-color-primary" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 font-medium">Doctor</p>
                                    <p class="text-gray-900 font-semibold">{{ $doctorName }}</p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-primary/10 p-2 rounded-lg">
                                    <x-heroicon-o-clock class="w-5 h-5 svg-color-primary" />
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 font-medium">Time</p>
                                    <p class="text-gray-900 font-semibold">{{ $appointmentTime }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Reference -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between bg-primary/5 p-4 rounded-xl">
                            <div class="flex items-center gap-3">
                                <x-heroicon-o-ticket class="w-6 h-6 svg-color-primary" />
                                <span class="text-gray-700 font-medium">Booking Reference</span>
                            </div>
                            <span class="text-xl font-bold text-primary">{{ $bookingReference }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Important Notice -->
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-5 mb-6 card-slide"
                style="animation-delay: 0.2s;">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-amber-900 mb-1">Important Reminders</h3>
                        <ul class="text-sm text-amber-800 space-y-1 list-disc list-inside">
                            <li>Please arrive 15 minutes before your appointment time</li>
                            <li>Bring a valid ID and your booking reference</li>
                            <li>Download and keep your receipt for records</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 card-slide" style="animation-delay: 0.3s;">
                <button
                    class="bg-primary hover:bg-primary/90 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                    Download Receipt
                </button>
                <button
                    class="bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5" />
                    Add to Calendar
                </button>
            </div>

            <!-- Important Notice -->
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-lg p-5 mb-6 card-slide"
                style="animation-delay: 0.2s;">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-semibold text-amber-900 mb-1">Important Reminders</h3>
                        <ul class="text-sm text-amber-800 space-y-1 list-disc list-inside">
                            <li>Please arrive 15 minutes before your appointment time</li>
                            <li>Bring a valid ID and your booking reference</li>
                            <li>Download and keep your receipt for records</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 card-slide" style="animation-delay: 0.3s;">
                <button
                    class="bg-primary hover:bg-primary/90 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <x-heroicon-o-arrow-down-tray class="w-5 h-5" />
                    Download Receipt
                </button>
                <button
                    class="bg-white hover:bg-gray-50 text-gray-700 border-2 border-gray-200 font-semibold py-4 px-6 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <x-heroicon-o-calendar-days class="w-5 h-5" />
                    Add to Calendar
                </button>
            </div>
        @endif

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-600 text-sm">
            <p>Need help? Contact us at <a href="mailto:support@cmctelehealth.com"
                    class="text-primary hover:underline font-medium">support@cmctelehealth.com</a></p>
        </div>
    </div>
</body>

</html>
