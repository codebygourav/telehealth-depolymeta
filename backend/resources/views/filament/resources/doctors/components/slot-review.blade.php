<div class="overflow-auto">
    <table class="filament-table w-full" style="width:100%;border-collapse:collapse">
        @foreach($rows as $row)
            <tr style="border-bottom:1px solid #e5e7eb">
                <td style="padding:8px;font-weight:600;width:30%;vertical-align:top">{{ $row['label'] }}</td>
                <td style="padding:8px;vertical-align:top">{{ $row['value'] }}</td>
            </tr>
        @endforeach
    </table>
</div>
