<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h3 class="text-sm font-semibold text-slate-900">Webhook Endpoint</h3>
            <p class="mt-1 text-sm text-slate-700">{{ $this->getWebhookUrl() }}</p>
            <p class="mt-2 text-xs text-slate-600">Configure this exact callback URL in Meta App Dashboard for WhatsApp Cloud API.</p>
        </div>

        <div class="rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">Connection Status</h3>
                <span @class([
                    'rounded-full px-2.5 py-1 text-xs font-semibold',
                    'bg-emerald-100 text-emerald-700' => $this->isConfigured(),
                    'bg-rose-100 text-rose-700' => ! $this->isConfigured(),
                ])>
                    {{ $this->isConfigured() ? 'Configured' : 'Missing credentials' }}
                </span>
            </div>
            <p class="mt-2 text-xs text-slate-600">Required: WHATSAPP_ACCESS_TOKEN, WHATSAPP_PHONE_NUMBER_ID. Optional: WHATSAPP_VERIFY_TOKEN.</p>
        </div>

        <div class="rounded-lg border bg-white p-5 space-y-4">
            {{ $this->form }}

            <div class="flex gap-3">
                <x-filament::button wire:click="sendTest" color="success">
                    Send Test Message
                </x-filament::button>
                <x-filament::button wire:click="refreshLogs" color="gray">
                    Refresh Logs
                </x-filament::button>
            </div>
        </div>

        <div class="rounded-lg border bg-white p-5">
            <h3 class="text-sm font-semibold text-slate-900">Recent WhatsApp Logs</h3>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-slate-600">
                            <th class="py-2 pr-4">Time</th>
                            <th class="py-2 pr-4">Channel</th>
                            <th class="py-2 pr-4">To</th>
                            <th class="py-2 pr-4">Type</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 pr-4">Meta ID</th>
                            <th class="py-2">Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr class="border-b border-slate-100 text-slate-700">
                                <td class="py-2 pr-4">{{ $log['created_at'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $log['channel'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $log['to'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $log['message_type'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $log['status'] ?? '-' }}</td>
                                <td class="py-2 pr-4">{{ $log['wa_message_id'] ?? '-' }}</td>
                                <td class="py-2 text-rose-700">{{ $log['error_message'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-3 text-slate-500">No logs yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
