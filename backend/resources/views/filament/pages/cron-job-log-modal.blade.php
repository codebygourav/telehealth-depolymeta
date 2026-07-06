<div class="space-y-4">
    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
        <div><strong>Command:</strong> <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace;">php artisan {{ $record->command }}</code></div>
        <div><strong>Last Run:</strong> {{ $record->last_run_at ? $record->last_run_at->format('Y-m-d H:i:s') : 'Never' }}</div>
    </div>
    
    <div>
        <label style="display: block; font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px;">Execution Output Log</label>
        <pre style="width: 100%; padding: 16px; border-radius: 8px; bg-color: #0f172a; background: #0f172a; color: #f8fafc; font-family: monospace; font-size: 12px; line-height: 1.5; overflow-x: auto; whitespace: pre-wrap; white-space: pre-wrap; max-height: 380px; border: 1px solid #1e293b;">{{ $record->last_run_output ?: 'No run output recorded yet.' }}</pre>
    </div>
</div>
