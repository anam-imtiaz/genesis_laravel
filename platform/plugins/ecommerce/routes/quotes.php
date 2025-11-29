<?php

use Botble\Base\Facades\AdminHelper;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\Ecommerce\Http\Controllers', 'prefix' => 'ecommerce'], function (): void {
        Route::group(['prefix' => 'quotes', 'as' => 'quotes.'], function (): void {
            Route::match(['GET', 'POST'], '', ['as' => 'index', 'uses' => 'QuoteController@index']);
            Route::get('{quote}/edit', ['as' => 'edit', 'uses' => 'QuoteController@edit']);
            Route::put('{quote}', ['as' => 'update', 'uses' => 'QuoteController@update']);
            Route::patch('{quote}', ['as' => 'update', 'uses' => 'QuoteController@update']);
            Route::delete('{quote}', ['as' => 'destroy', 'uses' => 'QuoteController@destroy']);
        });
    });
});

Theme::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\Ecommerce\Http\Controllers\Fronts'], function (): void {
        Route::group(['prefix' => 'quote', 'as' => 'public.quote.'], function (): void {
            Route::get('basket', [
                'as' => 'basket',
                'uses' => 'PublicQuoteController@index',
            ]);
            
            Route::post('add-to-quote', [
                'as' => 'add-to-quote',
                'uses' => 'PublicQuoteController@addToQuote',
            ]);
            
            Route::post('update', [
                'as' => 'update',
                'uses' => 'PublicQuoteController@update',
            ]);
            
            Route::post('remove/{rowId}', [
                'as' => 'remove',
                'uses' => 'PublicQuoteController@remove',
            ]);
            
            Route::post('destroy', [
                'as' => 'destroy',
                'uses' => 'PublicQuoteController@destroy',
            ]);
            
            Route::post('submit', [
                'as' => 'submit',
                'uses' => 'PublicQuoteController@submit',
            ])->middleware('auth:customer');
        });
    });
});