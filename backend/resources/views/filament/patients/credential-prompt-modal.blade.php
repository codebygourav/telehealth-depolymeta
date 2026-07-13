@if ($show)
    <div
        x-data="{ open: @js($show) }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        style="z-index: 9999;"
        x-init="if (@js($show)) { setTimeout(() => open = true, 150) }"
    >
        <div class="fixed inset-0 bg-black/50" @click="$wire.closeCredentialPrompt()"></div>

        <div class="relative w-full max-w-md mx-4 overflow-hidden bg-white rounded-xl shadow-xl">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center flex-shrink-0 w-10 h-10 rounded-xl bg-green-100">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Send Login Credentials?</h3>
                </div>
            </div>

            <div class="px-6 py-4">
                <p class="text-gray-600">
                    @if ($context === 'create')
                        Patient created successfully.
                        <br><br>
                        Send login credentials to <strong>{{ $email }}</strong>?
                    @else
                        Password updated successfully.
                        <br><br>
                        Send login credentials to <strong>{{ $email }}</strong>?
                    @endif
                </p>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 bg-gray-50">
                <button
                    type="button"
                    wire:click="closeCredentialPrompt"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                >
                    No
                </button>
                <button
                    type="button"
                    wire:click="sendPendingCredentials"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="sendPendingCredentials">Yes, Send Email</span>
                    <span wire:loading wire:target="sendPendingCredentials">Sending...</span>
                </button>
            </div>
        </div>
    </div>
@endif
