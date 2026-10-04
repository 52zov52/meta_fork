<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\StudentGroupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class MentorStoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            $this->personalRules(),
            $this->institutionRules(),
            $this->accountRules(),
            [
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'is_terms_accepted' => ['nullable', 'boolean'],
            ]
        );
    }

    private function personalRules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'integer', Rule::in([0, 1])],
            'birthday' => ['nullable', Rule::date()->format('Y-m-d')],
        ];
    }

    private function institutionRules(): array
    {
        return [
            'course' => ['nullable', 'integer'],
            'student_group' => StudentGroupRules::rules(),
        ];
    }

    public function messages(): array
    {
        return [
            'student_group.regex' => StudentGroupRules::invalidMessage(),
        ];
    }

    private function accountRules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'file' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }
}
