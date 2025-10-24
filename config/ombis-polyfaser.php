<?php

use App\Enums\Pim\PimFormFileUploadType;
use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\PimTax;

return [

    'vendorName' => 'OmbisPolyfaserJSON',
    'vendorCatalogDefinitionName' => 'Ombis Polyfaser Products local storage',

    'structure' => [
        PimMappingType::PRODUCT->value => [
            'name' => 'Name_de',
        ],
    ],

    'validManufacturerCodes' => null,

    'queryModifier' => [
        PimMappingType::PRODUCT->value => [
            'mainProductIdentifier' => 'data.Nummer',
            'sortBy' => 'data.ID',
        ],
    ],

    'mapping' => [

        PimMappingType::MANUFACTURER->value => [],

        PimMappingType::PRODUCT->value => [

            'PreviewImageUpload' => [
                'type' => PimFormType::FILEUPLOAD->name,
                'field' => PimFormStoreField::MEDIA->value,
                'label' => 'Preview Image',
                'collection' => 'preview-image',
                'section' => PimFormSection::SPECIFICATION,
                'acceptedFileTypes' => PimFormFileUploadType::IMAGE,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            'Datasheet' => [
                'type' => PimFormType::FILEUPLOAD->name,
                'field' => PimFormStoreField::MEDIA->value,
                'label' => 'Datasheet',
                'collection' => 'datasheet',
                'section' => PimFormSection::SPECIFICATION,
                'acceptedFileTypes' => PimFormFileUploadType::PDF,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'translatable' => true,
            ],

            // ERP ID
            'ID' => [
                'type' => PimFormType::NUMBER->name,
                'format' => 'integer',
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'ERP Id',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            // gtin number
            'Code' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::IDENTIFIER->value,
                'label' => 'Code',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            // Product number of variant
            'Warennummer' => [
                'type' => PimFormType::TEXT->name,
                'format' => 'integer',
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Warennummer',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            'Nummer' => [
                'type' => PimFormType::TEXT->name,
                'format' => 'integer',
                'field' => PimFormStoreField::PRODUCT_NUMBER->value,
                'label' => 'Nummer',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            /*
            'Suchbegriff' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Suchbegriff',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],
            */

            'Herstellerkode' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Herstellerkode',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            'Modell' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Modell',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            /*
            // doesn't exist
            'PreisPro' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Preis pro',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],
            */

            /*
            'VkPreis' => [
                'type' => PimFormType::PRICE->name,
                'field' => PimFormStoreField::PRICES->value,
                'label' => 'Preis Verkauf',
                'section' => PimFormSection::PRICING,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'required' => true,
            ],

            'Bruttopreis' => [
                'type' => PimFormType::PRICE->name,
                'field' => PimFormStoreField::PRICES->value,
                'label' => 'Preis Brutto',
                'section' => PimFormSection::PRICING,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'required' => true,
            ],
            */

            'Name_de' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::NAME->value,
                'label' => 'Name',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'translatable' => true,
                'translations' => [
                    'it_IT' => 'Name_it',
                    'en_GB' => 'Name_en',
                ],
            ],

            'Description_de' => [
                'type' => PimFormType::TEXTAREA->name,
                'field' => PimFormStoreField::DESCRIPTION->value,
                'label' => 'Beschreibung',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'translatable' => true,
                'translations' => [
                    'it_IT' => 'Description_it',
                    'en_GB' => 'Description_en',
                ],
            ],

            'XF_Onlineoffer' => [
                'type' => PimFormType::BOOL->name,
                'field' => PimFormStoreField::ACTIVE->value,
                'label' => 'Aktiv',
                'section' => PimFormSection::AVAILABILITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            // overwrite
            /*
            'MwStSatz' => [
                'type' => PimTax::class,
                'field' => 'pim_tax_id',
                'label' => 'Steuersatz',
                'section' => PimFormSection::PRICING,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],
*/
            'Stock' => [
                'type' => PimFormType::NUMBER->name,
                'format' => 'integer',
                'field' => PimFormStoreField::STOCK->value,
                'value' => -1,
                'label' => 'Lagerbestand',
                'section' => PimFormSection::AVAILABILITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

        ],
    ],

];
