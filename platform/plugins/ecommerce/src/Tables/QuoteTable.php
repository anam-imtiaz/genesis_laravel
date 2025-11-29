<?php

namespace Botble\Ecommerce\Tables;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\Html;
use Botble\Ecommerce\Enums\QuoteStatusEnum;
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

class QuoteTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Quote::class)
           
            ->setOption('class', 'quotes-table')
            ->addColumns([
                IdColumn::make(),
                Column::make('code')
                    ->title(trans('plugins/ecommerce::quote.code'))
                    ->alignLeft(),
                Column::make('customer_id')
                    ->title(trans('plugins/ecommerce::quote.customer'))
                    ->alignLeft(),
                Column::make('products')
                    ->title(trans('plugins/ecommerce::quote.products'))
                    ->alignLeft(),
                Column::make('quantity')
                    ->title(trans('plugins/ecommerce::quote.quantity'))
                    ->alignLeft(),
                Column::make('total')
                    ->title(trans('plugins/ecommerce::quote.total'))
                    ->alignLeft(),
                StatusColumn::make(),
                CreatedAtColumn::make(),
            ])
            ->addActions([
                EditAction::make()->route('quotes.edit'),
                DeleteAction::make()
                    ->route('quotes.destroy') // Ensure the correct route is used
                    ->label('Remove Item') // Change the button text
                    ->icon('fa fa-trash-alt') // Change the icon
                    ->permission('quotes.destroy') // Change the permission requirement
                    ->attributes([
                        'class' => 'btn btn-danger delete-item-custom', // Add custom CSS class
                        'data-toggle' => 'tooltip',
                        'data-original-title' => 'Delete this item', // Custom tooltip
                        'data-parent-table' => $this->getTableId(),
                    ])
                //DeleteAction::make()->route('quotes.destroy'),
            ])
            ->addBulkAction(DeleteBulkAction::make())
            ->queryUsing(function (Builder $query): void {
                $query
                    ->with(['customer', 'products', 'products.product'])
                    ->select([
                        'id',
                        'code',
                        'customer_id',
                        'status',
                        'description',
                        'created_at',
                    ]);
            });
       
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('code', function (Quote $item) {
                return Html::link(route('quotes.edit', $item->getKey()), $item->code);
            })
            ->editColumn('customer_id', function (Quote $item) {
                return $item->customer ? $item->customer->name : 'N/A';
            })
            ->editColumn('products', function (Quote $item) {
                $products = $item->products;
                if ($products->isEmpty()) {
                    return 'N/A';
                }
                $productNames = $products->take(3)->map(function ($quoteProduct) {
                    return $quoteProduct->product ? $quoteProduct->product->name : 'N/A';
                })->implode(', ');
                if ($products->count() > 3) {
                    $productNames .= ' (+' . ($products->count() - 3) . ' more)';
                }
                return $productNames;
            })
            ->editColumn('quantity', function (Quote $item) {
                return $item->quantity;
            })
            ->editColumn('total', function (Quote $item) {
                return format_price($item->total);
            })
            ->editColumn('status', function (Quote $item) {
                // Use the enum's toHtml method which handles all statuses properly
                return $item->status->toHtml();
            })
            ->editColumn('created_at', function (Quote $item) {
                return BaseHelper::formatDate($item->created_at);
            })
            ->filter(function ($query) {
                if ($keyword = $this->request->input('search.value')) {
                    return $query
                        ->where('code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('customer', function ($query) use ($keyword) {
                            return $query->where('name', 'like', '%' . $keyword . '%');
                        });
                }

                return $query;
            });

        return $this->toJson($data);
    }

        public function buttons(): array
        {
            return [];
        }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('quotes.destroy'),
        ];
    }

    public function getBulkChanges(): array
    {
        return [
           
        ];
    }

    /**
     * Get the table ID
     *
     * @return string
     */
    public function getTableId(): string
    {
        return $this->getOption('id');
    }
}
