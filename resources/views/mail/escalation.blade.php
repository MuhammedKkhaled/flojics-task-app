<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Ticket escalated</title>
    </head>
    <body style="font-family: sans-serif; color: #172033; line-height: 1.5">
        <h1>Ticket #{{ $escalation->ticketId }} was escalated</h1>
        <p><strong>Subject:</strong> {{ $escalation->subject }}</p>
        <p><strong>Priority:</strong> {{ ucfirst($escalation->priority) }}</p>
        <p><strong>Escalated by:</strong> {{ $escalation->actor }}</p>
        <p><strong>Escalated at:</strong> {{ $escalation->escalatedAt->format('Y-m-d H:i:s T') }}</p>

        @if ($escalation->reason)
            <p><strong>Reason:</strong> {{ $escalation->reason }}</p>
        @endif

        <p style="color: #6a7285; font-size: 12px">
            Correlation ID: {{ $escalation->correlationUuid }}
        </p>
    </body>
</html>
