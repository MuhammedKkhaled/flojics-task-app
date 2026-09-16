<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use App\Notifications\ChannelManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EscalateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return $ticket instanceof Ticket
            && $this->user()?->can('escalate', $ticket) === true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'channels' => ['sometimes', 'array', 'min:1'],
            'channels.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(app(ChannelManager::class)->available()),
            ],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return list<string> */
    public function channels(): array
    {
        /** @var list<string> $channels */
        $channels = $this->validated('channels', config('notifications.default_channels', []));

        return $channels;
    }

    public function reason(): ?string
    {
        $reason = $this->validated('reason');

        return is_string($reason) ? $reason : null;
    }
}
