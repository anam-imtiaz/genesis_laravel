{!! Theme::partial('header') !!}

<div id="homepage-1">
    {!! Theme::content() !!}
</div>

{!! Theme::partial('footer') !!}

@push('footer')
<script src="{{ asset('vendor/core/plugins/ecommerce/js/add-to-quote.js') }}?v={{ time() }}"></script>
@endpush
