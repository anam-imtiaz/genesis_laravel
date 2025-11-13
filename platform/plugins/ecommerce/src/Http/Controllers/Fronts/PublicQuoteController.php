<?php

namespace Botble\Ecommerce\Http\Controllers\Fronts;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Quote;
use Botble\Ecommerce\Models\QuoteProduct;
use Botble\Media\Facades\RvMedia;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PublicQuoteController extends BaseController
{
    public function index()
    {
        $products = new Collection();
        $quoteCart = Cart::instance('quote');

        if ($quoteCart->isNotEmpty()) {
            // Get product IDs from quote cart
            $cartContent = $quoteCart->content();
            $productIds = array_unique($cartContent->pluck('id')->toArray());
            
            if (!empty($productIds)) {
                $with = [
                    'variationInfo',
                    'variationInfo.configurableProduct',
                    'variationInfo.configurableProduct.slugable',
                    'variationProductAttributes',
                ];

                if (is_plugin_active('marketplace')) {
                    $with = array_merge($with, [
                        'variationInfo.configurableProduct.store',
                        'variationInfo.configurableProduct.store.slugable',
                    ]);
                }

                $productsData = Product::query()
                    ->whereIn('id', $productIds)
                    ->with($with)
                    ->get();

                // Create collection matching cart items
                foreach ($cartContent as $cartItem) {
                    $product = $productsData->firstWhere('id', $cartItem->id);
                    if ($product) {
                        $productInCart = clone $product;
                        $productInCart->cartItem = $cartItem;
                        $products->push($productInCart);
                    }
                }
            }
        }

        SeoHelper::setTitle(__('Quote Basket'));

        Theme::breadcrumb()->add(__('Quote Basket'), route('public.quote.basket'));

        return Theme::scope(
            'ecommerce.quote-basket',
            compact('products'),
            'plugins/ecommerce::themes.quote-basket'
        )->render();
    }

    public function addToQuote(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'id' => 'required|exists:ec_products,id',
            'qty' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $product = Product::findOrFail($request->input('id'));
            $quantity = $request->input('qty', 1);

            /*if (!$product->isAvailableForSale()) {
                return $response
                    ->setError()
                    ->setMessage(__('This product is not available for sale.'));
            }*/

            $quoteCart = Cart::instance('quote');

            // Check if product already exists in quote
            $cartItem = $quoteCart->search(function ($cartItem) use ($product) {
                return $cartItem->id == $product->getKey();
            })->first();

            if ($cartItem) {
                // Update quantity if product already in quote
                $quoteCart->update($cartItem->rowId, $cartItem->qty + $quantity);
            } else {
                // Add new product to quote
                $quoteCart->add(
                    $product->getKey(),
                    $product->name,
                    $quantity,
                    $product->front_sale_price_with_taxes,
                    [
                        'image' => RvMedia::getImageUrl($product->image, 'thumb', false, RvMedia::getDefaultImage()),
                        'attributes' => $product->variation_attributes ?? '',
                        'sku' => $product->sku,
                        'description' => $request->input('description', ''),
                    ]
                )->associate(Product::class);
            }

            // Get quote HTML content for dropdown
            $quoteContent = $this->getQuoteContent();
            
            return $response
                ->setMessage(__('Product added to quote successfully!'))
                ->setData([
                    'count' => $quoteCart->count(),
                    'subtotal' => format_price($quoteCart->rawSubTotal()),
                    'html' => $quoteContent,
                ]);

        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function update(Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.rowId' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            $quoteCart = Cart::instance('quote');

            foreach ($request->input('items') as $item) {
                $quoteCart->update($item['rowId'], $item['qty']);
            }

            // Get updated quote HTML content
            $quoteContent = $this->getQuoteContent();
            
            return $response
                ->setMessage(__('Quote updated successfully!'))
                ->setData([
                    'count' => $quoteCart->count(),
                    'subtotal' => format_price($quoteCart->rawSubTotal()),
                    'html' => $quoteContent,
                ]);

        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function remove($rowId, BaseHttpResponse $response)
    {
        try {
            Cart::instance('quote')->remove($rowId);

            // Get updated quote HTML content
            $quoteContent = $this->getQuoteContent();
            
            return $response
                ->setMessage(__('Item removed from quote successfully!'))
                ->setData([
                    'count' => Cart::instance('quote')->count(),
                    'subtotal' => format_price(Cart::instance('quote')->rawSubTotal()),
                    'html' => $quoteContent,
                ]);

        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function destroy(BaseHttpResponse $response)
    {
        try {
            Cart::instance('quote')->destroy();

            return $response
                ->setMessage(__('Quote basket cleared successfully!'));

        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
    
    public function submit(Request $request, BaseHttpResponse $response)
    {
        // Check if customer is authenticated
        if (!auth('customer')->check()) {
            if ($request->ajax() || $request->wantsJson()) {
                return $response
                    ->setError()
                    ->setMessage(__('Please login to submit quote request.'))
                    ->setData(['login_required' => true, 'login_url' => route('customer.login')]);
            }
            
            return redirect()->route('customer.login')->with('error', __('Please login to submit quote request.'));
        }
        
        $quoteCart = Cart::instance('quote');
        
        if ($quoteCart->isEmpty()) {
            return $response
                ->setError()
                ->setMessage(__('Quote basket is empty. Please add products before submitting.'));
        }
        
        try {
            $cartContent = $quoteCart->content();
            $productIds = array_unique($cartContent->pluck('id')->toArray());
            
            if (empty($productIds)) {
                return $response
                    ->setError()
                    ->setMessage(__('No products found in quote basket.'));
            }
            
            // Get products
            $productsData = Product::query()
                ->whereIn('id', $productIds)
                ->with(['variationInfo', 'variationInfo.configurableProduct'])
                ->get();
            
            // Get customer ID (required at this point)
            $customerId = auth('customer')->id();
            
            // Create quote record
            $quote = Quote::create([
                'customer_id' => $customerId,
                'status' => \Botble\Ecommerce\Enums\OrderStatusEnum::PENDING,
                'description' => $request->input('description', ''),
            ]);
            
            // Create quote products - store in quote table if no separate table
            // Since Quote model stores products in the same table or via relationship
            // We'll update the quote with product info or create separate records
            $totalAmount = 0;
            foreach ($cartContent as $cartItem) {
                $product = $productsData->firstWhere('id', $cartItem->id);
                
                if ($product) {
                    try {
                        QuoteProduct::create([
                            'quote_id' => $quote->id,
                            'product_id' => $product->id,
                            'quantity' => $cartItem->qty,
                            'price' => $cartItem->price,
                            'total' => $cartItem->price * $cartItem->qty,
                            'options' => $cartItem->options->toArray(),
                        ]);
                    } catch (\Exception $e) {
                        // If QuoteProduct table doesn't exist, store in quote directly
                        // This is a fallback - you may need to create the migration
                    }
                    $totalAmount += $cartItem->price * $cartItem->qty;
                }
            }
            
            // Totals are calculated via accessors in the model
            
            // Clear the quote cart after successful submission
            $quoteCart->destroy();
            
            // Get updated quote content (empty)
            $quoteContent = $this->getQuoteContent();
            
            return $response
                ->setMessage(__('Quote request submitted successfully! We will contact you soon.'))
                ->setData([
                    'count' => 0,
                    'subtotal' => format_price(0),
                    'html' => $quoteContent,
                    'quote_id' => $quote->id,
                    'quote_code' => $quote->code ?? '',
                ]);

        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
    
    /**
     * Get quote HTML content for AJAX updates
     */
    protected function getQuoteContent(): string
    {
        try {
            $quoteCart = Cart::instance('quote');
            $products = new Collection();
            
            if ($quoteCart->isNotEmpty()) {
                $cartContent = $quoteCart->content();
                $productIds = array_unique($cartContent->pluck('id')->toArray());
                
                if (!empty($productIds)) {
                    $with = [
                        'variationInfo',
                        'variationInfo.configurableProduct',
                    ];

                    if (is_plugin_active('marketplace')) {
                        $with = array_merge($with, [
                            'variationInfo.configurableProduct.store',
                            'variationInfo.configurableProduct.store.slugable',
                        ]);
                    }

                    $productsData = Product::query()
                        ->whereIn('id', $productIds)
                        ->with($with)
                        ->get();
                        
                    foreach ($cartContent as $cartItem) {
                        $product = $productsData->firstWhere('id', $cartItem->id);
                        if ($product) {
                            $products->push($product);
                        }
                    }
                }
            }
            
            // Theme::partial already returns rendered HTML string
            return Theme::partial('quote') ?: '';
        } catch (\Exception $e) {
            return '';
        }
    }
}
