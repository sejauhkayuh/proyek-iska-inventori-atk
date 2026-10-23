<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjukanPermintaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pemohon' => ['required', 'string', 'max:120'],
            'bagian' => ['required', 'string', 'max:80'],
            'kode_barang' => ['required', 'string', 'regex:/^[A-Z]{3}-[0-9]{3}$/'],
            'jumlah' => ['required', 'integer', 'min:1'],
        ];
    }
}
