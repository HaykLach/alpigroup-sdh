<?php

namespace App\Enums\Pim;

enum PimCustomerType: string
{
    case AGENT = 'agent';
    case CUSTOMER = 'customer';
    case CRM_CUSTOMER = 'crm_customer';
}
