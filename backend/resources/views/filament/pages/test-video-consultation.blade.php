<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Info Section -->
        <div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
            <h3 class="font-semibold text-blue-900 mb-2">Video Consultation API Testing</h3>
            <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
                <li>Select an appointment below to test video consultation API endpoints</li>
                <li>All operations use the API - no direct database access</li>
                <li>Make sure WHEREBY_API_KEY is configured in Settings > Third Party API</li>
                <li>You'll need an authentication token to test the endpoints</li>
            </ul>
        </div>

        <!-- Form Section -->
        <div class="bg-white rounded-lg shadow-sm border p-6">
            {{ $this->form }}
        </div>

        <!-- API Testing Section -->
        <div id="api-testing-section" class="hidden">
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold mb-4">API Testing</h3>

                <!-- Token Input -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Authentication Token
                    </label>
                    <input type="text" id="auth-token" placeholder="Enter your Sanctum Bearer token"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" />
                    <p class="mt-1 text-xs text-gray-500">
                        Get token by logging in via API: <code class="bg-gray-100 px-1 rounded">POST
                            /api/v2/app/login</code>
                    </p>
                </div>

                <!-- API Endpoints -->
                <div class="space-y-4" id="api-endpoints">
                    <!-- Endpoints will be loaded here via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

@push('scripts')
    <script>
        // Watch for appointment selection changes
        document.addEventListener('DOMContentLoaded', () => {
            // Find the appointment select field
            const selectField = document.querySelector('select[name="data.appointment_id"]');
            if (selectField) {
                selectField.addEventListener('change', function() {
                    const appointmentId = this.value;
                    if (appointmentId) {
                        loadApiEndpoints(appointmentId);
                    } else {
                        document.getElementById('api-testing-section').classList.add('hidden');
                    }
                });
            }
        });

        async function loadApiEndpoints(appointmentId) {
            const section = document.getElementById('api-testing-section');
            const endpointsDiv = document.getElementById('api-endpoints');

            section.classList.remove('hidden');

            // Fetch appointment details to check if video consultation exists
            try {
                const response = await fetch(`/api/v2/app/appointments/${appointmentId}`);
                const data = await response.json();

                const hasVideoConsultation = data.data?.video_consultation !== null;
                const videoConsultationId = data.data?.video_consultation?.id;

                let endpointsHtml = '';

                if (hasVideoConsultation && videoConsultationId) {
                    // Show endpoints for existing video consultation
                    endpointsHtml = `
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-900">Video Consultation ID: <code class="text-sm">${videoConsultationId}</code></h4>

                        <!-- Get Video Consultation -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded mr-2">GET</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/appointment/${appointmentId}</span>
                                </div>
                                <button onclick="testApi('GET', '/api/v2/app/video-consultation/appointment/${appointmentId}', null, 'result-1')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">Get video consultation details</p>
                            <div id="result-1" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>

                        <!-- Get Join URL -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded mr-2">GET</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/${videoConsultationId}/join-url</span>
                                </div>
                                <button onclick="testApi('GET', '/api/v2/app/video-consultation/${videoConsultationId}/join-url', null, 'result-2')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">Get join URL for video consultation</p>
                            <div id="result-2" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>

                        <!-- Start Consultation -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/${videoConsultationId}/start</span>
                                </div>
                                <button onclick="testApi('POST', '/api/v2/app/video-consultation/${videoConsultationId}/start', null, 'result-3')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">Start consultation (Doctor only)</p>
                            <div id="result-3" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>

                        <!-- End Consultation -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/${videoConsultationId}/end</span>
                                </div>
                                <button onclick="testApi('POST', '/api/v2/app/video-consultation/${videoConsultationId}/end', null, 'result-4')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">End consultation (Doctor only)</p>
                            <div id="result-4" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>

                        <!-- Regenerate URLs -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/${videoConsultationId}/regenerate</span>
                                </div>
                                <button onclick="testApi('POST', '/api/v2/app/video-consultation/${videoConsultationId}/regenerate', null, 'result-5')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">Regenerate video consultation URLs</p>
                            <div id="result-5" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>
                    </div>
                `;
                } else {
                    // Show create endpoint
                    endpointsHtml = `
                    <div class="space-y-3">
                        <h4 class="font-semibold text-gray-900">Create Video Consultation</h4>

                        <!-- Create Video Consultation -->
                        <div class="border rounded-lg p-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <span class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                                    <span class="font-mono text-sm">/api/v2/app/video-consultation/create</span>
                                </div>
                                <button onclick="testApi('POST', '/api/v2/app/video-consultation/create', {appointment_id: '${appointmentId}'}, 'result-6')"
                                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                    Test
                                </button>
                            </div>
                            <p class="text-xs text-gray-600 mb-2">Create video consultation for this appointment</p>
                            <div id="result-6" class="mt-2 hidden">
                                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                            </div>
                        </div>
                    </div>
                `;
                }

                endpointsDiv.innerHTML = endpointsHtml;
            } catch (error) {
                endpointsDiv.innerHTML =
                    `<div class="text-red-600 text-sm">Error loading appointment details: ${error.message}</div>`;
            }
        }

        async function testApi(method, endpoint, body, resultId) {
            const token = document.getElementById('auth-token').value;
            const resultDiv = document.getElementById(resultId);
            const pre = resultDiv.querySelector('pre');

            if (!token) {
                alert('Please enter an authentication token first');
                return;
            }

            resultDiv.classList.remove('hidden');
            pre.textContent = 'Loading...';

            try {
                const options = {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    }
                };

                // Add token to headers
                if (token.startsWith('Bearer ')) {
                    options.headers['Authorization'] = token;
                } else {
                    options.headers['Authorization'] = 'Bearer ' + token;
                }

                // Add body for POST requests
                if (body && method === 'POST') {
                    options.body = JSON.stringify(body);
                }

                const fullUrl = window.location.origin + endpoint;
                const response = await fetch(fullUrl, options);
                const data = await response.json();

                pre.textContent = JSON.stringify({
                    url: fullUrl,
                    method: method,
                    status: response.status,
                    statusText: response.statusText,
                    data: data
                }, null, 2);

                // Color code the result
                if (response.ok) {
                    pre.classList.add('text-green-700');
                    pre.classList.remove('text-red-700', 'text-gray-700');
                } else {
                    pre.classList.add('text-red-700');
                    pre.classList.remove('text-green-700', 'text-gray-700');
                }
            } catch (error) {
                pre.textContent = JSON.stringify({
                    error: error.message
                }, null, 2);
                pre.classList.add('text-red-700');
                pre.classList.remove('text-green-700', 'text-gray-700');
            }
        }
    </script>
@endpush
