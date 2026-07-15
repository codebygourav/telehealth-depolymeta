<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Prescription</title>
    <style>
        @font-face {
            font-family: 'Alex Brush';
            src: url("data:font/ttf;base64,{{ $alex_brush_font }}") format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .hospital-name {
            font-size: 24px;
            font-weight: bold;
            color: #0056b3;
            text-transform: uppercase;
        }

        .doctor-info {
            float: left;
            width: 50%;
        }

        .patient-info {
            float: right;
            width: 45%;
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .prescription-body {
            margin-top: 30px;
        }

        .rx-symbol {
            font-size: 32px;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .footer {
            margin-top: 50px;
            position: relative;
        }

        .signature-container {
            float: right;
            text-align: center;
            width: 200px;
        }

        .signature-image {
            max-width: 150px;
            max-height: 80px;
        }

        .stamp-container {
            float: left;
            text-align: center;
            width: 200px;
        }

        .stamp-image {
            max-width: 120px;
            max-height: 120px;
            opacity: 0.8;
        }

        .signature-label {
            border-top: 1px solid #777;
            margin-top: 5px;
            font-size: 12px;
        }

        .note {
            margin-top: 30px;
            font-style: italic;
            font-size: 11px;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="header">
        @if (isset($hospital_logo_url) && $hospital_logo_url)
            <img src="{{ $hospital_logo_url }}" style="max-height: 60px; margin-bottom: 5px;" alt="Hospital Logo"><br>
        @endif
        <div>{{ $hospital_address ?? '123 Main St, Anytown, USA' }}</div>
        <div>Phone: {{ $hospital_phone ?? '555-555-5555' }} | Email: {{ $hospital_email ?? 'info@cmctelehealth.com' }}
        </div>
    </div>

    <div class="info-section">
        <div class="doctor-info">
            <strong>Doctor:</strong> {{ $doctor->first_name }} {{ $doctor->last_name }}<br>
            @if (!empty($doctor->sub_title))
                <div class="doctor-sub-title">{{ $doctor->sub_title }}</div>
            @endif
            {{ $doctor->qualification }}<br>
            License: {{ $doctor->medical_license_number }}
        </div>
        <div class="patient-info">
            <strong>Patient:</strong> {{ $patient->first_name }} {{ $patient->last_name }}<br>
            Age/Gender: {{ $patient->age }} / {{ $patient->gender }}<br>
            Date: {{ now()->format('d M, Y') }}
        </div>
        <div class="clear"></div>
    </div>

    <div class="prescription-body">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Medicine</th>
                    <th>Dosage</th>
                    <th>Frequency</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($prescriptions as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item->medicine_name }}</strong><br>
                            @if (!empty($item->strength))
                                <small>Strength: {{ $item->strength }}</small><br>
                            @endif
                            @if (!empty($item->route))
                                <small>Route: {{ $item->route }}@if (!empty($item->application_area)) - {{ $item->application_area }}@endif</small><br>
                            @endif
                            @if (!empty($item->sos_instruction))
                                <small>SOS: {{ $item->sos_instruction }}</small><br>
                            @endif
                            <small>{{ $item->instructions }}</small>
                            @if (!empty($item->remarks))
                                <br><small>{{ $item->remarks }}</small>
                            @endif
                        </td>
                        <td>{{ $item->dosage }}</td>
                        <td>
                            {{ $item->frequency }}
                            @if ($item->frequency_times)
                                <br><small>({{ implode(', ', $item->frequency_times) }})</small>
                            @endif
                        </td>
                        <td>{{ $item->start_date?->format('d/m/Y') }} - {{ $item->end_date?->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($appointment->notes)
        <div class="note">
            <strong>Notes:</strong>
            {{ is_array($appointment->notes) ? $appointment->notes['problem'] ?? '' : $appointment->notes }}
        </div>
    @endif

    @if (!empty($appointment->instructions_by_doctor))
        <div class="note">
            <strong>Follow-up advice:</strong>
            @if (is_array($appointment->instructions_by_doctor))
                {{ implode(', ', array_filter($appointment->instructions_by_doctor)) }}
            @else
                {{ $appointment->instructions_by_doctor }}
            @endif
        </div>
    @endif

    <div class="footer">
        <div class="stamps-section"
            style="position: relative; width: 350px; height: 160px; margin-top: 40px; float: right;">
            @php
                $has_sign = isset($signature_url) && $signature_url;
            @endphp

            {{-- Global Stamp (Layer 1) --}}
            @if ($show_global && isset($global_stamp_url) && $global_stamp_url)
                <div style="position: absolute; left: 10px; bottom: 55px; z-index: 1;">
                    <img src="{{ $global_stamp_url }}" style="max-width: 190px; max-height: 125px; opacity: 0.8;"
                        alt="Global Stamp">
                </div>
            @endif

            {{-- Department Stamp (Layer 2, Overlaps Global by half) --}}
            @if ($show_dept && isset($department_stamp_url) && $department_stamp_url)
                <div style="position: absolute; left: {{ ($show_global && isset($global_stamp_url) && $global_stamp_url) ? '70px' : '10px' }}; bottom: 55px; z-index: 2;">
                    <img src="{{ $department_stamp_url }}" style="max-width: 155px; max-height: 125px; opacity: 0.8;"
                        alt="Department Stamp">
                </div>
            @endif

            {{-- Signature, Date and Labels (Layer 3, On top) --}}
            <div style="position: absolute; left: 0; bottom: 25px; width: 300px; text-align: center; z-index: 10; -webkit-transform: rotate(-7deg); transform: rotate(-7deg);">
                <div style="height: 100px; position: relative; margin-bottom: 5px;">
                    @if ($has_sign)
                        <img src="{{ $signature_url }}" style="max-width: 145px; max-height: 80px; margin-bottom: 5px;"
                            alt="Doctor Signature">
                    @else
                        <div
                            style="font-family: 'Alex Brush', cursive !important; font-size: 24px; color: #000080; -webkit-transform: rotate(-3deg); transform: rotate(-3deg); padding-top: 0px; font-weight: normal !important; font-style: normal !important;">
                            {{ $doctor->first_name }} {{ $doctor->last_name }}
                        </div>
                    @endif

                    <div
                        style="font-size: 13px; font-weight: bold; color: #000; position: absolute; right: 80px; top: 40px; -webkit-transform: rotate(5deg); transform: rotate(5deg);">
                        {{ now()->format('d/m/Y') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="note" style="text-align: center; margin-top: 50px; border-top: 1px solid #eee; padding-top: 10px;">
        This is a digitally generated prescription.
    </div>
</body>

</html>
