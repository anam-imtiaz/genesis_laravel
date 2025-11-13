<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\Base\Facades\Assets;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Http\Requests\CreateOrderRequest;
use Botble\Ecommerce\Http\Requests\UpdateOrderRequest;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Quote;
use Botble\Ecommerce\Tables\BuyEnquiryTable;
use Botble\Payment\Enums\PaymentStatusEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BuyEnquiryController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ecommerce::quote.menu'), route('quotes.index'))
            ->add(trans('plugins/ecommerce::quote.buy_enquiry'), route('buy-enquiry.index'));
    }

    public function index(BuyEnquiryTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::quote.buy_enquiry'));

        return $dataTable->renderTable();
    }

    public function create()
    {
        $this->pageTitle(trans('plugins/ecommerce::quote.create_buy_enquiry'));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly(['vendor/core/plugins/ecommerce/js/ecommerce.js']);

        $customers = Customer::query()
            ->where('is_verified', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $products = Product::query()
            ->where('status', 'published')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return view('plugins/ecommerce::buy-enquiry.create', compact('customers', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:ec_customers,id',
            'product_id' => 'required|exists:ec_products,id',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $quote = Quote::create([
                'customer_id' => $request->input('customer_id'),
                'product_id' => $request->input('product_id'),
                'quantity' => $request->input('quantity'),
                'description' => $request->input('description'),
                'status' => 'draft',
                'price' => 0, // Will be calculated later
                'total' => 0, // Will be calculated later
            ]);

            DB::commit();

            return $this
                ->httpResponse()
                ->setPreviousUrl(route('buy-enquiry.index'))
                ->setNextUrl(route('buy-enquiry.edit', $quote->id))
                ->withCreatedSuccessMessage();
        } catch (Exception $exception) {
            DB::rollBack();

            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function edit(Quote $buyEnquiry)
    {
        $this->pageTitle(trans('plugins/ecommerce::quote.edit_buy_enquiry'));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css'])
            ->addScriptsDirectly(['vendor/core/plugins/ecommerce/js/ecommerce.js']);

        $customers = Customer::query()
            ->where('is_verified', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        $products = Product::query()
            ->where('status', 'published')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();

        return view('plugins/ecommerce::buy-enquiry.edit', compact('buyEnquiry', 'customers', 'products'));
    }

    public function update(Request $request, Quote $buyEnquiry)
    {
        $request->validate([
            'customer_id' => 'required|exists:ec_customers,id',
            'product_id' => 'required|exists:ec_products,id',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
        ]);

        try {
            DB::beginTransaction();

            $buyEnquiry->update([
                'customer_id' => $request->input('customer_id'),
                'product_id' => $request->input('product_id'),
                'quantity' => $request->input('quantity'),
                'description' => $request->input('description'),
                'price' => $request->input('price', 0),
                'status' => $request->input('status'),
                'total' => $request->input('price', 0) * $request->input('quantity'),
            ]);

            DB::commit();

            return $this
                ->httpResponse()
                ->setPreviousUrl(route('buy-enquiry.index'))
                ->withUpdatedSuccessMessage();
        } catch (Exception $exception) {
            DB::rollBack();

            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function destroy(Quote $buyEnquiry)
    {
        try {
            $buyEnquiry->delete();

            return $this
                ->httpResponse()
                ->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
