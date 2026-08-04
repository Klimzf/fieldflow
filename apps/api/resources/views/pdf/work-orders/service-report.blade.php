<!doctype html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <title>Service Report #{{ $workOrder->id }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.45;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 8px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 16px;
            margin-top: 24px;
            padding-bottom: 6px;
            border-bottom: 1px solid #d1d5db;
        }

        p {
            margin: 0 0 6px;
        }

        .muted {
            color: #6b7280;
        }

        .header {
            margin-bottom: 24px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .grid th,
        .grid td {
            text-align: left;
            vertical-align: top;
            padding: 8px;
            border: 1px solid #d1d5db;
        }

        .grid th {
            width: 32%;
            background: #f3f4f6;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            background: #e5e7eb;
            font-size: 11px;
        }

        .section-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .footer {
            margin-top: 32px;
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Service Report</h1>
        <p class="muted">Work Order #{{ $workOrder->id }}</p>
        <p class="muted">Generated at: {{ $generatedAt->format('Y-m-d H:i') }}</p>
    </div>

    <h2>Work Order</h2>

    <table class="grid">
        <tr>
            <th>Title</th>
            <td>{{ $workOrder->title }}</td>
        </tr>
        <tr>
            <th>Description</th>
            <td>{{ $workOrder->description ?: '—' }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td><span class="badge">{{ $workOrder->status }}</span></td>
        </tr>
        <tr>
            <th>Priority</th>
            <td><span class="badge">{{ $workOrder->priority }}</span></td>
        </tr>
        <tr>
            <th>Scheduled at</th>
            <td>{{ $workOrder->scheduled_at?->format('Y-m-d H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <th>Completed at</th>
            <td>{{ $workOrder->completed_at?->format('Y-m-d H:i') ?? '—' }}</td>
        </tr>
    </table>

    <h2>Client and Site</h2>

    <table class="grid">
        <tr>
            <th>Client</th>
            <td>{{ $workOrder->client?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Site</th>
            <td>{{ $workOrder->site?->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Site address</th>
            <td>{{ $workOrder->site?->address ?? '—' }}</td>
        </tr>
        <tr>
            <th>Equipment</th>
            <td>{{ $workOrder->equipment?->name ?? '—' }}</td>
        </tr>
    </table>

    <h2>Assigned Users</h2>

    @forelse ($workOrder->assignments as $assignment)
        <div class="section-item">
            <p>{{ $assignment->user?->name ?? 'Unknown user' }}</p>
            <p class="muted">{{ $assignment->user?->email ?? '—' }}</p>
        </div>
    @empty
        <p class="muted">No assigned users.</p>
    @endforelse

    <h2>Checklist</h2>

    @forelse ($workOrder->checklistItems as $item)
        <div class="section-item">
            <p>
                {{ $item->is_completed ? '[x]' : '[ ]' }}
                {{ $item->title }}
            </p>

            @if ($item->is_completed)
                <p class="muted">
                    Completed by:
                    {{ $item->completedBy?->name ?? 'Unknown user' }}
                    @if ($item->completed_at)
                        at {{ $item->completed_at->format('Y-m-d H:i') }}
                    @endif
                </p>
            @endif
        </div>
    @empty
        <p class="muted">No checklist items.</p>
    @endforelse

    <h2>Files</h2>

    @forelse ($workOrder->files as $file)
        <div class="section-item">
            <p>{{ $file->original_name }}</p>
            <p class="muted">
                {{ $file->mime_type ?? 'unknown' }},
                {{ number_format($file->size / 1024, 1) }} KB,
                uploaded by {{ $file->uploadedBy?->name ?? 'Unknown user' }}
            </p>
        </div>
    @empty
        <p class="muted">No files attached.</p>
    @endforelse

    <h2>Timeline</h2>

    @forelse ($workOrder->updates as $update)
        <div class="section-item">
            <p>
                <strong>{{ $update->type }}</strong>
                — {{ $update->created_at?->format('Y-m-d H:i') }}
            </p>

            @if ($update->message)
                <p>{{ $update->message }}</p>
            @endif

            @if ($update->old_status || $update->new_status)
                <p class="muted">
                    Status:
                    {{ $update->old_status ?? '—' }}
                    →
                    {{ $update->new_status ?? '—' }}
                </p>
            @endif

            <p class="muted">
                User: {{ $update->user?->name ?? 'System' }}
            </p>
        </div>
    @empty
        <p class="muted">No timeline updates.</p>
    @endforelse

    <div class="footer">
        <p>Generated by FieldFlow.</p>
    </div>
</body>

</html>
