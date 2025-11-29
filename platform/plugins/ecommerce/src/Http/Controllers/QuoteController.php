<?php

namespace Botble\Ecommerce\Http\Controllers;

use Botble\ACL\Models\User;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\EmailHandler;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Supports\Breadcrumb;
use Botble\Ecommerce\Cart\CartItem;
use Botble\Ecommerce\Enums\OrderHistoryActionEnum;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Enums\QuoteStatusEnum;
use Botble\Ecommerce\Enums\ShippingCodStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Enums\ShippingStatusEnum;
use Botble\Ecommerce\Events\OrderCreated;
use Botble\Ecommerce\Events\ProductQuantityUpdatedEvent;
use Botble\Ecommerce\Facades\Cart;
use Botble\Ecommerce\Facades\Discount;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\InvoiceHelper;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Http\Requests\AddressRequest;
use Botble\Ecommerce\Http\Requests\ApplyCouponRequest;
use Botble\Ecommerce\Http\Requests\CreateOrderRequest;
use Botble\Ecommerce\Http\Requests\CreateShipmentRequest;
use Botble\Ecommerce\Http\Requests\MarkOrderAsCompletedRequest;
use Botble\Ecommerce\Http\Requests\RefundRequest;
use Botble\Ecommerce\Http\Requests\UpdateOrderRequest;
use Botble\Ecommerce\Http\Resources\CartItemResource;
use Botble\Ecommerce\Http\Resources\CustomerAddressResource;
use Botble\Ecommerce\Models\Customer;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\OrderAddress;
use Botble\Ecommerce\Models\OrderHistory;
use Botble\Ecommerce\Models\OrderProduct;
use Botble\Ecommerce\Models\OrderTaxInformation;
use Botble\Ecommerce\Models\Product;
use Botble\Ecommerce\Models\Shipment;
use Botble\Ecommerce\Models\ShipmentHistory;
use Botble\Ecommerce\Models\StoreLocator;
use Botble\Ecommerce\Services\CreatePaymentForOrderService;
use Botble\Ecommerce\Services\HandleApplyCouponService;
use Botble\Ecommerce\Services\HandleApplyPromotionsService;
use Botble\Ecommerce\Services\HandleShippingFeeService;
use Botble\Ecommerce\Services\HandleTaxService;
use Botble\Ecommerce\Models\Quote;
use Botble\Ecommerce\Tables\OrderIncompleteTable;
use Botble\Ecommerce\Tables\OrderTable;
use Botble\Ecommerce\Tables\QuoteTable;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Enums\PaymentStatusEnum;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuoteController extends BaseController
{
    public function __construct(
        protected HandleShippingFeeService $shippingFeeService,
        protected HandleApplyCouponService $handleApplyCouponService,
        protected HandleApplyPromotionsService $applyPromotionsService
    ) {
    }

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/ecommerce::quote.menu'), route('quotes.index'));
    }

    public function index(QuoteTable $dataTable)
    {
        $this->pageTitle(trans('plugins/ecommerce::quote.menu'));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/quote.css'])
            ->addScriptsDirectly('vendor/core/plugins/ecommerce/js/quote.js', 'footer', ['data-quote-script' => 'true']);

        return $dataTable->renderTable();
    }

    public function edit(Quote $quote)
    {
        $this->pageTitle(trans('plugins/ecommerce::quote.edit_quote', ['code' => $quote->code]));

        Assets::addStylesDirectly(['vendor/core/plugins/ecommerce/css/ecommerce.css']);

        $quote->load(['customer', 'products', 'products.product', 'assignedTo']);

        $statuses = QuoteStatusEnum::labels();
        $users = User::query()
            ->orderBy('first_name')
            ->get()
            ->mapWithKeys(function ($user) {
                $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                return [$user->id => $name ?: $user->email];
            })
            ->all();

        return view('plugins/ecommerce::quotes.edit', compact('quote', 'statuses', 'users'));
    }

    public function update(Quote $quote, Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'assigned_to_id' => 'nullable|exists:users,id',
        ]);

        $oldStatus = $quote->status->getValue();
        $newStatus = $request->input('status');

        $quote->fill($request->only(['status', 'description', 'assigned_to_id']));
        $quote->save();

        // Send email to customer if status changed to rejected
        if ($newStatus === QuoteStatusEnum::REJECTED && $oldStatus !== QuoteStatusEnum::REJECTED) {
            $quote->load(['customer', 'products', 'products.product']);
            $this->sendQuoteRejectionEmail($quote);
        }

        event(new UpdatedContentEvent('quote', $request, $quote));

        return $this
            ->httpResponse()
            ->setPreviousUrl(route('quotes.index'))
            ->withUpdatedSuccessMessage();
    }

    protected function sendQuoteRejectionEmail(Quote $quote): void
    {
        if (!$quote->customer || !$quote->customer->email) {
            return;
        }

        try {
            $mailer = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME);

            // Set email variables
            $mailer->setVariableValues([
                'customer_name' => $quote->customer->name,
                'customer_email' => $quote->customer->email,
                'quote_code' => $quote->code,
                'quote_id' => $quote->code,
                'quote_description' => $quote->description ?? '',
                'quote_total' => format_price($quote->total),
                'quote_link' => route('public.quote.basket'),
            ]);

            $mailer->sendUsingTemplate('quote_rejected', $quote->customer->email);
        } catch (Exception $exception) {
            BaseHelper::logError($exception);
        }
    }

    public function destroy(Quote $quote)
    {
        return DeleteResourceAction::make($quote)
            ->afterDeleting(function ($action) {
                $action->getHttpResponse()->setData([
                    'reload_table' => true,
                    'table_id' => 'table_quotes_table',
                ]);
            });
    }

}
