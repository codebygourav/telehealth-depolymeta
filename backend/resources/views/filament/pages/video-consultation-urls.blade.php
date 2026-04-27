<div class="space-y-4">
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-3">Video Consultation Details</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-600 font-medium">Room ID:</span>
                <p class="text-gray-900 font-mono text-xs break-all">{{ $videoConsultation->room_id ?? 'N/A' }}</p>
            </div>
            <div>
                <span class="text-gray-600 font-medium">Status:</span>
                <p class="text-gray-900 font-bold">{{ ucfirst($videoConsultation->status ?? 'N/A') }}</p>
            </div>
        </div>
    </div>

    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h5 class="font-medium text-green-900 mb-3">Doctor (Host) URL:</h5>
        <div class="flex items-center gap-2">
            <input type="text" value="{{ $videoConsultation->host_url ?? 'N/A' }}" readonly
                class="flex-1 px-3 py-2 bg-white border border-green-300 rounded text-sm font-mono"
                id="host-url-input" />
            <button onclick="copyToClipboard('host-url-input')"
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">
                Copy
            </button>
        </div>
        <a href="{{ $videoConsultation->host_url ?? '#' }}" target="_blank"
            class="mt-2 inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">
            Open Host Room
        </a>
    </div>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h5 class="font-medium text-blue-900 mb-3">Patient (Participant) URL:</h5>
        <div class="flex items-center gap-2">
            <input type="text" value="{{ $videoConsultation->participate_url ?? 'N/A' }}" readonly
                class="flex-1 px-3 py-2 bg-white border border-blue-300 rounded text-sm font-mono"
                id="participate-url-input" />
            <button onclick="copyToClipboard('participate-url-input')"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
                Copy
            </button>
        </div>
        <a href="{{ $videoConsultation->participate_url ?? '#' }}" target="_blank"
            class="mt-2 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium">
            Open Participant Room
        </a>
    </div>

    @if ($videoConsultation->room_url)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h5 class="font-medium text-gray-900 mb-3">General Room URL:</h5>
            <div class="flex items-center gap-2">
                <input type="text" value="{{ $videoConsultation->room_url }}" readonly
                    class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded text-sm font-mono"
                    id="room-url-input" />
                <button onclick="copyToClipboard('room-url-input')"
                    class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm font-medium">
                    Copy
                </button>
            </div>
        </div>
    @endif

    @if ($videoConsultation->metadata)
        <details class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <summary class="cursor-pointer font-medium text-gray-700 hover:text-gray-900">
                View Metadata
            </summary>
            <div class="mt-2 bg-white rounded p-3">
                <pre class="text-xs text-gray-600 overflow-x-auto">{{ json_encode($videoConsultation->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </details>
    @endif
</div>

<script>
    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        input.select();
        input.setSelectionRange(0, 99999); // For mobile devices
        document.execCommand('copy');

        // Show feedback
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Copied!';
        button.classList.add('bg-green-600');

        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('bg-green-600');
        }, 2000);
    }
</script>
