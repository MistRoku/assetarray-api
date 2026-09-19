<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background CSV import for the product catalogue.
 *
 * Dispatched by ProductService::importCsv() with the stored file path.
 * $userId tracks who uploaded the file for progress/error reporting.
 * Parsing happens here (not in the request) so large files can't time out.
 */
class ProcessProductCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $path,
        public readonly ?int $userId = null,
    ) {}

    public function handle(): void
    {
        // TODO: implement CSV import processing.
    }
}
