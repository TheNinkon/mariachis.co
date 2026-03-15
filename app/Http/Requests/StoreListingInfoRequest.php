<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreListingInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:40'],
            'event_date' => ['required', 'date'],
            'event_city' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:4000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizeInlineText($this->input('name')),
            'email' => mb_strtolower(trim((string) $this->input('email', ''))),
            'phone' => $this->sanitizeInlineText($this->input('phone')),
            'event_city' => $this->sanitizeInlineText($this->input('event_city')),
            'message' => $this->sanitizeMessage($this->input('message')),
        ]);
    }

    private function sanitizeInlineText(mixed $value): ?string
    {
        $text = trim(strip_tags((string) $value));

        return $text === '' ? null : preg_replace('/\s+/u', ' ', $text);
    }

    private function sanitizeMessage(mixed $value): ?string
    {
        $text = trim(strip_tags((string) $value));

        return $text === '' ? null : preg_replace('/[ \t]+/u', ' ', preg_replace("/\r\n|\r/u", "\n", $text));
    }
}
