@if ($quotation)
@endif
@php

    use App\Filament\Services\PimQuotationResourceService;
    use App\Models\Pim\Customer\PimAgent;
    use App\Models\Pim\Customer\PimCustomer;
    use Carbon\Carbon;

    const DOCUMENT_LANGUAGE = 'de';

    $priceKey = PimQuotationResourceService::getPriceKeyByQuotation($quotation);
    $quotationDate = Carbon::parse($quotation->date)->format('d.m.Y');
    $quotationDateEnd = Carbon::parse($quotation->validity_period)->format('d.m.Y');
    /** @var PimCustomer $customer */
    $customer = $quotation->customers->first();
    $customerAddress = $customer?->main_address;

    /** @var PimAgent $customer */
    $agent = $quotation->agents->first();
    $agentAddress = $agent?->main_address;

    $quotationSumWithoutDiscount = PimQuotationResourceService::calcItemsCost($quotation, $priceKey, false);
    $unifySumAndTotalCost = number_format($quotationSumWithoutDiscount, 2) === number_format((float)$quotation->total_cost, 2);

@endphp
<!DOCTYPE html>
<html lang="{{ DOCUMENT_LANGUAGE }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angebot Nr.:{{ $quotation->formatted_quotation_number }}</title>
    <link rel="stylesheet" href="{{ public_path('/css/quotationPdf/quotationPdf.css') }}">
    <style>
        .page-footer-content:after {
            content: "Seite " counter(page);
            float: right;
        }
    </style>
</head>
<body>

<div class="page-header">
    <img class="logo" src="{{ public_path('/images/logo.png') }}" alt="Logo"/>
</div>

<div class="page-footer">
    <div class="page-footer-content">Angebot Nr.:{{ $quotation->formatted_quotation_number }}</div>
</div>

<table class="footer-infos smaller-text">
    <tr>
        <td class="company-info">
            <div class="content">
                <p>Some informations</p>
            </div>
        </td>
        <td class="company-info">
            <div class="content">
            </div>
        </td>
        <td class="company-info">
            <div class="content">
            </div>
        </td>
    </tr>
</table>

