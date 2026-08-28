<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManageDoctorPatientAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
        ];
    }
}
