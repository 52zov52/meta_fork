<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recipient_type' => ['required', 'string', 'in:all,students,mentors,individual'],
            'user_id' => ['required_if:recipient_type,individual', 'nullable', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required_without:message', 'nullable', 'string'],
            'message' => ['required_without:description', 'nullable', 'string'],
            'checked' => ['boolean', 'nullable'],
        ];
    }
}
