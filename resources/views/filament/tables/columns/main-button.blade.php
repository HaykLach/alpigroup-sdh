<div>
    <button onclick="window.open('pim-products?tableFilters[product_number][value]={{ $getRecord()->parent->product_number }}&activeTab=main', '_self')"
            style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600); width: 80px; margin-right: 1em; margin-left: 1em;"
            class="inline-block fi-badge rounded-md text-xs font-medium ring-1 ring-inset px-4 min-w-[theme(spacing.5)] py-2 tracking-tight fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-primary w-max"
    >
        {{ $getRecord()->parent->product_number }}
    </button>
</div>
