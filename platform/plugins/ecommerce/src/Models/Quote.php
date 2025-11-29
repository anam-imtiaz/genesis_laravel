<?php

namespace Botble\Ecommerce\Models;

use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Botble\Ecommerce\Enums\OrderAddressTypeEnum;
use Botble\Ecommerce\Enums\OrderCancellationReasonEnum;
use Botble\Ecommerce\Enums\QuoteStatusEnum;
use Botble\Ecommerce\Enums\ShippingMethodEnum;
use Botble\Ecommerce\Enums\ShippingStatusEnum;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Customer;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection as IlluminateCollection;
use Illuminate\Support\Facades\DB;

class Quote extends BaseModel
{
    protected $table = 'ec_quotes';

    protected $fillable = [
        'customer_id',
        'store_id',
        'assigned_to_id',
        'status',
        'description',
        'code',
        'total',
        'price',
    ];

    protected $casts = [
        'status' => QuoteStatusEnum::class,
    ];

    public static function generateUniqueCode(): string
    {
        $nextInsertId = BaseModel::determineIfUsingUuidsForId() ? static::query()->count() + 1 : static::query()->max(
            'id'
        ) + 1;

        do {
            $code = get_order_code($nextInsertId);
            $nextInsertId++;
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    protected static function booted(): void
    {
        self::deleted(function (Quote $quote): void {
            $quote->products()->delete();
        });

        static::creating(fn (Quote $quote) => $quote->code = static::generateUniqueCode());
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault();
    }

    public function user(): BelongsTo
    {
        return $this->customer(); // Alias for backward compatibility
    }
    
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id')->withDefault();
    }
    
    public function store(): BelongsTo
    {
        if (is_plugin_active('marketplace')) {
            return $this->belongsTo(\Botble\Marketplace\Models\Store::class, 'store_id')->withDefault();
        }
        return $this->belongsTo(\stdClass::class, 'store_id'); // Dummy relation if marketplace not active
    }
    
    public function getQuantityAttribute(): int
    {
        return (int) $this->products->sum('quantity');
    }
    
    public function getTotalAttribute(): float
    {
        return (float) $this->products->sum('total');
    }
    
    public function getPriceAttribute(): float
    {
        return $this->total;
    }


    public function products(): HasMany
    {
        return $this->hasMany(QuoteProduct::class, 'quote_id')->with(['product']);
    }    

    public function getQuoteProducts(): IlluminateCollection
    {
        $productsIds = $this->products->pluck('product_id')->all();

        if (empty($productsIds)) {
            return collect();
        }

        return get_products([
            'condition' => [
                ['ec_products.id', 'IN', $productsIds],
            ],
            'select' => [
                'ec_products.id',
                'ec_products.images',
                'ec_products.name',
                'ec_products.price',
                'ec_products.sale_price',
                'ec_products.sale_type',
                'ec_products.start_date',
                'ec_products.end_date',
                'ec_products.sku',
                'ec_products.order',
                'ec_products.created_at',
                'ec_products.is_variation',
            ],
            'with' => [
                'variationProductAttributes',
            ],
        ]);
    }
}
