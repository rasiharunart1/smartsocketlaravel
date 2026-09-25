<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeviceSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'max_voltage' => ['required', 'numeric', 'min:0', 'max:300'],
            'max_current' => ['required', 'numeric', 'min:0', 'max:30'],
            'max_temperature' => ['required', 'numeric', 'min:0', 'max:120'],
            'max_smoke_ppm' => ['required', 'numeric', 'min:0', 'max:5000'],
            'kwh_rate' => ['required', 'numeric', 'min:0', 'max:100000'],
            'log_interval' => ['nullable', 'integer', 'min:3', 'max:3600'],
        ];
    }
}
