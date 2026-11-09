<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParkingTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('vehicle_plate')) {
            $this->merge([
                'vehicle_plate' => mb_strtoupper(trim((string) $this->input('vehicle_plate'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'driver_name' => ['sometimes', 'required', 'string', 'max:255', 'required_with:vehicle_plate'],
            'vehicle_plate' => ['sometimes', 'required', 'string', 'max:20', 'required_with:driver_name'],
            'parking_slot_id' => ['sometimes', 'required', 'integer', 'exists:parking_slots,id'],
            'entry_time' => ['sometimes', 'required', 'date'],
            'exit_time' => ['sometimes', 'nullable', 'date'],
            'payment_status' => ['sometimes', 'required', 'in:Pending,Paid'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_name.required_with' => 'Provide both the driver name and vehicle plate when changing driver details.',
            'vehicle_plate.required_with' => 'Provide both the driver name and vehicle plate when changing driver details.',
            'parking_slot_id.exists' => 'The selected parking slot does not exist.',
            'exit_time.date' => 'The exit time must be a valid date and time.',
            'payment_status.in' => 'Payment status must be Pending or Paid.',
        ];
    }
}
