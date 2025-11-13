<?php

namespace Botble\Ecommerce\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Ecommerce\Enums\OrderStatusEnum;
use Botble\Ecommerce\Models\Quote;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\CreatedAtColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\StatusColumn;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BuyEnquiryTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Quote::class)
            ->addColumns([
                IdColumn::make(),
                Column::make('code')
                    ->title(trans('plugins/ecommerce::quote.code'))
                    ->alignLeft(),
                Column::make('customer_id')
                    ->title(trans('plugins/ecommerce::quote.customer'))
                    ->alignLeft(),
                Column::make('product_id')
                    ->title(trans('plugins/ecommerce::quote.product'))
                    ->alignLeft(),
                Column::make('quantity')
                    ->title(trans('plugins/ecommerce::quote.quantity'))
                    ->alignLeft(),
                Column::make('price')
                    ->title(trans('plugins/ecommerce::quote.price'))
                    ->alignLeft(),
                Column::make('total')
                    ->title(trans('plugins/ecommerce::quote.total'))
                    ->alignLeft(),
                StatusColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->addActions([
                EditAction::make()->route('buy-enquiry.edit'),
                DeleteAction::make()->route('buy-enquiry.destroy'),
            ])
            ->addBulkAction(DeleteBulkAction::make())
            ->queryUsing(function (Builder $query): void {
                $query
                    ->with(['user', 'products'])
                    ->select([
                        'id',
                        'code',
                        'customer_id',
                        'product_id',
                        'quantity',
                        'price',
                        'total',
                        'status',
                        'created_at',
                    ]);
            })
            ->defaultSortColumn('created_at', 'desc');
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('code', function (Quote $item) {
                return Html::link(route('buy-enquiry.edit', $item->getKey()), $item->code);
            })
            ->editColumn('customer_id', function (Quote $item) {
                return $item->user ? $item->user->name : 'N/A';
            })
            ->editColumn('product_id', function (Quote $item) {
                return $item->products->first()?->product?->name ?? 'N/A';
            })
            ->editColumn('price', function (Quote $item) {
                return format_price($item->price);
            })
            ->editColumn('total', function (Quote $item) {
                return format_price($item->total);
            })
            ->editColumn('status', function (Quote $item) {
                return $item->status->toHtml();
            })
            ->editColumn('created_at', function (Quote $item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->filter(function ($query) {
                if ($keyword = $this->request->input('search.value')) {
                    return $query
                        ->where('code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('user', function ($query) use ($keyword) {
                            return $query->where('name', 'like', '%' . $keyword . '%');
                        });
                }

                return $query;
            });

        return $this->toJson($data);
    }

    public function buttons(): array
    {
        return $this->addCreateButton(route('buy-enquiry.create'), 'buy-enquiry.create');
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('buy-enquiry.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
            'status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => OrderStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', OrderStatusEnum::values()),
            ],
        ];
    }
}
