<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PortfolioRequest extends FormRequest
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
            'file' => [
                'nullable',
                'file',
                'mimes:pdf,jpeg,jpg,png,doc,docx',
                'max:10240', // 10 МБ
            ],
            'link' => 'nullable|url|max:1000',
            'title' => 'required|string|max:255|min:3',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Ensure that at least one of 'file' or 'link' is present
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! $this->hasFile('file') && empty($this->input('link'))) {
                $validator->errors()->add('file', 'Необходимо прикрепить файл или указать ссылку.');
                $validator->errors()->add('link', 'Необходимо прикрепить файл или указать ссылку.');
            }
        });
    }

    /**
     * Кастомные сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            'file.file' => 'Загруженный файл некорректен',
            'file.mimes' => 'Допустимые форматы файлов: PDF, JPEG, JPG, PNG, DOC, DOCX',
            'file.max' => 'Размер файла не должен превышать 10 МБ',

            'link.url' => 'Ссылка должна быть корректным URL-адресом',
            'link.max' => 'Ссылка не должна превышать 1000 символов',

            'title.required' => 'Название портфолио обязательно',
            'title.string' => 'Название должно быть строкой',
            'title.max' => 'Название не должно превышать 255 символов',
            'title.min' => 'Название должно содержать минимум 3 символа',

            'description.string' => 'Описание должно быть строкой',
            'description.max' => 'Описание не должно превышать 1000 символов',
        ];
    }

    /**
     * Кастомные названия полей для ошибок
     */
    public function attributes(): array
    {
        return [
            'file' => 'файл',
            'link' => 'ссылка',
            'title' => 'название',
            'description' => 'описание',
        ];
    }

    /**
     * Обработка ошибок валидации - возвращает JSON вместо редиректа
     */
    protected function failedValidation(Validator $validator)
    {
        $firstError = $validator->errors()->first();

        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
