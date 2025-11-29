@extends(BaseHelper::getAdminMasterLayoutTemplate())

@section('content')
    <div class="max-width-1200">
        <form action="{{ route('quotes.update', $quote->id) }}" method="POST">
            @csrf
            @method('PUT')

            <x-core::card>
                <x-core::card.header>
                    <x-core::card.title>
                        {{ trans('plugins/ecommerce::quote.edit_quote', ['code' => $quote->code]) }}
                    </x-core::card.title>
                </x-core::card.header>

                <x-core::card.body>
                    <div class="row">
                        <div class="col-md-6">
                            <x-core::form.text-input
                                id="code"
                                name="code"
                                :value="$quote->code"
                                :label="trans('plugins/ecommerce::quote.code')"
                                disabled
                            />
                        </div>

                        <div class="col-md-6">
                            <x-core::form.text-input
                                id="customer"
                                name="customer"
                                :value="$quote->customer ? $quote->customer->name : 'N/A'"
                                :label="trans('plugins/ecommerce::quote.customer')"
                                disabled
                            />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <x-core::form.select
                                id="status"
                                name="status"
                                :options="$statuses"
                                :value="$quote->status->getValue()"
                                :label="trans('plugins/ecommerce::quote.status')"
                                required
                            />
                        </div>

                       
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <x-core::form.textarea
                                id="description"
                                name="description"
                                :value="$quote->description"
                                :label="trans('plugins/ecommerce::quote.description')"
                                rows="4"
                            />
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h5>{{ trans('plugins/ecommerce::quote.products') }}</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ trans('plugins/ecommerce::products.name') }}</th>
                                            <th>{{ trans('plugins/ecommerce::quote.quantity') }}</th>
                                            <th>{{ trans('plugins/ecommerce::products.price') }}</th>
                                            <th>{{ trans('plugins/ecommerce::quote.total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($quote->products as $quoteProduct)
                                            <tr>
                                                <td>{{ $quoteProduct->product ? $quoteProduct->product->name : 'N/A' }}</td>
                                                <td>{{ $quoteProduct->quantity }}</td>
                                                <td>{{ format_price($quoteProduct->price) }}</td>
                                                <td>{{ format_price($quoteProduct->total) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center">
                                                    {{ trans('core/base::tables.no_data') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">
                                                {{ trans('plugins/ecommerce::quote.total') }}:
                                            </th>
                                            <th>{{ format_price($quote->total) }}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </x-core::card.body>

                <x-core::card.footer>
                    <x-core::button
                        type="submit"
                        color="primary"
                    >
                        {{ trans('core/base::forms.save') }}
                    </x-core::button>

                    <x-core::button
                        tag="a"
                        :href="route('quotes.index')"
                    >
                        {{ trans('core/base::forms.cancel') }}
                    </x-core::button>
                </x-core::card.footer>
            </x-core::card>
        </form>
    </div>
@endsection

