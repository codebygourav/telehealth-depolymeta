<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>

<body>

    <h2>Payment Receipt</h2>

    <table>
        <tr>
            <td>Receipt ID</td>
            <td>{{ $payment->id }}</td>
        </tr>
        <tr>
            <td>Transaction ID</td>
            <td>{{ $payment->razorpay_payment_id }}</td>
        </tr>
        <tr>
            <td>Order ID</td>
            <td>{{ $payment->razorpay_order_id }}</td>
        </tr>
        <tr>
            <td>Amount</td>
            <td>₹{{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td>Status</td>
            <td>{{ $payment->status->label() }} </td>
        </tr>
        <tr>
            <td>Date</td>
            <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
        </tr>
        <tr>
            <td>Paid To</td>
            <td>
                Dr. {{ optional($payment->appointment->doctor)->first_name }}
                {{ optional($payment->appointment->doctor)->last_name }}
            </td>
        </tr>
        <tr>
            <td>Payment Method</td>
            <td>{{ strtoupper($payment->payment_method ?? 'UPI') }}
            </td>
        </tr>
        @if ($payment->vpa)
            <tr>
                <td>UPI ID</td>
                <td>{{ $payment->vpa }}</td>
            </tr>
        @endif
        @if ($payment->bank)
            <tr>
                <td>Bank</td>
                <td>{{ $payment->bank }}</td>
            </tr>
        @endif
    </table>

    <p style="margin-top:30px;text-align:center;">
        Thank you for your payment.
    </p>

</body>

</html>
