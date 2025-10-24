<?php

namespace App\Filament\Services;

use App\Enums\Pim\PimProductPriceListTypes;
use App\Enums\Pim\PimQuatationListingItemVisibility;
use App\Enums\Pim\PimQuotationStatus;
use App\Filament\Resources\Pim\PimCustomerResource;
use App\Mail\PimQuotationCustomerMail;
use App\Models\Pim\Customer\PimCustomer;
use App\Models\Pim\PimMediaPool;
use App\Models\Pim\PimQuotation;
use App\Models\Pim\PimQuotationCustomProduct;
use App\Models\Pim\PimQuotationTemplate;
use App\Models\Pim\PimTax;
use App\Models\Pim\Product\PimProduct;
use App\Models\Pim\QuotationProduct;
use App\Services\Pdf\PimQuotationPdfService;
use App\Services\Pim\PimProductService;
use Arr;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Mailer\Exception\TransportException;

class PimQuotationResourceService
{
    public static function stripSelectAssignmentFormData(array &$data): void
    {
        unset($data['customers']);
        unset($data['agents']);
    }

    public static function getPriceKeyByQuotation(PimQuotation|PimQuotationTemplate|null $quotation = null): PimProductPriceListTypes
    {
        if ($quotation !== null &&
            $quotation instanceof PimQuotation &&
            $quotation->customers->first()
        ) {
            $address = $quotation->customers->first()->main_address;
            if ($address !== null) {
                $iso = $address->country->iso;
                if ($iso === PimProductPriceListTypes::AT->name) {
                    return PimProductPriceListTypes::AT;
                }
            }
        }

        return PimProductService::getPriceTableType(auth()->user());
    }

    public static function getPriceKeyByGet(Get $get): PimProductPriceListTypes
    {
        $customerId = $get('customers');
        if ($customerId === null) {
            return PimProductPriceListTypes::DEFAULT;
        }
        $address = PimCustomer::query()
            ->with('addresses')
            ->where('id', '=', $customerId)
            ->first()
            ->main_address;

        if ($address !== null) {
            $iso = $address->country->iso;

            return $iso === 'AT' ? PimProductPriceListTypes::AT : PimProductPriceListTypes::DEFAULT;
        }

        return PimProductPriceListTypes::DEFAULT;
    }

    public static function calculateProductRow(QuotationProduct $record, ?PimProductPriceListTypes $priceKey = null, bool $addDiscount = true): ?float
    {
        $sum = PimQuotationResourceService::getAmountItemPrice($record, $priceKey);

        return $addDiscount ? $sum * (1 - $record->discount_percentage / 100) : $sum;
    }

    public static function getItemPrice(QuotationProduct $record, ?PimProductPriceListTypes $priceKey): ?float
    {
        if ($record->visibility === PimQuatationListingItemVisibility::INTERNAL->value) {
            return null;
        }

        if ($record->product_type === PimQuotationCustomProduct::class) {
            return $record->price_override;
        }

        if ($record->product_type === PimProduct::class) {
            $itemPrice = $record->price_override ?? PimQuotationResourceService::getProductItemPrice($record->product, $priceKey) ?? 0;
            if ($itemPrice === 0) {
                return null;
            }

            return $itemPrice;
        }

        return null;
    }

    public static function getProductItemPrice(PimProduct $record, PimProductPriceListTypes $priceKey): ?float
    {
        // if the price is not set for the current property group, we return the default price
        if (isset($record->prices[$priceKey->value])) {
            return $record->prices[$priceKey->value];
        }

        if (isset($record->prices[PimProductPriceListTypes::DEFAULT->value])) {
            return $record->prices[PimProductPriceListTypes::DEFAULT->value];
        }

        return null;
    }

    public static function getAmountItemPrice(QuotationProduct $record, ?PimProductPriceListTypes $priceKey = null): ?float
    {
        $itemPrice = PimQuotationResourceService::getItemPrice($record, $priceKey);

        return $record->quantity * $itemPrice;
    }

    public static function calcItemsCost(PimQuotation|PimQuotationTemplate|null $quotation, PimProductPriceListTypes $priceKey, bool $addDiscount = true): float
    {
        $itemsCost = 0.0;
        if (! $quotation) {
            return $itemsCost;
        }
        $quotation->products->each(function (QuotationProduct $product) use (&$itemsCost, $priceKey, $addDiscount) {
            $itemsCost += PimQuotationResourceService::calculateProductRow($product, $priceKey, $addDiscount);
        });

        return $itemsCost;
    }

