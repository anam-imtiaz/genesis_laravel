<?php

namespace Botble\Ecommerce\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteProduct extends BaseModel
{
    protected $table = 'ec_quote_products';

    protected $fillable = [
        'quote_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'options',
    ];

    protected $casts = [
        'options' => 'json',
        'price' => 'float',
        'total' => 'float',
        'quantity' => 'int',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
