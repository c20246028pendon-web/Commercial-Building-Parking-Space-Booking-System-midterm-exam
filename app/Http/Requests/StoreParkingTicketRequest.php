<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParkingTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['vehicle_plate'] as $field) {
            if ($this->has($field)) {
                $normalized[$field] = mb_strtoupper(trim((string) $this->input($field)));
            }
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'driver_name' => ['required', 'string', 'max:255'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'parking_slot_id' => ['required', 'integer', 'exists:parking_slots,id'],
            'entry_time' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_name.required' => 'Enter the driver name.',
            'vehicle_plate.required' => 'Enter the vehicle license plate.',
            'parking_slot_id.required' => 'Select a parking slot.',
            'parking_slot_id.exists' => 'The selected parking slot does not exist.',
            'entry_time.required' => 'Enter the parking entry time.',
        ];
    }
}
