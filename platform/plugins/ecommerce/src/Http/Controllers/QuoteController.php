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

        Assets::addStylesDirectly('vendor/core/plugins/ecommerce/css/quote.css');

        return $dataTable->renderTable();
    }

    public function destroy(Quote $quote)
    {
        return DeleteResourceAction::make($quote);
    }

}
