<?php

namespace App\Services\Pim\Import;

use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\Product\PimProductManufacturer;
use App\Models\Pim\Property\PimPropertyGroup;
use App\Models\VendorCatalog\ImportDefinition\VendorCatalogImportDefinition;
use App\Models\VendorCatalog\VendorCatalogVendor;
use App\Services\Pim\PimGenerateIdService;

class PimVendorCatalogImportService
{
    public static function truncateTables()
    {
        \Illuminate\Support\Facades\DB::table('failed_jobs')->delete();
        \Illuminate\Support\Facades\DB::table('jobs')->delete();

        // delete all PimPropertyGroup not softdelete
        PimProduct::query()->forceDelete();
        // delete all PimProductManufacturer
        PimProductManufacturer::query()->forceDelete();
        // delete all PimPropertyGroup
        PimPropertyGroup::query()->delete();
        // delete all media
        // Media::query()->delete();

        // force delete records from tables: vendor_catalog_import_records, vendor_catalog_entries
        \Illuminate\Support\Facades\DB::table('vendor_catalog_import_records')->delete();
        \Illuminate\Support\Facades\DB::table('vendor_catalog_entries')->delete();

    }

    public static function createVendorCatalogVendor(string $vendorName): string
    {
        // create definition
        $vendorId = PimGenerateIdService::getVendorCatalogVendorId($vendorName);
        VendorCatalogVendor::updateOrCreate([
            'id' => $vendorId,
        ],
            [
                'name' => $vendorName,
                'code' => $vendorName,
                'contact' => json_decode('{"name": null, "email": null, "phone": null}'),
            ]);

        return $vendorId;
    }

    public static function createVendorDefinition(string $nameField, string $vendorId, string $definitionName): VendorCatalogImportDefinition
    {
        // INSERT INTO vendor_catalog_import_definitions (id, name, source, protocol, vendor_catalog_vendor_id, file, compression, setup, notification, configuration, mappings, columns, deleted_at, created_at, updated_at) VALUES ('1e2e2b55-d3de-56e7-9779-e8a339ac468c', 'Ombis Products local storage', 'json', 'local', 'e4c629b9-90f7-5d2e-808b-854cf90d4f33', '{"escape": "\\\\", "encoding": null, "delimiter": ";", "enclosure": "\'", "start_row": "1", "header_row": null}', '{"type": null, "active": false}', '{"depot_id": "Code", "stock_column": null, "article_column": "ID"}', '{"mail": {"address": null, "notification": false}}', '{"ftp": {"ssl": true, "host": null, "path": null, "port": null, "root": null, "passive": true, "timeout": null, "password": null, "username": null}, "http": {"url": null, "type": "No Authentication", "password": null, "username": null}, "local": {"filename": "products.json"}}', '[{"to": "gtin", "from": "Code"}, {"to": "number", "from": "Code"}, {"to": "name", "from": ".$nameField."}]', '[{"name": "EANCode GTIN", "field": "Code"}, {"name": "Product Code", "field": "Code"}, {"name": "Name", "field": '.$nameField.'}]', null, '2024-10-14 13:15:42', '2024-10-14 13:17:09');
        $data = [
            'id' => PimGenerateIdService::getVendorCatalogImportDefinitionId($definitionName),
            'name' => $definitionName,
            'source' => 'json',
            'protocol' => 'local',
            'vendor_catalog_vendor_id' => $vendorId,
            'file' => json_decode('{"escape": "\\\\", "encoding": null, "delimiter": ";", "enclosure": "\'", "start_row": "1", "header_row": null}', true),
            'compression' => json_decode('{"type": null, "active": false}', true),
            'setup' => json_decode('{"depot_id": "Code", "stock_column": null, "article_column": "ID"}', true),
            'notification' => json_decode('{"mail": {"address": null, "notification": false}}', true),
            'configuration' => json_decode('{"ftp": {"ssl": true, "host": null, "path": null, "port": null, "root": null, "passive": true, "timeout": null, "password": null, "username": null}, "http": {"url": null, "type": "No Authentication", "password": null, "username": null}, "local": {"filename": "products.json"}}', true),
            'mappings' => json_decode('[{"to": "gtin", "from": "Code"}, {"to": "number", "from": "Code"}, {"to": "name", "from": "'.$nameField.'"}]', true),
            'columns' => json_decode('[{"name": "EANCode GTIN", "field": "Code"}, {"name": "Product Code", "field": "Code"}, {"name": "Name", "field": "'.$nameField.'"}]', true),
        ];

        return VendorCatalogImportDefinition::create($data);
    }
}
