<?php

namespace App\Services\VendorCatalog;

use App\Enums\Currency;
use App\Enums\VendorCatalog\VendorCatalogHttpAuthenticationType;
use App\Enums\VendorCatalog\VendorCatalogImportDefinitionProtocolType;
use App\Enums\VendorCatalog\VendorCatalogImportRecordState;
use App\Enums\VendorCatalog\VendorCatalogImportState;
use App\Exceptions\VendorCatalog\VendorCatalogNoRecordsToSynchronize;
use App\Imports\VendorCatalog\VendorCatalogGenericCsvImporter;
use App\Imports\VendorCatalog\VendorCatalogGenericJsonImporter;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogEntry;
use App\Models\VendorCatalog\VendorCatalogImport;
use App\Models\VendorCatalog\VendorCatalogImportRecord;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use ZipArchive;

class VendorCatalogFileImportService
{
    public function importEntries(VendorCatalogImport $import): void
    {
        $query = self::getRecordsToImport($import);

        if ($query->count() === 0) {
            throw new VendorCatalogNoRecordsToSynchronize;
        }

        // import records
        $ids = collect();
        $query->chunk(1000, function ($records) use ($import, $ids) {
            foreach ($records as $record) {
                $this->importEntry($import, $record);
                $ids->push($record->id);
            }
        });

        // update VendorCatalogImportRecord state
        $ids->chunk(1000)->each(function ($ids) {
            VendorCatalogImportRecord::whereIn('id', $ids)
                ->update(['state' => VendorCatalogImportRecordState::PROCESSED]);
        });
    }

    protected function getRecordsToImport(VendorCatalogImport $import): HasMany
    {
        return $import->records()
            ->where('state', VendorCatalogImportRecordState::NEW);
    }

    private function importEntry(VendorCatalogImport $import, VendorCatalogImportRecord $record): VendorCatalogEntry
    {
        $attributes = [
            'vendor_catalog_vendor_id' => $import->importDefinition->vendor_catalog_vendor_id,
            'vendor_catalog_import_record_id' => $record->id,
            'data' => $record->data,
            'currency' => Currency::EURO,
            ...$this->mapData($import->importDefinition, $record),
        ];

        return VendorCatalogEntry::create($attributes);
    }

    protected function mapData(VendorCatalogImportDefinition $definition, VendorCatalogImportRecord $record): array
    {
        switch ($definition->source->value) {
            case 'json':
                return $this->mapJsonFields($definition, $record);

            default:
                $attributes = [];

                $attributes['gtin'] = $record->data[26];
                $attributes['number'] = $record->data[3];
                $attributes['name'] = $record->data[13];
                // $attributes['stock'] = $record->data[];
                $attributes['price'] = $record->data[4];

                return $attributes;
        }
    }

    protected function mapJsonFields(VendorCatalogImportDefinition $definition, VendorCatalogImportRecord $record): array
    {
        $attributes = [];

        $attributes['gtin'] = null;
        $attributes['number'] = null;
        $attributes['name'] = null;
        $attributes['stock'] = null;
        $attributes['price'] = null;

        foreach ($definition->mappings as $mapping) {
            $attributes[$mapping['to']] = $record->data[$mapping['from']];
        }

        return $attributes;
    }

    public function truncateRecords(VendorCatalogImport $import): VendorCatalogImport
    {
        $import->records()->delete();

        return tap($import)->refresh();
    }

    public function importRecords(VendorCatalogImport $import): ?Collection
    {
        switch ($import->importDefinition->source->value) {
            case 'json':
                (new VendorCatalogGenericJsonImporter($import))->import();

                return null;

            default:
                $importer = new VendorCatalogGenericCsvImporter($import);
                Excel::import(
                    import: $importer,
                    filePath: $import->path,
                    disk: $import->disk,
                    readerType: \Maatwebsite\Excel\Excel::CSV
                );

                $import->refresh();

                return $import->records;
        }
    }

    public function readHeadings(VendorCatalogImport $import): void
    {

        $headings = (new HeadingRowImport)->toArray(filePath: $import->path, disk: $import->disk, readerType: \Maatwebsite\Excel\Excel::CSV);
        $importDefinition = $import->importDefinition()->first();
        $columns = $headings[0][0];
        $data = [];
        foreach ($columns as $index => $value) {
            $data[] = ['field' => $index, 'name' => $importDefinition->file['header_row'] > 0 ? $value : ($index + 1).'. column'];
        }
        $importDefinition->columns = $data;
        $importDefinition->save();
    }

    /**
     * @throws \Exception
     */
    public function importFile(VendorCatalogImportDefinition $definition, ?string $filePath = null): VendorCatalogImport
    {
        // @todo replace with handler class, get handler from definition. 1 liner
        $import = match ($definition->protocol) {
            VendorCatalogImportDefinitionProtocolType::FTP => $this->handleFtp($definition),
            VendorCatalogImportDefinitionProtocolType::HTTP => $this->handleHttp($definition),
            VendorCatalogImportDefinitionProtocolType::LOCAL => $this->handleLocal($definition),
            VendorCatalogImportDefinitionProtocolType::UPLOAD => $this->handleUpload($definition, $filePath),
            default => throw new \Exception('unknown protocol')
        };

        if ($definition->compression && $definition->compression['active']) {
            $import = $this->extractZip($import);
        }

        return $import;
    }

