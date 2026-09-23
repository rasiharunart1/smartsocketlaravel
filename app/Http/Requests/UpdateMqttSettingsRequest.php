<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMqttSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'mqtt_tls' => $this->boolean('mqtt_tls'),
        ]);
    }

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
            'mqtt_host' => ['nullable', 'string', 'max:255'],
            'mqtt_port' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'mqtt_tls' => ['required', 'boolean'],
            'mqtt_username' => ['nullable', 'string', 'max:255'],
            'mqtt_password' => ['nullable', 'string', 'max:255'],
            'mqtt_client_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
