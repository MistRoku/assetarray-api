<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * CSV import upload. 10 MB cap keeps queue payloads sane; the file is stored
 * first and parsed by ProcessProductCsvImportJob, not in the request.
 */
class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import', Product::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ];
    }
}
