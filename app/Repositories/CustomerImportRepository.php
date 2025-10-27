<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\CustomerImport;

class CustomerImportRepository
{
    public function insert(string $path, string $fileName, string $status): CustomerImport
    {
        return CustomerImport::query()->create([
            'path' => $path,
            'file_name' => $fileName,
            'status' => $status,
        ]);
    }

    public function updateStatus(string $id, string $status): void
    {
        CustomerImport::query()
            ->whereKey($id)
            ->update([
                'status' => $status,
            ]);
    }
}
