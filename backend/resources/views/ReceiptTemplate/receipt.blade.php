<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            margin: 0;
            padding: 30px;
            font-size: 13px;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .logo {
            height: 60px;
            margin-bottom: 10px;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            color: #0f766e;
        }

        .subtitle {
            font-size: 14px;
            color: #666;
        }

        .receipt-box {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .section-title {
            background: #f4f7f9;
            padding: 10px 15px;
            font-weight: bold;
            color: #0f766e;
            border-bottom: 1px solid #ddd;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        td.label {
            width: 35%;
            font-weight: bold;
            color: #444;
            background: #fafafa;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }

        .status {
            font-weight: bold;
            color: green;
        }
    </style>
</head>

<body>

    @php
        use App\Services\SettingService;
        use Illuminate\Support\Arr;

        $appointment = $payment->appointment;
        $patient = $appointment->patient;
        $doctor = $appointment->doctor;

        // Normalize to match SettingService logic
        $consultationType = $appointment->consultation_type ?? 'video';

        $contactInfo = SettingService::getConsultationSupportInfo($consultationType);

        $supportPhone = $contactInfo['phone'] ?? '';
        $supportEmail = $contactInfo['support_email'] ?? '';
        $supportAddress = $contactInfo['address'] ?? '';

        // Whether this appointment is online/video
        $isOnline = in_array(strtolower(trim($consultationType)), ['video', 'online']);

        // Payment breakdown (mimic TransactionResource logic)
        $method = strtolower($payment->payment_method ?? '');
        $paymentDetails = [
            'type' => '',
            'method' => '',
            'card_last4' => null,
            'bank_name' => null,
            'card_type' => null,
            'network' => null,
            'upi_id' => null,
        ];

        if ($method === 'card' && $payment->card_id) {
            $cardDetails = Arr::wrap($payment->card_details ?? []);
            $paymentDetails['type'] = 'Card';
            $paymentDetails['method'] = strtoupper($cardDetails['network'] ?? 'CARD');
            $paymentDetails['card_last4'] = $cardDetails['last4'] ?? substr($payment->card_id, -4);
            $paymentDetails['bank_name'] = $cardDetails['issuer'] ?? 'Unknown';
            $paymentDetails['card_type'] = $cardDetails['type'] ?? null;
            $paymentDetails['network'] = $cardDetails['network'] ?? null;
        } elseif ($method === 'upi' && $payment->vpa) {
            $paymentDetails['type'] = 'UPI';
            $paymentDetails['method'] = ucfirst($payment->wallet ?? 'UPI');
            $paymentDetails['upi_id'] = $payment->vpa;
            $paymentDetails['bank_name'] = $payment->bank;
        } elseif ($method === 'netbanking') {
            $paymentDetails['type'] = 'Net Banking';
            $paymentDetails['method'] = strtoupper($payment->bank ?? 'Netbanking');
            $paymentDetails['bank_name'] = $payment->bank;
        } elseif ($method === 'wallet') {
            $paymentDetails['type'] = 'Wallet';
            $paymentDetails['method'] = ucfirst($payment->wallet ?? 'Wallet');
        } else {
            $paymentDetails['type'] = ucfirst($method ?: 'Payment');
            $paymentDetails['method'] = ucfirst($method ?: 'Razorpay');
        }
    @endphp

    <!-- HEADER -->
    <div class="header">

        {{-- Logo --}}
        <img src="{{ asset('images/cmc-telehealth-black.png') }}" class="logo">
        <div class="title">
            Telehealth Deploymeta
        </div>

        <div class="subtitle">
            Appointment / Transaction Receipt
        </div>
    </div>

    <!-- APPOINTMENT DETAILS -->
    <div class="receipt-box">

        <div class="section-title">
            Appointment Details
        </div>

        <table>
            @if ($patient->first_name || $patient->last_name)
                <tr>
                    <td class="label">Patient Name</td>
                    <td>
                        {{ trim(($patient->first_name ?? '') . ' ' . ($patient->last_name ?? '')) }}
                    </td>
                </tr>
            @endif
            @if ($doctor->first_name || $doctor->last_name)
                <tr>
                    <td class="label">Doctor</td>
                    <td>
                        Dr.
                        {{ trim(($doctor->first_name ?? '') . ' ' . ($doctor->last_name ?? '')) }}
                    </td>
                </tr>
            @endif
            @if ($isOnline)
                <tr>
                    <td class="label">Consultation Type</td>
                    <td>
                        {{ $isOnline ? 'Online Telehealth Consultation' : 'In-Person Consultation' }}
                    </td>
                </tr>
            @endif
            @if ($appointment->appointment_time || $appointment->appointment_date)
                <tr>
                    <td class="label">Consultation Time</td>
                    <td>
                        {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Date & Time</td>
                    <td>
                        {{ optional($appointment->appointment_date)->format('d M Y') }}
                        ,
                        {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('h:i A') }}
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <!-- PAYMENT DETAILS -->
    <div class="receipt-box" style="margin-top:20px;">

        <div class="section-title">
            Payment Details
            <span>Your Receipt ID is: {{ $payment->id }}</span>
        </div>

        <table>
            <tr>
                <td class="label">Transaction ID</td>
                <td>{{ $payment->razorpay_payment_id }}</td>
            </tr>
            <tr>
                <td class="label">Order ID</td>
                <td>{{ $payment->razorpay_order_id }}</td>
            </tr>
            @if ($payment->created_at)
                <tr>
                    <td class="label">Transaction Date & Time</td>
                    <td>
                        {{ $payment->created_at->format('d M Y, h:i A') }}
                    </td>
                </tr>
            @endif
            @if ($payment->amount)
                <tr>
                    <td class="label">Amount Paid</td>
                    <td>
                        ₹{{ number_format($payment->amount, 2) }}
                    </td>
                </tr>
            @endif
            @if($paymentDetails['type'])
            <tr>
                <td class="label">Payment Type</td>
                <td>
                    {{ $paymentDetails['type'] }}
                </td>
            </tr>
            @endif
            @if($paymentDetails['method'])
            <tr>
                <td class="label">Payment Method</td>
                <td>
                    {{ $paymentDetails['method'] }}
                </td>
            </tr>
            @endif
            @if($paymentDetails['method'])
            <tr>
                <td class="label">Payment Status</td>
                <td class="status">
                    {{ strtoupper($payment->status->label()) }}
                </td>
            </tr>
            @endif
            {{-- Special display for cards --}}
            @if ($paymentDetails['type'] === 'Card')
                <tr>
                    <td class="label">Card Last 4</td>
                    <td>**** {{ $paymentDetails['card_last4'] }}</td>
                </tr>
                @if ($paymentDetails['bank_name'])
                    <tr>
                        <td class="label">Bank</td>
                        <td>{{ $paymentDetails['bank_name'] }}</td>
                    </tr>
                @endif
                @if ($paymentDetails['network'])
                    <tr>
                        <td class="label">Card Network</td>
                        <td>{{ $paymentDetails['network'] }}</td>
                    </tr>
                @endif
                @if ($paymentDetails['card_type'])
                    <tr>
                        <td class="label">Card Type</td>
                        <td>{{ $paymentDetails['card_type'] }}</td>
                    </tr>
                @endif
            @endif

            {{-- Special display for UPI --}}
            @if ($paymentDetails['type'] === 'UPI')
                <tr>
                    <td class="label">UPI ID</td>
                    <td>{{ $paymentDetails['upi_id'] }}</td>
                </tr>
                @if ($paymentDetails['bank_name'])
                    <tr>
                        <td class="label">Bank</td>
                        <td>{{ $paymentDetails['bank_name'] }}</td>
                    </tr>
                @endif
            @endif

            {{-- Special display for Net Banking --}}
            @if ($paymentDetails['type'] === 'Net Banking' && $paymentDetails['bank_name'])
                <tr>
                    <td class="label">Bank</td>
                    <td>{{ $paymentDetails['bank_name'] }}</td>
                </tr>
            @endif

        </table>
    </div>

    @if ($supportPhone || $supportEmail || $supportAddress)
        <div class="receipt-box" style="margin-top:20px;">
            <div class="section-title">
                Support Information
            </div>
            <table>
                @if ($supportPhone)
                    <tr>
                        <td class="label">Phone Number</td>
                        <td>{{ $supportPhone }}</td>
                    </tr>
                @endif
                @if ($supportEmail)
                    <tr>
                        <td class="label">Support Email</td>
                        <td>{{ $supportEmail }}</td>
                    </tr>
                @endif
                @if ($supportAddress)
                    <tr>
                        <td class="label">Address</td>
                        <td>{{ $supportAddress }}</td>
                    </tr>
                @endif

            </table>
        </div>
    @endif

    <!-- FOOTER -->
    <div class="footer">
        Thank you for choosing Telehealth Deploymeta.
        <br>
        This is a computer-generated receipt.
    </div>

</body>

</html>