    /**
     * @throws \Exception
     */
    private function handleFtp(VendorCatalogImportDefinition $definition): VendorCatalogImport
    {
        $ftp = $definition->configuration['ftp'];

        $disk = Storage::build([
            'driver' => 'ftp',

            'host' => $ftp['host'],
            'username' => $ftp['username'],
            'password' => $ftp['password'],

            // Optional FTP Settings...
            // 'port' => env('FTP_PORT', 21),
            // 'root' => env('FTP_ROOT'),
            // 'passive' => true,
            // 'ssl' => true,
            // 'timeout' => 30,
        ]);

        $content = $disk->get($ftp['path']);

        return $this->storeImport($definition, $ftp['path'], $content);
    }

    private function handleHttp(VendorCatalogImportDefinition $definition): VendorCatalogImport
    {
        $config = $definition->configuration['http'];

        $response = match ($definition->configuration['http']['type']) {
            VendorCatalogHttpAuthenticationType::BASIC_AUTH => Http::withBasicAuth($config['username'], $config['password'])->get($config['url']),
            VendorCatalogHttpAuthenticationType::DIGEST_AUTH => Http::withDigestAuth($config['username'], $config['password'])->get($config['url']),
            VendorCatalogHttpAuthenticationType::AUTH_HEADER => Http::withHeaders([])->get($config['url']),
            default => Http::get($config['url']),
        };

        if ($response->failed()) {
            $response->throw();
        }

        $content = $response->body();

        $stats = $response->handlerStats();

        return $this->storeImport($definition, $stats['url'], $content, $stats['content_type']);
    }

    public function getFileUploadPath(VendorCatalogImportDefinition $definition, ?string $file = null): string
    {
        if ($file === null) {
            $file = $definition->configuration['local']['filename'];
        }

        return 'vendor_catalogs/upload/'.strtolower($definition->vendor->name).'/'.$file;
    }

    protected function getFileStoredPath(string $fileName): string
    {
        return 'vendor_catalogs/'.now()->format('Y/m/d').'/'.$fileName;
    }

    protected function storeImport(VendorCatalogImportDefinition $definition, string $file, $content, ?string $contentType = null): VendorCatalogImport
    {
        $fileName = Str::uuid();
        $path = $this->getFileStoredPath($fileName);
        $this->storeFile($path, $content);

        return $this->createImportFile(definition: $definition, path: $path, fileName: $fileName, name: basename($file), contentType: $contentType);
    }

    public function storeFile(string $path, $content): void
    {
        Storage::put($path, $content ?? '');
    }

    private function handleLocal(VendorCatalogImportDefinition $definition): VendorCatalogImport
    {
        Log::info('handle local');

        $content = Storage::get($this->getFileUploadPath($definition));

        return $this->storeImport($definition, $definition->configuration['local']['filename'], $content);
    }

    private function handleUpload(VendorCatalogImportDefinition $definition, ?string $uploadedFile)
    {
        Log::info('handle upload');

        $content = Storage::get($uploadedFile);

        return $this->storeImport($definition, $uploadedFile, $content);
    }

    public function createImportFile(
        VendorCatalogImportDefinition $definition,
        string $path,
        string $fileName,
        string $name,
        ?string $contentType = null
    ): VendorCatalogImport {
        if (! Storage::exists($path)) {
            throw new Exception('file not found');
        }

        if (Storage::fileSize($path) === 0) {
            Log::warning('file size 0');

            throw new Exception('file size 0');
        }

        $hash = hash_file('md5', storage_path(path: 'app/'.$path));

        $state = VendorCatalogImportState::NEW;

        // get latest VendorCatalogImport record with status new
        $latest = VendorCatalogImport::where('state', VendorCatalogImportState::NEW)
            ->orderBy('created_at', 'desc')
            ->first();

        // if content of last response is the same as the current file, mark as duplicated
        if ($latest !== null && $latest->file_hash === $hash) {
            Log::warning('file already present: '.$path);

            // throw new Exception('file already present');

            $state = VendorCatalogImportState::DUPLICATED;
        }

        return $this->createFile($definition, $state, $path, $fileName, $name, $hash, $contentType);
    }

    public function createFile(
        VendorCatalogImportDefinition $definition,
        VendorCatalogImportState $state,
        string $path,
        string $fileName,
        string $name,
        string $hash,
        ?string $contentType = null
    ): VendorCatalogImport {
        return VendorCatalogImport::create([
            'vendor_catalog_import_definition_id' => $definition->id,

            'state' => $state,
            'file_name' => $fileName,
            'path' => $path,
            'disk' => 'local',

            'name' => $name,
            'mime_type' => $contentType ?? 'none',

            'file_hash' => $hash,
            'size' => Storage::exists($path) ? Storage::fileSize($path) : 0,
        ]);
    }

    private function extractZip(VendorCatalogImport $import): VendorCatalogImport
    {
        $zip = new ZipArchive;
        $path = storage_path('app/'.$import->path);
        $gate = $zip->open($path);

        if ($gate === true) {
            $path = 'vendor_catalogs/'.now()->format('Y/m/d').'/zip/'.$import->file_name;
            Storage::createDirectory($path);
            $zip->extractTo(storage_path('app/'.$path));
            $zip->close();
        }

        $file_name = basename(Storage::files($path)[0]);
        $import->update([
            'path' => $path.'/'.$file_name,
        ]);

        return $import;
    }

    public function getDefinitionByName(string $name): ?VendorCatalogImportDefinition
    {
        return VendorCatalogImportDefinition::query()
            ->where('name', '=', $name)
            ->get()
            ->first();
    }
}
