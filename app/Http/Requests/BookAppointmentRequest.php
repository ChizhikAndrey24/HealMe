<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BookAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $symptoms = trim((string) $this->input('symptoms', ''));

        if ($symptoms !== '' && ! $this->filled('chief_complaint')) {
            $this->merge([
                'chief_complaint' => mb_substr($symptoms, 0, 120),
            ]);
        }

        if ($symptoms !== '' && ! $this->filled('clinical_summary')) {
            $this->merge([
                'clinical_summary' => $symptoms,
            ]);
        }

        if (! $this->filled('urgency_level')) {
            $this->merge([
                'urgency_level' => 'Medium',
            ]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'doctor_availability_slot_id' => ['required', 'integer', 'exists:doctor_availability_slots,id'],
            'symptoms' => ['required', 'string', 'min:10', 'max:5000'],
            'chief_complaint' => ['required', 'string', 'max:255'],
            'clinical_summary' => ['required', 'string', 'max:5000'],
            'urgency_level' => ['required', 'string', Rule::in(['Low', 'Medium', 'High', 'Emergency'])],
        ];
    }
}