<div class="page">
    <table class="header-infos smaller-text">
        <tr>
            <td class="company-info">
                <p>Handelskammer Bozen 1234546</p>
            </td>
            <td class="company-info">
                <p>Handelskammer Bozen 1234546</p>
            </td>
            <td class="company-info">
                <p>Handelskammer Bozen 1234546</p>
            </td>
        </tr>
    </table>

    <table class="head">
        <tr>
            <td>
                <div class="block">
                    <p class="font-bold">Company AG</p>
                    <p>Rainbowstreet 99</p>
                    <p>I-39100 Bozen</p>
                </div>
                <div class="block">
                    <p>T. +39 0123 456 879</p>
                    <p>info@company.com</p>
                    <p>www.company.com</p>
                </div>
            </td>
            <td>
                <div class="recipient">
                    <p class="font-bold">
                        {{ $customer->first_name }} {{ $customer->last_name }}</p>
                    @if (!empty($customerAddress))
                        @if (!empty($customerAddress->street))
                            <p>{{ $customerAddress->street }}</p>
                        @endif
                        @if (!empty($customerAddress->additional_address_line_1))
                            <p>{{ $customerAddress->additional_address_line_1 }}</p>
                        @endif
                        <p>
                            {{ $customerAddress->zipcode }} {{ $customerAddress->city }}
                            @if (!empty($customerAddress->region))
                                ({{ $customerAddress->region }})
                            @endif
                        </p>
                        <p>{{ Countries::countryName($customerAddress->country->iso, DOCUMENT_LANGUAGE) }}</p>
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="agent">
                    <p class="font-bold">
                        {{ $agent->first_name }} {{ $agent->last_name }}</p>
                    @if (!empty($agentAddress))
                        @if (!empty($agentAddress->street))
                            <p>{{ $agentAddress->street }}</p>
                        @endif
                        @if (!empty($agentAddress->additional_address_line_1))
                            <p>{{ $agentAddress->additional_address_line_1 }}</p>
                        @endif
                        <p>
                            {{ $agentAddress->zipcode }} {{ $agentAddress->city }}
                            @if (!empty($agentAddress->region))
                                ({{ $agentAddress->region }})
                            @endif
                        </p>
                        <p>{{ Countries::countryName($agentAddress->country->iso, DOCUMENT_LANGUAGE) }}</p>
                    @endif
                </div>
            </td>
            <td>
                <div class="type font-bold">
                    <span>Angebot</span>
                    <img
                            src="data:image/png;base64, iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8+h8AAu8B9totwrcAAAAASUVORK5CYII="
                            alt="placeholder"/>
                </div>
                <div class="grid-container">
                    <div class="entry">
                        <span class="label">Datum: </span><span class="value">{{ $quotationDate }}</span>
                    </div>
                    <div class="entry">
                        <span class="label">Nummer: </span><span class="value">{{ $quotation->formatted_quotation_number }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="text-body intro-text">
        <p>@if (!empty($customer->salutation))
                {{ str_replace(['%C', '%A'], [$customer->first_name, $customer->last_name], $customer->salutation->letter_name) }}
                ,
            @else
                Sehr geehrte Damen und Herren,
            @endif</p>
        <p>{!! nl2br(e(rtrim($quotation->content->introductionText, "\r\n"))) !!}</p>
    </div>
    <table class="products-table">
        <thead>
        <tr>
            <th class="text-left description">
                <div class="label">Beschreibung</div>
            </th>
            <th class="text-right">
                <div class="label">M.E.</div>
            </th>
            <th class="text-right">
                <div class="label">Menge</div>
            </th>
            <th class="text-right">
                <div class="label">Einzelpreis</div>
            </th>
            <th class="text-right">
                <div class="label">Rab.%</div>
            </th>
            <th class="text-right">
                <div class="label">Betrag</div>
            </th>
        </tr>
        </thead>
        <tbody>
        @foreach ($quotation->products as $quotationProduct)
            @if ($quotationProduct->visibility === 1)
                @php
                    $itemPrice = PimQuotationResourceService::getItemPrice($quotationProduct, $priceKey);
                    $itemPrice = $itemPrice !== null && $itemPrice !== 0.0 ? PimQuotationResourceService::formatMoney($itemPrice, true) : '';

                    $rowSum = PimQuotationResourceService::calculateProductRow($quotationProduct, $priceKey);
                    $rowSum = $rowSum !== 0.0 ? PimQuotationResourceService::formatMoney($rowSum, true) : '';
                @endphp
                <tr class="row product-row">
                    <td class="product-info">
                        <p class="code">{{ $quotationProduct->product->identifier ?? '*' }}</p>
                        @if (isset($quotationProduct->product->name))
                            <p>{{ $quotationProduct->product->name }}</p>
                        @endif
                        @if (isset($quotationProduct->product->description))
                            <p>{!! nl2br(e(rtrim($quotationProduct->product->description, "\r\n"))) !!}</p>
                        @endif
                        @if (!empty($quotationProduct->note))
                            <p>{!! nl2br(e(rtrim($quotationProduct->note, "\r\n"))) !!}</p>
                        @endif
                    </td>
                    <td class="text-right">Stk.</td>
                    <td class="text-right">{{ $quotationProduct->quantity ?? '' }}</td>
                    <td class="text-right">{{ $itemPrice }}</td>
                    <td class="text-right">
                        @if ($quotationProduct->discount_percentage != 0)
                            {{ $quotationProduct->discount_percentage }}
                        @endif
                    </td>
                    <td class="text-right">{{ $rowSum }}</td>
                </tr>
            @endif
        @endforeach
        <tr class="row calc-top">
            <td colspan="8"></td>
        </tr>
        <tr class="row calc font-bold">
            <td class="placeholder"></td>
            <td colspan="2" class="labels">
                @if (!$unifySumAndTotalCost)
                    <p>Summe Betrag</p>
                    @if ($quotation->discount_percentage > 0)
                        <p>Rabatt %</p>
                    @endif
                    @if ($quotation->discount_amount > 0)
                        <p>Rabatt fix</p>
                    @endif
                @endif
                <p>MwSt.Grundlage</p>
                <p>MwSt.Betrag ({{ $quotation->tax->tax_rate }} %)</p>
                <p>Gesamtbetrag</p>
            </td>
            <td colspan="4" class="text-right sum">
                @if (!$unifySumAndTotalCost)
                    <p>{{ PimQuotationResourceService::formatMoney($quotationSumWithoutDiscount, true) }}</p>
                    @if ($quotation->discount_percentage > 0)
                        <p>-{{ $quotation->discount_percentage }} %</p>
                    @endif
                    @if ($quotation->discount_amount > 0)
                        <p>-{{ PimQuotationResourceService::formatMoney($quotation->discount_amount, true) }}</p>
                    @endif
                @endif
                <p>{{ PimQuotationResourceService::formatMoney($quotation->total_cost, true) }}</p>
                <p>{{ PimQuotationResourceService::formatMoney($quotation->total_cost_with_tax - $quotation->total_cost, true) }}</p>
                <p>{{ PimQuotationResourceService::formatMoney($quotation->total_cost_with_tax, true) }}</p>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="bottom-text text-body">
        <div class="text-body-entry">
            <p class="title">GÜLTIGKEIT DES ANGEBOTES</p>
            <p>bis {{ $quotationDateEnd }}</p>
        </div>
        <div class="text-body-entry">
            <p class="title">LIEFERZEIT</p>
            <p>{!! nl2br(e(rtrim($quotation->content->deliveryTime, "\r\n"))) !!}</p>
        </div>
        <div class="text-body-entry">
            <p class="title">TRANSPORT</p>
            <p>{!! nl2br(e(rtrim($quotation->content->transport, "\r\n"))) !!}</p>
        </div>
        <div class="text-body-entry">
            <p class="title">MONTAGE</p>
            <p>{!! nl2br(e(rtrim($quotation->content->installation, "\r\n"))) !!}</p>
        </div>
        <div class="text-body-entry">
            <p class="title">GARANTIE</p>
            <p>{!! nl2br(e(rtrim($quotation->content->warranty, "\r\n"))) !!}</p>
        </div>
        <div class="text-body-entry">
            <p class="title">ZAHLUNG</p>
            <p>{!! nl2br(e(rtrim($quotation->content->paymentTerms, "\r\n"))) !!}</p>
        </div>
        <div class="text-body-entry">
            <p>{!! nl2br(e(rtrim($quotation->content->generalInformation, "\r\n"))) !!}</p>
        </div>
        <div class="avoid-page-break">
            <div class="text-body-entry">
                <p>{!! nl2br(e(rtrim($quotation->content->additionalText, "\r\n"))) !!}</p>
            </div>
            <div class="text-body-entry salutation-text">
                <p>Mit freundlichen Grüßen<br>{{ $agent->first_name }} {{ $agent->last_name }}</p>
            </div>
        </div>
    </div>
</div>
@if (count($imageSet))
    <div class="page-break page page-separator">
        <h1>Details</h1>
    </div>
    <div class="page-break page">
        <table class="image-table">
            @foreach ($imageSet as $product)
                <tr>
                    <td>
                        <div class="avoid-page-break">
                            <div class="text">
                                <h1>{{ $product['product']['name']  }}</h1>
                                <p>{{ $product['product']['description'] }}</p>
                            </div>
                            <div class="images">
                                @foreach ($product['images'] as $key => $image)
                                    <img src="{{ $image->getPath() }}"
                                         alt="{{ $product['product']['name'] }} {{ $key }}"/>
                                @endforeach
                            </div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
@endif

@if ($includeDatasheets)
    <div class="page-break page page-separator">
        <h1>Datenblätter</h1>
    </div>
@endif
</body>
</html>
