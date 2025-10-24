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
}
