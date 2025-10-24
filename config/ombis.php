<?php

use App\Enums\Pim\PimFormFileUploadType;
use App\Enums\Pim\PimFormSection;
use App\Enums\Pim\PimFormStoreField;
use App\Enums\Pim\PimFormType;
use App\Enums\Pim\PimMappingType;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProductManufacturer;

return [

    'vendorName' => 'OmbisJSON',
    'vendorCatalogDefinitionName' => 'Ombis Products local storage',

    'structure' => [
        PimMappingType::PRODUCT->value => [
            'name' => 'XF_MainName',
        ],
    ],

    'validManufacturerCodes' => ['HAKRO', 'F. ENGEL'],

    'queryModifier' => [
        PimMappingType::PRODUCT->value => [
            'mainProductIdentifier' => 'data.ArtikelkodeHersteller',
            'sortBy' => 'data.ID',
        ],
    ],

    'mapping' => [

        PimMappingType::MANUFACTURER->value => [
            'Sizing-information' => [
                'type' => PimFormType::FILEUPLOAD->name,
                'field' => PimFormStoreField::MEDIA->value,
                'label' => 'Größeninformation',
                'collection' => 'sizing-information',
                'section' => PimFormSection::SPECIFICATION,
                'acceptedFileTypes' => PimFormFileUploadType::IMAGE,
                'translatable' => true,
            ],

            'Logo-upload' => [
                'type' => PimFormType::FILEUPLOAD->name,
                'field' => PimFormStoreField::MEDIA->value,
                'label' => 'Logo',
                'collection' => 'logo',
                'section' => PimFormSection::SPECIFICATION,
                'acceptedFileTypes' => PimFormFileUploadType::IMAGE,
            ],
        ],

        PimMappingType::PRODUCT->value => [

            'XF_Gender' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Geschlecht',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            'XF_ArmLength' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Armlänge',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            'XF_Cut' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Schnitt',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            'XF_Size' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Größe (Detail)',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
                'required' => true,
                'readonly' => true,
            ],

            'XF_SizeFilter' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Größe',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
                'required' => true,
                'readonly' => true,
            ],

            'XF_Color' => [
                'type' => PimFormType::COLOR->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Farbe (Detail)',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
                'translatable' => true,
                'required' => true,
                'readonly' => true,
            ],
            'XF_ColorFilter' => [
                'type' => PimFormType::COLOR->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Farbe',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
                'translatable' => true,
                'required' => true,
            ],

            'Nettogewicht' => [
                'type' => PimFormType::NUMBER->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Gewicht (Gramm)',
                'format' => 'integer',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
            ],

            'Bezeichnung_de' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::NAME->value,
                'label' => 'Name',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
            ],
            'Beschreibung_de' => [
                'type' => PimFormType::TEXTAREA->name,
                'field' => PimFormStoreField::DESCRIPTION->value,
                'label' => 'Beschreibung',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
            ],

            'XF_TissueInfo' => [
                'type' => PimFormType::TEXTAREA->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Material',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            'XF_NoteWashCare' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Pflege',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            'XF_LinkDataSheet' => [
                'type' => PimFormType::URL->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'DataSheet',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],
            /*
            'PDF-upload' => [
                'type' => PimFormType::FILEUPLOAD->name,
                'field' => PimFormStoreField::MEDIA->value,
                'label' => 'Product information',
                'collection' => 'product-information',
                'section' => PimFormSection::SPECIFICATION,
                'acceptedFileTypes' => PimFormFileUploadType::PDF,
                'edit' => [
                    'main' => true,
                    'variant' => false
                ],
                'translatable' => true,
            ],
*/
            // @todo create thumbnails for pim list view
            'XF_LinkModelImage1' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkModelImage2' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkModelImage3' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkModelImage4' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkModelImage5' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkProductImage1' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkProductImage2' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkProductImage3' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkProductImage4' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],
            'XF_LinkProductImage5' => [
                'type' => 'image',
                'field' => PimFormStoreField::IMAGES->value,
            ],

            // gtin number
            'EANCode' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::IDENTIFIER->value,
                'label' => 'Ean code',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
            ],
            // Product number of variant
            'Code' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::PRODUCT_NUMBER->value,
                'label' => 'Product Nr.',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
            ],

            // Main Product number of variant / main product
            'XF_MainName' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::MAIN_NAME->value,
                'model' => 'parent',
                'label' => 'Main Product Nr.',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
            ],

            // Main product number
            'ArtikelkodeHersteller' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Main number',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
            ],

            // ERP ID
            'ID' => [
                'type' => PimFormType::TEXT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'ERP Id',
                'section' => PimFormSection::IDENTITY,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],

            'XF_Shop' => [
                'type' => PimFormType::BOOL->name,
                'field' => PimFormStoreField::ACTIVE->value,
                'label' => 'Aktiv',
                'section' => PimFormSection::AVAILABILITY,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
            ],

            'XF_Season' => [
                'type' => PimFormType::SELECT->name,
                'field' => PimFormStoreField::CUSTOM_FIELDS->value,
                'label' => 'Saison',
                'section' => PimFormSection::SPECIFICATION,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
                'translatable' => true,
            ],

            // overwrite
            'MwStSatz' => [
                'type' => PimTax::class,
                'field' => 'pim_tax_id',
                'label' => 'Steuersatz',
                'section' => PimFormSection::PRICING,
                'edit' => [
                    'main' => true,
                    'variant' => false,
                ],
            ],
            'MarkeCode' => [
                'type' => PimProductManufacturer::class,
                'field' => 'pim_manufacturer_id',
                'label' => 'Hersteller',
                'section' => PimFormSection::MAIN,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
            ],
            'Stock' => [
                'type' => PimFormType::NUMBER->name,
                'field' => PimFormStoreField::STOCK->value,
                'value' => -1,
                'label' => 'Lagerbestand',
                'format' => 'integer',
                'section' => PimFormSection::AVAILABILITY,
                'edit' => [
                    'main' => false,
                    'variant' => true,
                ],
            ],
            'Verkaufspreis' => [
                'type' => PimFormType::PRICE->name,
                'field' => PimFormStoreField::PRICES->value,
                'label' => 'Verkaufspreis',
                'section' => PimFormSection::PRICING,
                'edit' => [
                    'main' => true,
                    'variant' => true,
                ],
                'required' => true,
            ],
        ],
    ],

];
