<div class="space-y-4">
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-2">API Testing</h4>
        <p class="text-sm text-gray-600 mb-4">
            Test the video consultation API endpoints. You'll need a valid authentication token to test these endpoints.
        </p>

        <div class="space-y-3">
            <!-- Appointment Info -->
            <div class="bg-white rounded p-3 border">
                <p class="text-xs font-semibold text-gray-500 mb-1">Appointment ID:</p>
                <code class="text-sm text-gray-800 break-all">{{ $appointment->id }}</code>
            </div>

            @if ($videoConsultation)
                <div class="bg-white rounded p-3 border">
                    <p class="text-xs font-semibold text-gray-500 mb-1">Video Consultation ID:</p>
                    <code class="text-sm text-gray-800 break-all">{{ $videoConsultation->id }}</code>
                </div>
            @endif
        </div>
    </div>

    <!-- API Endpoints -->
    <div class="space-y-3">
        <h4 class="font-semibold text-gray-900">Available Endpoints:</h4>

        <!-- Get Video Consultation -->
        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <span
                        class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded mr-2">GET</span>
                    <span
                        class="font-mono text-sm">/api/v2/app/video-consultation/appointment/{{ $appointment->id }}</span>
                </div>
                <button
                    onclick="testApi('GET', '/api/v2/app/video-consultation/appointment/{{ $appointment->id }}', null, 'result-1')"
                    class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                    Test
                </button>
            </div>
            <p class="text-xs text-gray-600 mb-2">Get video consultation details for this appointment</p>
            <div id="result-1" class="mt-2 hidden">
                <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
            </div>
        </div>

        @if ($videoConsultation)
            <!-- Get Join URL -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span
                            class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded mr-2">GET</span>
                        <span
                            class="font-mono text-sm">/api/v2/app/video-consultation/{{ $videoConsultation->id }}/join-url</span>
                    </div>
                    <button
                        onclick="testApi('GET', '/api/v2/app/video-consultation/{{ $videoConsultation->id }}/join-url', null, 'result-2')"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Test
                    </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">Get join URL for the video consultation</p>
                <div id="result-2" class="mt-2 hidden">
                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                </div>
            </div>

            <!-- Start Consultation -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span
                            class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                        <span
                            class="font-mono text-sm">/api/v2/app/video-consultation/{{ $videoConsultation->id }}/start</span>
                    </div>
                    <button
                        onclick="testApi('POST', '/api/v2/app/video-consultation/{{ $videoConsultation->id }}/start', null, 'result-3')"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Test
                    </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">Start the video consultation (Doctor only)</p>
                <div id="result-3" class="mt-2 hidden">
                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                </div>
            </div>

            <!-- End Consultation -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span
                            class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                        <span
                            class="font-mono text-sm">/api/v2/app/video-consultation/{{ $videoConsultation->id }}/end</span>
                    </div>
                    <button
                        onclick="testApi('POST', '/api/v2/app/video-consultation/{{ $videoConsultation->id }}/end', null, 'result-4')"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Test
                    </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">End the video consultation (Doctor only)</p>
                <div id="result-4" class="mt-2 hidden">
                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                </div>
            </div>

            <!-- Regenerate URLs -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span
                            class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                        <span
                            class="font-mono text-sm">/api/v2/app/video-consultation/{{ $videoConsultation->id }}/regenerate</span>
                    </div>
                    <button
                        onclick="testApi('POST', '/api/v2/app/video-consultation/{{ $videoConsultation->id }}/regenerate', null, 'result-5')"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Test
                    </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">Regenerate video consultation URLs</p>
                <div id="result-5" class="mt-2 hidden">
                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                </div>
            </div>
        @else
            <!-- Create Video Consultation -->
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-start justify-between mb-2">
                    <div>
                        <span
                            class="inline-block bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded mr-2">POST</span>
                        <span class="font-mono text-sm">/api/v2/app/video-consultation/create</span>
                    </div>
                    <button
                        onclick="testApi('POST', '/api/v2/app/video-consultation/create', {appointment_id: '{{ $appointment->id }}'}, 'result-6')"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                        Test
                    </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">Create video consultation for this appointment</p>
                <div id="result-6" class="mt-2 hidden">
                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40"></pre>
                </div>
            </div>
        @endif
    </div>

    <!-- Token Input -->
    <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
        <h4 class="font-semibold text-yellow-900 mb-2">Authentication Token</h4>
        <p class="text-sm text-yellow-800 mb-2">
            Enter your authentication token to test the API endpoints:
        </p>
        <input type="text" id="auth-token" placeholder="Enter your Sanctum Bearer token here"
            class="w-full px-3 py-2 border border-yellow-300 rounded text-sm mb-2" />
        <div class="bg-white rounded p-3 border border-yellow-200 mb-2">
            <p class="text-xs font-semibold text-gray-700 mb-1">How to get a token:</p>
            <ol class="list-decimal list-inside text-xs text-gray-600 space-y-1">
                <li>Login via API: <code class="bg-gray-100 px-1 rounded">POST /api/v2/app/login</code></li>
                <li>Use the token from the response</li>
                <li>Or use tinker: <code
                        class="bg-gray-100 px-1 rounded">$user->createToken('test')->plainTextToken</code></li>
            </ol>
        </div>
        <p class="text-xs text-yellow-700">
            Note: You need to be authenticated as the patient or doctor associated with this appointment.
            @if ($appointment->patient && $appointment->patient->user)
                <br>Patient: <strong>{{ $appointment->patient->user->email }}</strong>
            @endif
            @if ($appointment->doctor && $appointment->doctor->user)
                <br>Doctor: <strong>{{ $appointment->doctor->user->email }}</strong>
            @endif
        </p>
    </div>
</div>

<script>
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
