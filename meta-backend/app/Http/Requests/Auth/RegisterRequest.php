<?php

namespace App\Http\Requests\Auth;

use App\Support\StudentGroupRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
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
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'numeric', 'in:0,1'],
            'birthday' => ['nullable', 'date'],
            'region_id' => ['required', 'numeric', 'min:1'],
            'planet_id' => ['required', 'numeric', 'min:1'],
            'institution_id' => ['required', 'integer', 'min:1'],
            'institution' => ['nullable', 'string', 'max:255'],
            'course' => ['nullable', 'numeric'],
            'student_group' => StudentGroupRules::rules(),
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'string', 'in:student,mentor,employer'],
            'is_terms_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'last_name.required' => 'Поле фамилия обязательно для заполнения.',
            'last_name.max' => 'Фамилия не должна превышать 50 символов.',
            'first_name.required' => 'Поле имя обязательно для заполнения.',
            'first_name.max' => 'Имя не должно превышать 50 символов.',
            'middle_name.max' => 'Отчество не должно превышать 50 символов.',
            'gender.required' => 'Выберите ваш пол.',
            'gender.numeric' => 'Некорректное значение пола.',
            'gender.in' => 'Некорректное значение пола.',
            'birthday.date' => 'Некорректный формат даты рождения.',
            'region_id.required' => 'Выберите ваш регион.',
            'region_id.numeric' => 'Некорректный ID региона.',
            'region_id.min' => 'Некорректный ID региона.',
            'planet_id.required' => 'Выберите ваше направление.',
            'planet_id.numeric' => 'Некорректный ID направления.',
            'planet_id.min' => 'Некорректный ID направления.',
            'institution_id.required' => 'Выберите тип учреждения.',
            'institution_id.integer' => 'Некорректный ID учреждения.',
            'institution_id.min' => 'Некорректный ID учреждения.',
            'institution.max' => 'Название учреждения не должно превышать 255 символов.',
            'course.numeric' => 'Курс должен быть числом.',
            'student_group.max' => 'Группа не должна превышать 10 символов.',
            'student_group.regex' => StudentGroupRules::invalidMessage(),
            'email.required' => 'Поле email обязательно для заполнения.',
            'email.email' => 'Некорректный формат email.',
            'email.unique' => 'Этот email уже зарегистрирован.',
            'password.required' => 'Поле пароль обязательно для заполнения.',
            'password.confirmed' => 'Пароли не совпадают.',
            'password.min' => 'Пароль должен содержать не менее 8 символов.',
            'role.required' => 'Выберите вашу роль.',
            'role.in' => 'Некорректная роль пользователя.',
            'is_terms_accepted.required' => 'Необходимо принять условия соглашения.',
            'is_terms_accepted.accepted' => 'Необходимо принять условия соглашения.',
        ];
    }
}
