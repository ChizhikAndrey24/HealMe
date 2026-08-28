<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MatchDoctorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'symptoms' => ['required', 'string', 'min:10', 'max:5000'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5'],
        ];
    }
}