    public static function calcTotal(PimQuotation|PimQuotationTemplate|null $quotation,
        PimProductPriceListTypes $priceKey,
        ?float $shippingCost = null,
        ?float $discount_amount = null,
        ?float $discount_percentage = null,
        bool $addDiscount = true
    ): float {
        $discountAmount = $discount_amount ?? $quotation->discount_amount;
        $shippingCost = $shippingCost ?? $quotation->shipping_cost;
        $discountPercentage = $discount_percentage ?? $quotation->discount_percentage;

        $itemsCost = self::calcItemsCost($quotation, $priceKey, $addDiscount);

        $discountPercentage = 1.00 - $discountPercentage / 100;

        return ($itemsCost * $discountPercentage) + $shippingCost - $discountAmount;
    }

    public static function calcTotalWithAppliedTax(float $taxRate, float $total): float
    {
        return $total * (1 + $taxRate / 100);
    }

    public static function getCalcTotalLiveFnc(): callable
    {
        return function (Get $get, $record, Set $set) {
            $total = PimQuotationResourceService::calcTotal(
                quotation: $record,
                priceKey: PimQuotationResourceService::getPriceKeyByGet($get),
                shippingCost: PimQuotationResourceService::replaceComma($get('shipping_cost')),
                discount_amount: PimQuotationResourceService::replaceComma($get('discount_amount')),
                discount_percentage: PimQuotationResourceService::replaceComma($get('discount_percentage')),
            );
            $set('total_cost', PimQuotationResourceService::formatMoney($total));

            $taxId = $get('pim_tax_id');
            if ($taxId !== null) {
                $totalWithTax = PimQuotationResourceService::calcTotalWithAppliedTax(
                    taxRate: PimTax::find($taxId)->tax_rate,
                    total: $total
                );
                $set('total_cost_with_tax', PimQuotationResourceService::formatMoney($totalWithTax));
            }
        };
    }

    public static function replaceComma(?string $value = null): float
    {
        return $value ? floatval(str_replace(',', '.', $value)) : 0.00;
    }

    public static function formatMoney(float $amount = 0.00, bool $addCurencySign = false): string
    {
        $suffix = $addCurencySign ? ' €' : '';

        return number_format($amount, 2, ',', '.').$suffix;
    }

    public static function formatMoneyToDB(string $amount): float
    {
        $amount = str_replace('.', '', $amount);
        $amount = str_replace(',', '.', $amount);

        return (float) $amount;
    }

    public static function getRootQuotation(PimQuotation $quotation): PimQuotation
    {
        if (! empty($quotation->parent_id)) {
            return PimQuotationResourceService::getQuotationById($quotation->parent_id);
        }

        return $quotation;
    }

    public static function getAllVersions(PimQuotation $quotation): Collection
    {
        $root = PimQuotationResourceService::getRootQuotation($quotation);

        return PimQuotationResourceService::getQuotationsByRootId($root->id)->get();
    }

    public static function getQuotationsByRootId(string $quotationId): Builder
    {
        /** @var PimQuotation $quotation */
        return PimQuotation::query()
            ->with([
                'children',
                'parent',
                'customers',
                'agents',
                'products',
                'products.product',
                'products.product.media' => function ($query) {
                    $query->orderBy('order_column');
                },
                // 'products.product.translations',
            ])
            ->where('parent_id', '=', $quotationId)
            ->orWhere('id', '=', $quotationId)
            ->orderBy('version', 'desc');
    }

    public static function getNewVersionNumber(PimQuotation $quotation): int
    {
        return PimQuotation::query()
            ->select('version')
            ->withTrashed()
            ->where('quotation_number', '=', $quotation->quotation_number)
            ->orderBy('version', 'desc')
            ->first()
            ->version + 1;
    }

    public static function getQuotationById(string $quotationId)
    {
        /** @var PimQuotation $quotation */
        return PimQuotation::with([
            'children',
            'parent',
            'customers',
            'agents',
            'products',
            'products.product',
            'products.product.media' => function ($query) {
                $query->orderBy('order_column');
            },
            // 'products.product.translations',
        ])->find($quotationId);
    }

    public static function getPimQuotationTemplate(string $id): ?PimQuotationTemplate
    {
        return PimQuotationTemplate::with('products')->find($id);

    }

