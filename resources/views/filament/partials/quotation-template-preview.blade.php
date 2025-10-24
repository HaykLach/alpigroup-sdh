<div class="mb-5 mt-4">
    <h2 class="text-xl font-bold mb-4">{{ $quotation->formatted_quotation_number }}</h2>
    @if(!empty($quotation->internal_comment))
        <p class="mb-5">{{ $quotation->internal_comment }}</p>
    @endif

    <table class="min-w-full table-auto border-collapse text-sm text-left border m-auto">
        <thead>
        <tr class="bg-gray-200 text-gray-700 uppercase tracking-wide text-xs">
            <th class="px-4 py-2 border-b text-right">{{ __('Position') }}</th>
            <th class="px-4 py-2 border-b">{{ __('EAN Code') }}</th>
            <th class="px-4 py-2 border-b text-right">{{ __('Nr.') }}</th>
            <th class="px-4 py-2 border-b text-right">{{ __('Menge') }}</th>
            <th class="px-4 py-2 border-b text-right">{{ __('M.E.') }}</th>
            <th class="px-4 py-2 border-b text-right">{{ __('Rabatt in %') }}</th>
            <th class="px-4 py-2 border-b">{{ __('Name') }}</th>
            <th class="px-4 py-2 border-b">{{ __('Beschreibung') }}</th>
            <th class="px-4 py-2 border-b">{{ __('Notizen') }}</th>
            <th class="px-4 py-2 border-b">{{ __('Sichtbarkeit') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($quotation->products as $quotationProduct)
            <tr>
                <td class="px-4 py-2 border-b text-right align-top">{{ $quotationProduct->position }}</td>
                <td class="px-4 py-2 border-b align-top"><strong>{{ isset($quotationProduct->product->identifier) ? $quotationProduct->product->identifier : '' }}</strong></td>
                <td class="px-4 py-2 border-b text-right align-top">{{ isset($quotationProduct->product->product_number) ? $quotationProduct->product->product_number : '' }}</td>
                <td class="px-4 py-2 border-b text-right align-top">{{ $quotationProduct->quantity }}</td>
                <td class="px-4 py-2 border-b text-right align-top">{{ __('Stück') }}</td>
                <td class="px-4 py-2 border-b text-right align-top">{{ $quotationProduct->discount_percentage }}</td>
                <td class="px-4 py-2 border-b align-top"><strong>{{ $quotationProduct->product->name }}</strong></td>
                <td class="px-4 py-2 border-b max-w-lg break-words align-top">{{ $quotationProduct->product->description }}</td>
                <td class="px-4 py-2 border-b max-w-lg break-words align-top">{{ $quotationProduct->note }}</td>
                <td class="px-4 py-2 border-b text-right align-top">{{ $quotationProduct->visibility === 1 ? __('sichtbar') : __('intern') }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

</div>
