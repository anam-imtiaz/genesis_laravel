<section data-bb-toggle="quote-content" class="cart-area pt-50 pb-50">
    <div class="container">
        @php
            $quoteCart = Cart::instance('quote');
        @endphp
        @if ($quoteCart->isNotEmpty() && $quoteCart->count() > 0)
            <div class="row">
                <div class="col-xl-9 col-lg-8">
                    <x-core::form method="POST" :url="route('public.quote.update')" class="mw-100 overflow-x-auto">
                        <div class="cart-list mb-25 mr-30">
                            <table data-bb-value="quote-table" class="table">
                                <thead class="table-light">
                                <tr>
                                    <th colspan="2" class="cart-header-product">{{ __('Product') }}</th>
                                    <th class="cart-header-price">{{ __('Price') }}</th>
                                    <th class="cart-header-quantity">{{ __('Quantity') }}</th>
                                    <th class="cart-header-total">{{ __('Total') }}</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($quoteCart->content() as $key => $cartItem)
                                    @php
                                        $product = $products->firstWhere('id', $cartItem->id);
                                    @endphp

                                    @continue(empty($product))

                                    <tr data-bb-value="quote-row-{{ $cartItem->rowId }}">
                                        <input type="hidden" name="items[{{ $key }}][rowId]" value="{{ $cartItem->rowId }}">

                                        <td class="cart-img">
                                            <a href="{{ $product->original_product->url }}">
                                                {{ RvMedia::image($cartItem->options['image'], $product->original_product->name, 'thumb') }}
                                            </a>
                                        </td>
                                        <td class="ps-3 align-middle">
                                            {!! apply_filters('ecommerce_quote_before_item_content', null, $cartItem) !!}

                                            <div class="cart-title">
                                                <a href="{{ $product->original_product->url }}" class="ms-0">{{ $product->original_product->name }}</a>
                                                <span @class(['small', 'text-danger' => $product->isOutOfStock(), 'text-success' => ! $product->isOutOfStock()])>
                                                    @if ($product->isOutOfStock())
                                                        ({{ __('Out of stock') }})
                                                    @else
                                                        ({{ __('In stock') }})
                                                    @endif
                                                </span>
                                            </div>

                                            @if (is_plugin_active('marketplace') && $product->original_product->store->id)
                                                <div class="small">
                                                    <span>{{ __('Vendor:') }}</span>
                                                    <a href="{{ $product->original_product->store->url }}" class="fw-medium">{{ $product->original_product->store->name }}</a>
                                                </div>
                                            @endif

                                            <div class="small">{{ $cartItem->options['attributes'] ?? '' }}</div>

                                            @if (!empty($cartItem->options['description']))
                                                <div class="small text-muted">
                                                    <strong>{{ __('Notes:') }}</strong> {{ $cartItem->options['description'] }}
                                                </div>
                                            @endif

                                            @if (EcommerceHelper::isEnabledProductOptions() && !empty($cartItem->options['options']))
                                                {!! render_product_options_html($cartItem->options['options'], $product->price()->getPrice()) !!}
                                            @endif

                                            {!! apply_filters('ecommerce_quote_after_item_content', null, $cartItem) !!}
                                        </td>
                                        <td data-bb-value="quote-product-price-text" class="cart-price align-middle">
                                            @include(EcommerceHelper::viewPath('includes.product-price'))
                                        </td>
                                        <td data-bb-value="quote-product-quantity" class="cart-quantity align-middle">
                                            @include(EcommerceHelper::viewPath('includes.cart-quantity'))
                                        </td>
                                        <td data-bb-value="quote-product-total-price" class="cart-total align-middle bb-product-price">
                                            <span class="bb-product-price-text fw-bold">{{ format_price($cartItem->price * $cartItem->qty) }}</span>
                                        </td>
                                        <td class="cart-action align-middle">
                                            <a
                                                class="btn btn-danger btn-icon"
                                                data-url="{{ route('public.quote.remove', $cartItem->rowId) }}"
                                                data-bb-toggle="remove-from-quote"
                                                {!! EcommerceHelper::jsAttributes('remove-from-quote', $product, ['data-product-quantity' => $cartItem->qty]) !!}
                                            >
                                                <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path
                                                        fill-rule="evenodd"
                                                        clip-rule="evenodd"
                                                        d="M9.53033 1.53033C9.82322 1.23744 9.82322 0.762563 9.53033 0.46967C9.23744 0.176777 8.76256 0.176777 8.46967 0.46967L5 3.93934L1.53033 0.46967C1.23744 0.176777 0.762563 0.176777 0.46967 0.46967C0.176777 0.762563 0.176777 1.23744 0.46967 1.53033L3.93934 5L0.46967 8.46967C0.176777 8.76256 0.176777 9.23744 0.46967 9.53033C0.762563 9.82322 1.23744 9.82322 1.53033 9.53033L5 6.06066L8.46967 9.53033C8.76256 9.82322 9.23744 9.82322 9.53033 9.53033C9.82322 9.23744 9.82322 8.76256 9.53033 8.46967L6.06066 5L9.53033 1.53033Z"
                                                        fill="currentColor"
                                                    />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </x-core::form>
                </div>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="card p-4">
                        <div class="cart-checkout-top d-flex align-items-center justify-content-between pb-2 border-bottom mb-2">
                            <span class="cart-checkout-top-title fw-bold">{{ __('Subtotal') }}</span>
                            <span data-bb-value="quote-subtotal" class="cart-checkout-top-price fw-bold">{{ format_price(Cart::instance('quote')->rawSubTotal()) }}</span>
                        </div>
                        <div class="cart-checkout-total d-flex align-items-center justify-content-between mt-3 mb-0">
                            <span class="fw-bold mb-1">{{ __('Total') }}</span>
                            <span data-bb-value="quote-total" class="fw-bold">{{ format_price(Cart::instance('quote')->rawSubTotal()) }}</span>
                        </div>
                        <small class="small">{{ __('(Taxes and shipping fees not included)') }}</small>
                        <div class="cart-checkout-proceed mt-3">
                            <a href="{{ route('public.products') }}" class="cart-checkout-btn w-100 btn btn-primary">
                                {{ __('Continue Shopping') }}
                            </a>
                        </div>
                        <form method="POST" action="{{ route('public.quote.destroy') }}" class="mt-2">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('{{ __('Are you sure you want to clear the quote basket?') }}')">
                                {{ __('Clear Quote Basket') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-5">
                <svg width="120" height="120" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="mb-3">
                    <path d="M9 2L4.5 6.5L9 11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 8.5L4.5 6.5L3 4.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.3"/>
                    <path d="M12 8V12L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3>{{ __('Your quote basket is empty') }}</h3>
                <p class="text-muted">{{ __('Start adding products to get a quote!') }}</p>
                <a href="{{ route('public.products') }}" class="btn btn-primary mt-3">
                    {{ __('Browse Products') }}
                </a>
            </div>
        @endif
    </div>
</section>