    public static function getAttachments(array $data): Collection
    {
        $mediaIds = isset($data[PimMediaPool::getMediaCollectionName()]) ? array_keys(array_filter($data[PimMediaPool::getMediaCollectionName()])) : [];
        $attachments = collect();
        foreach ($mediaIds as $mediaId) {
            $media = Media::find($mediaId);
            if ($media) {
                $attachments->push($media);
            }
        }

        return $attachments;
    }

    public static function sendEmailAction(string $quotationId, array $data)
    {
        /** @var PimQuotation $quotation */
        $quotation = PimQuotationResourceService::getQuotationById($quotationId);
        $filePath = PimQuotationPdfService::generateQuotationPdf($quotation->id, $data);
        $attachments = PimQuotationResourceService::getAttachments($data);
        $additionalEmails = $data['add_additional_emails'] ? $data['additional_emails'] : null;

        try {
            $mailer = Mail::mailer('smtp')
                ->to($quotation->customers->first()->email)
                ->cc($additionalEmails);

            if (App::environment('production')) {
                $bccs = [
                    $quotation->agents->first()->email,
                ];

                $bcc = env('QUOTATION_EMAIL_BCC');
                if ($bcc !== null) {
                    $bccs[] = $bcc;
                }

                $bccs = array_unique(Arr::where($bccs, fn ($value) => filled($value)));
                if (! empty($bccs)) {
                    $mailer->bcc($bccs);
                }
            }

            $mailer->send(new PimQuotationCustomerMail($quotation, $filePath, $attachments, $data['email_content']));

            // set status to sent
            $quotation->update([
                'status' => PimQuotationStatus::SENT,
                'sent_to_customer' => Carbon::now(),
            ]);

            Notification::make()
                ->success()
                ->title(__('Email versendet'))
                ->body(__('Das Angebot wurde erfolgreich per Email versendet.'))
                ->send();

        } catch (TransportException $e) {
            Notification::make()
                ->danger()
                ->persistent()
                ->title(__('Fehler beim Senden der Email'))
                ->body($e->getMessage())
                ->send();
        }
    }

    public static function getSendCustomerMailAction(string $quotationId): Action
    {
        /** @var PimQuotation $quotation */
        $quotation = PimQuotationResourceService::getQuotationById($quotationId);
        $customer = $quotation->customers->first();

        $action = Action::make('send_email_to_customer')
            ->label(__('Angebot versenden'))
            ->icon('heroicon-o-document-text')
            ->modal()
            ->form(function () use ($quotation, $customer) {
                if ($customer->addresses->count() === 0) {
                    return [
                        Placeholder::make('missing_address')
                            ->label('')
                            ->content(__('⚠️ Kundenadresse fehlt. Adresse bitte vervollständigen.'))
                            ->columnSpanFull(),
                    ];
                }

                return PimQuotationResourceService::getSendCustomerEmailModalForm($quotation);
            })
            ->modalFooterActions([])
            ->visible(fn ($record) => $record?->status === PimQuotationStatus::DRAFT)
            ->modalHeading(__('Angebot an Kunde per email versenden'));

        if ($customer->addresses->count() === 0) {
            $editCustomerAction = EditAction::make('edit_customer_address')
                ->label(__('Kundenadresse bearbeiten'))
                ->icon('heroicon-o-pencil-square')
                ->url(fn (PimQuotation $record) => PimCustomerResource::getUrl('edit', ['record' => $customer->id, 'activeRelationManager' => 1]));
            $action->modalSubmitAction($editCustomerAction);
        }

        return $action;
    }

