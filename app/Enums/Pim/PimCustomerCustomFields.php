<?php

namespace App\Enums\Pim;

enum PimCustomerCustomFields: string
{
    case TYPE = 'type';
    case COMPANY_NAME = 'company_name';
    case FISCAL_CODE = 'fiscal_code';
    case VAT_ID = 'vat_id';
    case AGENT_ID = 'agent_id';
    case NET_FOLDER_DOCUMENTS = 'net_folder_documents';
    case BLOCKED = 'blocked';
    case SHIPPING_METHOD_CODE = 'shipping_method_code';
    case SHIPPING_METHOD_NAME = 'shipping_method_name';
    case SHIPPING_METHOD_PROVIDER = 'shipping_method_provider';
    case PAYMENT_METHOD_CODE = 'payment_method_code';
    case PAYMENT_METHOD_NAME = 'payment_method_name';
    case CURRENCY_CODE = 'currency_code';
    case CURRENCY_NAME = 'currency_name';
}
