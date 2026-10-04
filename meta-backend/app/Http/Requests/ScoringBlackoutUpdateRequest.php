<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScoringBlackoutUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'winter_start' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/'],
            'winter_end' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/'],
            'summer_start' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/'],
            'summer_end' => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\-(0[1-9]|[12][0-9]|3[01])$/'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['winter_start', 'winter_end', 'summer_start', 'summer_end'] as $key) {
                $value = $this->input($key);
                if (! is_string($value) || ! preg_match('/^\d{2}\-\d{2}$/', $value)) {
                    continue;
                }

                [$month, $day] = array_map('intval', explode('-', $value));
                if (! checkdate($month, $day, 2000)) {
                    $validator->errors()->add($key, 'Дата должна быть валидной в формате MM-DD.');
                }
            }
        });
    }
}