    private static function getSendCustomerEmailModalForm(PimQuotation $quotation): array
    {
        $agent = $quotation->agents->first();
        $customer = $quotation->customers->first();

        $defaultContent = '<p>';
        if (! empty($quotation->customers->first()->salutation)) {
            $defaultContent .= str_replace(
                ['%C', '%A'],
                [$quotation->customers->first()->first_name, $quotation->customers->first()->last_name],
                $quotation->customers->first()->salutation->letter_name
            ).',';
        } else {
            $defaultContent .= __('Sehr geehrte Damen und Herren,');
        }
        $defaultContent .= '</p>';
        $defaultContent .= '<p>'.__('anbei erhalten Sie unser Angebot').' '.$quotation->formatted_quotation_number.' '.__('vom').' '.$quotation->date->format('d.m.Y').'.</p>';
        $defaultContent .= '<p>'.__('Wir freuen uns auf Ihre Rückmeldung.').'</p>';
        $defaultContent .= '<p>'.__('Mit freundlichen Grüßen,').'</p>';
        $defaultContent .= '<p>'.$quotation->agents->first()->full_name.'</p>';

        return [
            Section::make(__('Empfänger'))
                ->columns(1)
                ->schema([
                    TextInput::make('agent_name')
                        ->label(__('Vertrieb (Sender)'))
                        ->default($agent->getFullNameAttribute().': '.$agent->email)
                        ->readOnly(),

                    TextInput::make('customer_name')
                        ->label(__('Kunde (Empfänger)'))
                        ->default($customer->getFullNameAttribute().': '.$customer->email)
                        ->readOnly(),

                    Toggle::make('add_additional_emails')
                        ->live()
                        ->label(__('Weitere Empfänger (CC) hinzufügen')),

                    Repeater::make('additional_emails')
                        ->label(__('Email Adressen'))
                        ->visible(fn (Get $get) => $get('add_additional_emails'))
                        ->minItems(1)
                        ->simple(
                            TextInput::make('email')
                                ->label(__('Email'))
                                ->email()
                                ->required()
                                ->nullable(false)
                                ->placeholder(__('example@domain.com')),
                        )
                        ->collapsible()
                        ->addActionLabel(__('weitere hinzufügen'))
                        ->columns(1),
                ]),

            Section::make(__('Inhalt'))
                ->columns(1)
                ->schema([
                    RichEditor::make('email_content')
                        ->hiddenLabel()
                        ->default($defaultContent)
                        ->toolbarButtons([
                            'bold',
                            'bulletList',
                            'link',
                            'redo',
                            'undo',
                        ]),
                ]),

            ...PimQuotationResourceFormService::getQuotationEmailAttachmentMediaPoolFormCheckboxes(),
            ...PimQuotationResourceFormService::getQuotationExportFormCheckboxes($quotation),
        ];
    }

    public static function assignQuotationProducts(PimQuotation|PimQuotationTemplate $quotationTemplate, PimQuotation $newQuotation): void
    {
        foreach ($quotationTemplate->products as $quotationProduct) {
            $data = [
                'position' => $quotationProduct->position,
                'quantity' => $quotationProduct->quantity,
                'discount_percentage' => $quotationProduct->discount_percentage,
                'price_override' => $quotationProduct->price_override,
                'visibility' => $quotationProduct->visibility,
                'note' => $quotationProduct->note,
            ];
            QuotationProduct::createForQuotation($newQuotation->id, $quotationProduct->product_id, $quotationProduct->product_type, $data);
        }
    }

    public static function generateQuotationVersion(PimQuotation $quotation): PimQuotation
    {
        $quotation = PimQuotationResourceService::getQuotationById($quotation->id);
        $newVersionNumber = PimQuotationResourceService::getNewVersionNumber($quotation);

        // Create a new quotation with the same attributes as the original
        $newQuotation = new PimQuotation;
        $newQuotation->fill([
            'parent_id' => $quotation->parent_id ?? $quotation->id,
            'version' => $newVersionNumber,
            'quotation_number' => $quotation->quotation_number,
            'content' => $quotation->content,
            'date' => Carbon::now(),
            'validity_period_unit' => $quotation->validity_period_unit,
            'validity_period_value' => $quotation->validity_period_value,
            'validity_period' => $quotation->validity_period,
            'discount_percentage' => $quotation->discount_percentage,
            'discount_amount' => $quotation->discount_amount,
            'shipping_cost' => $quotation->shipping_cost,
            'total_cost' => $quotation->total_cost,
            'pim_tax_id' => $quotation->pim_tax_id,
            'total_cost_with_tax' => $quotation->total_cost_with_tax,
            'status' => PimQuotationStatus::DRAFT,
            'pim_lead_id' => $quotation->pim_lead_id,
        ]);

        // Save the new quotation to generate a new ID and quotation number
        $newQuotation->save();

        // Copy products from the original quotation to the new one
        PimQuotationResourceService::assignQuotationProducts($quotation, $newQuotation);

        // Copy customer relationships
        foreach ($quotation->customers as $customer) {
            $newQuotation->customers()->attach($customer->id);
        }

        // Copy agent relationships
        foreach ($quotation->agents as $agent) {
            $newQuotation->agents()->attach($agent->id);
        }

        return $newQuotation;
    }
}
