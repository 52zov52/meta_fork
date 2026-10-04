<?php

namespace App\Http\Requests;

use App\Enums\ActivityStatus;
use App\Models\EducationLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
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
        $isDynamic = (bool) $this->input('is_dynamic_score', false);
        $requiresActivityType = $isDynamic && in_array((int) $this->input('category_id'), [1, 2, 4], true);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'is_dynamic_score' => ['required', 'boolean'],
            'score' => [Rule::requiredIf(! $isDynamic), 'integer', 'min:1', 'nullable'],
            'parameters' => [Rule::requiredIf($isDynamic), 'array', 'nullable'],
            'parameters.activity_type' => [Rule::requiredIf($requiresActivityType), 'string', 'nullable'],
            'parameters.has_places' => ['sometimes', 'boolean'],
            'parameters.has_implementation' => ['sometimes', 'boolean', 'nullable'],
            'parameters.is_leader' => ['sometimes', 'boolean'],
            'parameters.target_education_level_ids' => ['nullable', 'array', 'max:2', $this->adjacentTargetsRule()],
            'parameters.target_education_level_ids.*' => ['integer', 'exists:education_levels,id'],
            'status' => ['required', 'string', Rule::in(ActivityStatus::values())],
            'planet_id' => ['required', 'integer', 'exists:planets,id'],
            'academy_id' => ['nullable', 'integer', 'exists:academies,id'],
            'category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'user_id' => ['required', 'integer', 'exists:users,id'], // creator_id
            'file' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:2048'],
        ];
    }

    /**
     * Кастомное правило: target_education_level_ids должны быть NUO-соседними (разница по NUO ровно 1).
     */
    private function adjacentTargetsRule(): ValidationRule
    {
        return new class implements ValidationRule {
            public function validate(string $attribute, mixed $value, \Closure $fail): void
            {
                if (! is_array($value) || count($value) < 2) {
                    return;
                }
                $ids = array_values(array_unique(array_map('intval', $value)));
                if (count($ids) !== 2) {
                    $fail('Выберите не более 2 уровней обучения.');
                    return;
                }
                $nuos = EducationLevel::whereIn('id', $ids)->pluck('nuo_value')->map(fn ($v) => (int) $v)->all();
                if (count($nuos) !== 2 || abs($nuos[0] - $nuos[1]) !== 1) {
                    $fail('Можно выбрать только соседние уровни обучения (например, Колледж + Бакалавриат или Бакалавриат + Магистратура).');
                }
            }
        };
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Название мероприятия обязательно для заполнения.',
            'title.max' => 'Название мероприятия не должно превышать 255 символов.',
            'description.required' => 'Описание мероприятия обязательно для заполнения.',
            'description.max' => 'Описание не должно превышать 5000 символов.',
            'date_start.required' => 'Дата начала обязательна для заполнения.',
            'date_start.date_format' => 'Дата начала должна быть в формате Y-m-d (например: 2025-01-21).',
            'date_end.required' => 'Дата окончания обязательна для заполнения.',
            'date_end.date_format' => 'Дата окончания должна быть в формате Y-m-d.',
            'date_end.after_or_equal' => 'Дата окончания должна быть не раньше даты начала.',
            'score.required' => 'Количество баллов обязательно для заполнения.',
            'score.integer' => 'Количество баллов должно быть целым числом.',
            'score.min' => 'Количество баллов должно быть не меньше 1.',
            'status.required' => 'Статус мероприятия обязателен для заполнения.',
            'status.in' => 'Недопустимый статус. Допустимые значения: draft, scheduled, started, completed, canceled.',
            'planet_id.required' => 'ID планеты обязателен для заполнения.',
            'planet_id.integer' => 'ID планеты должен быть целым числом.',
            'planet_id.exists' => 'Выбранная планета не существует.',
            'academy_id.integer' => 'ID академии должен быть целым числом.',
            'academy_id.exists' => 'Выбранная академия не существует.',
            'category_id.required' => 'ID категории обязателен для заполнения.',
            'category_id.integer' => 'ID категории должен быть целым числом.',
            'category_id.exists' => 'Выбранная категория не существует.',
            'user_id.required' => 'ID создателя обязателен для заполнения.',
            'user_id.integer' => 'ID создателя должен быть целым числом.',
            'user_id.exists' => 'Пользователь с таким ID не существует.',
            'file.file' => 'Загруженный файл должен быть изображением.',
            'file.mimes' => 'Изображение должно быть в формате: jpeg, jpg или png.',
            'file.max' => 'Размер изображения не должен превышать 2MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'название',
            'description' => 'описание',
            'date_start' => 'дата начала',
            'date_end' => 'дата окончания',
            'score' => 'количество баллов',
            'status' => 'статус',
            'planet_id' => 'планета',
            'academy_id' => 'академия',
            'category_id' => 'категория',
            'user_id' => 'создатель',
            'file' => 'изображение',
        ];
    }
}
