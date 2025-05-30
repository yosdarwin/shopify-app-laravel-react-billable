<?php

use App\Lib\EnsureBilling;

return [

    /*
    |--------------------------------------------------------------------------
    | Shopify billing
    |--------------------------------------------------------------------------
    |
    | You may want to charge merchants for using your app. Setting required to true will cause the EnsureShopifySession
    | middleware to also ensure that the session is for a merchant that has an active one-time payment or subscription.
    | If no payment is found, it starts off the process and sends the merchant to a confirmation URL so that they can
    | approve the purchase.
    |
    | Learn more about billing in our documentation: https://shopify.dev/docs/apps/billing
    |
    */
    "billing" => [
        "required" => true,

        // Change to subscription billing
        "chargeName" => "Premium Plan",
        "amount" => 9.99,
        "currencyCode" => "USD",
        "interval" => EnsureBilling::INTERVAL_EVERY_30_DAYS,
    ],

];
