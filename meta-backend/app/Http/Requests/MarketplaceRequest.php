<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarketplaceRequest extends FormRequest
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
        $nameRule = $this->isMethod('patch') ? 'sometimes' : 'required';
        $scoreRule = $this->isMethod('patch') ? 'sometimes' : 'required';

        return [
            'name' => [$nameRule, 'string', 'max:255'],
            'score' => [$scoreRule, 'integer', 'min:1'],
            'photo' => ['sometimes', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'is_archive' => ['sometimes', 'boolean'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            'name' => [
                'description' => 'Название товара.',
                'example' => 'Новая книга',
            ],
            'score' => [
                'description' => 'Стоимость товара в баллах.',
                'example' => 20,
            ],
            'is_archive' => [
                'description' => 'Архивный товар.',
                'example' => false,
            ],
            'photo' => [
                'description' => 'Фото товара (jpg, jpeg или png, максимум 2 МБ).',
            ],
        ];
    }
}
