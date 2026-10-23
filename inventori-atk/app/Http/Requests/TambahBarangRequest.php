<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TambahBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => ['required', 'string', 'regex:/^[A-Z]{3}-[0-9]{3}$/', 'unique:barang,kode'],
            'nama' => ['required', 'string', 'max:120'],
            'satuan' => ['required', 'in:pcs,rim,box'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'stok' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
