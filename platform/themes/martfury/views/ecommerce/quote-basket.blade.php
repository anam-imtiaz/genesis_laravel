<div class="ps-section--shopping ps-shopping-cart pt-40">
    <div class="container">
        <div class="ps-section__header">
            <h1>{{ __('Quote Basket') }}</h1>
        </div>
        <div class="ps-section__content">
            @php
                $quoteCart = Cart::instance('quote');
            @endphp
            @if ($quoteCart->isNotEmpty() && $quoteCart->count() > 0)
                <form class="form--shopping-cart" method="post" action="{{ route('public.quote.update') }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table ps-table--shopping-cart">
                            <thead>
                            <tr>
                                <th>{{ __("Product's name") }}</th>
                                <th>{{ __('Price') }}</th>
                                <th>{{ __('Quantity') }}</th>
                                <th>{{ __('Total') }}</th>
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
                                        <td>
                                            <input type="hidden" name="items[{{ $key }}][rowId]" value="{{ $cartItem->rowId }}">
                                            <div class="ps-product--cart">
                                                <div class="ps-product__thumbnail">
                                                    <a href="{{ $product->original_product->url }}">
                                                        {!! RvMedia::image($cartItem->options['image'] ?? null, $product->original_product->name, 'thumb') !!}
                                                    </a>
                                                </div>
                                                <div class="ps-product__content">
                                                    <a href="{{ $product->original_product->url }}">{{ $product->original_product->name }}  @if ($product->isOutOfStock()) <span class="stock-status-label">({!! $product->stock_status_html !!})</span> @endif</a>
                                                    @if (is_plugin_active('marketplace') && isset($product->original_product->store) && $product->original_product->store->id)
                                                        <p class="d-block mb-0 sold-by"><small>{{ __('Sold by') }}: <a href="{{ $product->original_product->store->url }}">{{ $product->original_product->store->name }}</a></small></p>
                                                    @endif

                                                    <p class="mb-0"><small>{{ $cartItem->options['attributes'] ?? '' }}</small></p>

                                                    @if (!empty($cartItem->options['description']))
                                                        <p class="mb-0"><small><strong>{{ __('Notes:') }}</strong> {{ $cartItem->options['description'] }}</small></p>
                                                    @endif

                                                    @if (!empty($cartItem->options['options']))
                                                        {!! render_product_options_info($cartItem->options['options'], $product, true) !!}
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="price text-center">
                                            <div class="product__price @if ($product->front_sale_price != $product->price) sale @endif">
                                                <span>{{ format_price($cartItem->price) }}</span>
                                                @if ($product->front_sale_price != $product->price)
                                                    <small><del>{{ format_price($product->price) }}</del></small>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-group--number product__qty">
                                                <button class="up">+</button>
                                                <button class="down">-</button>
                                                <input type="number" class="form-control qty-input" min="1" value="{{ $cartItem->qty }}" title="{{ __('Qty') }}" name="items[{{ $key }}][qty]">
                                            </div>
                                        </td>
                                        <td class="text-center">{{ format_price($cartItem->price * $cartItem->qty) }}</td>
                                        <td>
                                            <a href="#" data-url="{{ route('public.quote.remove', $cartItem->rowId) }}" class="remove-quote-item" data-bb-toggle="remove-from-quote"><i class="icon-cross"></i></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            @else
                <div class="text-center py-5">
                    <p class="text-center">{{ __('Your quote basket is empty!') }}</p>
                    <a href="{{ route('public.products') }}" class="btn btn-primary mt-3">{{ __('Browse Products') }}</a>
                </div>
            @endif
        </div>
        @if ($quoteCart->isNotEmpty() && $quoteCart->count() > 0)
            <div class="ps-section__footer">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12">
                        <div class="ps-block--shopping-total">
                            <div class="ps-block__header">
                                <p>{{ __('Subtotal') }} <span> {{ format_price($quoteCart->rawSubTotal()) }}</span></p>
                            </div>
                            <div class="ps-block__content">
                                <h3>{{ __('Total') }} <span>{{ format_price($quoteCart->rawSubTotal()) }}</span></h3>
                                <p><small>({{ __('Taxes and shipping fees not included') }})</small></p>
                            </div>
                        </div>
                        <a class="ps-btn btn-cart-button-action" href="{{ route('public.products') }}"><i class="icon-arrow-left"></i> {{ __('Back to Shop') }}</a>
                        <div class="d-inline-block" style="float: right;">
                            @if(auth('customer')->check())
                                <form method="POST" action="{{ route('public.quote.submit') }}" id="submit-quote-form" class="d-inline-block">
                                    @csrf
                                    <button type="submit" style="background-color: red; color: #fff;" class="ps-btn ps-btn--black btn-cart-button-action" id="submit-quote-btn">
                                        <img src="{{ Theme::asset()->url('img/quotation-icon.png') }}" alt="{{ __('Submit Request for Quote') }}" style="width: 16px; height: 16px; vertical-align: middle; margin-right: 5px;"> {{ __('Submit Request for Quote') }}
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('customer.login') }}" class="ps-btn ps-btn--black btn-cart-button-action" style="background-color: red; color: #fff;">
                                    <i class="icon-user"></i> {{ __('Login to Submit Quote') }}
                                </a>
                            @endif
                            
                            <form method="POST" action="{{ route('public.quote.destroy') }}" style="display: inline-block;">
                                @csrf
                                <button type="submit" class="ps-btn ps-btn--outline btn-cart-button-action" onclick="return confirm('{{ __('Are you sure you want to clear the quote basket?') }}')">
                                    {{ __('Clear Quote Basket') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@push('footer')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitForm = document.getElementById('submit-quote-form');
    const submitBtn = document.getElementById('submit-quote-btn');
    
    if (submitForm) {
        submitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            @if(!auth('customer')->check())
                if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                    window.Botble.showNotice('error', '{{ __('Please login to submit quote request.') }}');
                } else {
                    alert('{{ __('Please login to submit quote request.') }}');
                }
                window.location.href = '{{ route('customer.login') }}';
                return;
            @endif
            
            if (!confirm('{{ __('Are you sure you want to submit this quote request?') }}')) {
                return;
            }
            
            const originalHTML = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="icon-spinner icon-spin"></i> {{ __('Submitting...') }}';
            submitBtn.disabled = true;
            
            const formData = new FormData(submitForm);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            fetch(submitForm.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    // Check if login is required
                    if (data.data && data.data.login_required) {
                        if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                            window.Botble.showNotice('error', data.message);
                        } else {
                            alert(data.message);
                        }
                        // Redirect to login page
                        if (data.data.login_url) {
                            window.location.href = data.data.login_url;
                        } else {
                            window.location.href = '{{ route('customer.login') }}';
                        }
                        return;
                    }
                    
                    if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                        window.Botble.showNotice('error', data.message);
                    } else {
                        alert(data.message);
                    }
                } else {
                    if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                        window.Botble.showNotice('success', data.message);
                    } else {
                        alert(data.message);
                    }
                    
                    // Update quote count
                    if (data.data && data.data.count !== undefined) {
                        const quoteCountElements = document.querySelectorAll('.btn-quote span i, .btn-quote span');
                        quoteCountElements.forEach(el => {
                            if (el.tagName === 'I') {
                                el.textContent = data.data.count;
                            } else if (el.querySelector('i')) {
                                el.querySelector('i').textContent = data.data.count;
                            }
                        });
                    }
                    
                    // Update quote dropdown
                    if (data.data && data.data.html) {
                        document.querySelectorAll('.btn-quote').forEach(btn => {
                            const cartMini = btn.closest('.ps-cart--mini');
                            if (cartMini) {
                                const dropdown = cartMini.querySelector('.ps-cart--mobile');
                                if (dropdown) {
                                    dropdown.innerHTML = data.data.html;
                                }
                            }
                        });
                    }
                    
                    // Reload page after a short delay to show empty basket
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                    window.Botble.showNotice('error', '{{ __('Error submitting quote request. Please try again.') }}');
                } else {
                    alert('{{ __('Error submitting quote request. Please try again.') }}');
                }
            })
            .finally(() => {
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>
@endpush
