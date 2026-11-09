<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('slot_code')) {
            $this->merge(['slot_code' => mb_strtoupper(trim((string) $this->input('slot_code')))]);
        }
    }

    public function rules(): array
    {
        return [
            'parking_zone_id' => ['required', 'integer', 'exists:parking_zones,id'],
            'slot_code' => ['required', 'string', 'max:20'],
            'status' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return [
            'parking_zone_id.required' => 'Select a parking zone for this slot.',
            'parking_zone_id.exists' => 'The selected parking zone does not exist.',
            'slot_code.required' => 'Enter a slot code.',
            'slot_code.max' => 'The slot code may not exceed 20 characters.',
            'status.prohibited' => 'Slot status is managed automatically by parking tickets.',
        ];
    }
}
