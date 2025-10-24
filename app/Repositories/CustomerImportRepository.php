<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class CustomerImportRepository
{
    public function insert(string $path, string $fileName, string $status): int
    {
        return (int) DB::table('customer_import')->insertGetId([
            'path' => $path,
            'file_name' => $fileName,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        DB::table('customer_import')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
}
