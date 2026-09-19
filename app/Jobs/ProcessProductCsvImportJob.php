<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Throwable;

/**
 * Import products from an uploaded CSV in the background.
 *
 * Dispatched by ProductService::importCsv() with the stored path. Design
 * notes:
 * - Streams the file (never loads it whole) so large imports don't OOM.
 * - Never touches global auth state: queue workers are long-lived and an
 *   Auth::setUser() here would leak into the next job on the same worker.
 *   The uploader id is kept purely for log correlation.
 * - Row failures are logged and skipped; one bad row never kills the batch.
 * - The source file is deleted only on success — on final failure it stays
 *   on disk for inspection (see failed()).
 *
 * Expected columns: name (required), category, description, cost_price,
 * selling_price, min_stock_threshold, barcode.
 */
class ProcessProductCsvImportJob implements ShouldQueue
{
    use Queueable;

    /** Retry transient failures (DB blips, locked sqlite) a few times. */
    public int $tries = 3;

    public function __construct(
        public string $filePath,
        public ?int $userId = null
    ) {}

    public function handle(): void
    {
        $stream = Storage::disk('local')->readStream($this->filePath);

        if ($stream === null) {
            Log::error('Product CSV import aborted: file not found.', [
                'path' => $this->filePath,
                'user_id' => $this->userId,
            ]);

            return;
        }

        try {
            $csv = Reader::createFromStream($stream);
            $csv->setHeaderOffset(0);

            if (! in_array('name', $csv->getHeader(), true)) {
                Log::error('Product CSV import aborted: missing required "name" column.', [
                    'path' => $this->filePath,
                    'user_id' => $this->userId,
                ]);

                return;
            }

            foreach ($csv->getRecords() as $offset => $record) {
                $this->importRow(is_array($record) ? $record : [], $offset);
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        Storage::disk('local')->delete($this->filePath);
    }

    /**
     * Called when all retries are exhausted. The source file is deliberately
     * left on disk for debugging — clean it up manually after investigating.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Product CSV import failed permanently.', [
            'path' => $this->filePath,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Import one CSV row. Blank-name rows are skipped quietly (usually a
     * trailing newline); anything else failing is logged with its row data.
     *
     * @param  array<string, mixed>  $record
     */
    private function importRow(array $record, int|string $offset): void
    {
        $name = trim((string) ($record['name'] ?? ''));

        if ($name === '') {
            return;
        }

        try {
            $categoryName = trim((string) ($record['category'] ?? '')) ?: 'Uncategorised';

            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                [
                    'slug' => Str::slug($categoryName),
                    'is_active' => true,
                ]
            );

            Product::create([
                'name' => $name,
                'description' => $record['description'] ?? null,
                'category_id' => $category->id,
                'cost_price' => $record['cost_price'] ?? 0,
                'selling_price' => $record['selling_price'] ?? 0,
                'min_stock_threshold' => $record['min_stock_threshold'] ?? 5,
                'barcode' => $record['barcode'] ?? null,
                'is_active' => true,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Product CSV row failed to import.', [
                'row' => $offset,
                'record' => $record,
                'user_id' => $this->userId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
