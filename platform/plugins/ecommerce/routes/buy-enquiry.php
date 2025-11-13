<?php

use Botble\Base\Facades\AdminHelper;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;

AdminHelper::registerRoutes(function (): void {
    Route::group(['namespace' => 'Botble\Ecommerce\Http\Controllers', 'prefix' => 'ecommerce'], function (): void {
        Route::group(['prefix' => 'buy-enquiry', 'as' => 'buy-enquiry.'], function (): void {
            Route::resource('', 'BuyEnquiryController')->parameters(['' => 'buyEnquiry']);
        });
    });
});
