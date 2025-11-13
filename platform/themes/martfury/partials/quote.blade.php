<div class="ps-cart__content">
    @php
        $quoteCart = Cart::instance('quote');
    @endphp
    @if ($quoteCart->count() > 0)
        <div class="ps-cart__items">
            <div class="ps-cart__items__body">
                @php
                    $cartContent = $quoteCart->content();
                    $productIds = array_unique($cartContent->pluck('id')->toArray());
                    $products = collect();
                    
                    if (!empty($productIds)) {
                        $productsData = \Botble\Ecommerce\Models\Product::query()
                            ->whereIn('id', $productIds)
                            ->with(['variationInfo', 'variationInfo.configurableProduct'])
                            ->get();
                            
                        foreach ($cartContent as $cartItem) {
                            $product = $productsData->firstWhere('id', $cartItem->id);
                            if ($product) {
                                $products->push($product);
                            }
                        }
                    }
                @endphp
                @if ($products->isNotEmpty())
                    @foreach($quoteCart->content() as $key => $cartItem)
                        @php
                            $product = $products->firstWhere('id', $cartItem->id);
                        @endphp

                        @if (!empty($product))
                            @php
                                $originalProduct = $product->original_product ?? $product;
                            @endphp
                            <div class="ps-product--cart-mobile">
                                <div class="ps-product__thumbnail">
                                    <a href="{{ $originalProduct->url }}">
                                        {!! RvMedia::image($cartItem->options['image'] ?? null, $originalProduct->name, 'thumb') !!}
                                    </a>
                                </div>
                                <div class="ps-product__content">
                                    <a class="ps-product__remove remove-quote-item" href="#" data-url="{{ route('public.quote.remove', $cartItem->rowId) }}"><i class="icon-cross"></i></a>
                                    <a href="{{ $originalProduct->url }}"> {{ $originalProduct->name }}  @if ($product->isOutOfStock()) <span class="stock-status-label">({!! $product->stock_status_html !!})</span> @endif</a>
                                    <p class="mb-0">
                                        <small>
                                            <span class="d-inline-block">{{ $cartItem->qty }} x</span> <span class="quote-price">{{ format_price($cartItem->price) }} @if ($product->front_sale_price != $product->price)
                                                    <small><del>{{ format_price($product->price) }}</del></small>
                                                @endif
                                            </span>
                                        </small>
                                    </p>
                                    <p class="mb-0"><small><small>{{ $cartItem->options['attributes'] ?? '' }}</small></small></p>

                                    @if (!empty($cartItem->options['description']))
                                        <p class="mb-0"><small><strong>{{ __('Notes:') }}</strong> {{ $cartItem->options['description'] }}</small></p>
                                    @endif

                                    @if (!empty($cartItem->options['options']))
                                        {!! render_product_options_info($cartItem->options['options'], $product, true) !!}
                                    @endif

                                    @if (is_plugin_active('marketplace') && isset($originalProduct->store) && $originalProduct->store->id)
                                        <p class="d-block mb-0 sold-by">
                                            <small>{{ __('Sold by') }}: <a href="{{ $originalProduct->store->url }}">{{ $originalProduct->store->name }}</a>
                                            </small>
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>
        </div>
        <div class="ps-cart__footer">
            <h3>{{ __('Sub Total') }}:<strong>{{ format_price($quoteCart->rawSubTotal()) }}</strong></h3>
            <figure>
                <a class="ps-btn" href="{{ route('public.quote.basket') }}">{{ __('View Quote') }}</a>
            </figure>
        </div>
    @else
        <div class="ps-cart__items ps-cart_no_items">
            <span class="cart-empty-message">{{ __('No products in the quote basket.') }}</span>
        </div>
    @endif
</div>
