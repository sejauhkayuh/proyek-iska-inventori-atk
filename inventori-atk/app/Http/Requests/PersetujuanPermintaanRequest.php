<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PersetujuanPermintaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'disetujui_oleh' => ['required', 'string', 'max:120'],
        ];
    }
}
