<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\StudentGroupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'store' => array_merge(
                $this->personalRules(),
                $this->institutionRules(),
                $this->accountRules(),
                [
                    'role' => ['required', Rule::in(['student', 'mentor', 'moderator', 'admin'])],
                    'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'is_terms_accepted' => ['nullable', 'boolean'],
                ]
            ),
            'updatePersonalData' => $this->personalRules(),
            'updateInstitutionData' => $this->institutionRules(),
            'updateAccountData' => $this->accountRules((int) $this->route('user_id')),
            'updatePassword' => [
                'old_password' => ['required'],
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
            ],
            default => [],
        };
    }

    private function personalRules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'integer', Rule::in([0, 1])],
            'birthday' => ['nullable', Rule::date()->format('Y-m-d')],
            'region_id' => ['required', 'integer', Rule::exists('regions', 'id')],
        ];
    }

    private function institutionRules(): array
    {
        return [
            'planet_id' => ['required', 'integer', Rule::exists('planets', 'id')],
            'institution_id' => ['required', 'integer', Rule::exists('institutions', 'id')],
            'institution' => ['nullable', 'required_if:institution_id,4', 'string', 'max:255'],
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

    private function accountRules(?int $ignoreUserId = null): array
    {
        $emailRule = Rule::unique(User::class);
        if ($ignoreUserId) {
            $emailRule = $emailRule->ignore($ignoreUserId);
        }

        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', $emailRule],
            'file' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }
}
