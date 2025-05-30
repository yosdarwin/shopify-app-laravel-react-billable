<?php

namespace App\Http\Controllers;

use App\Exceptions\ShopifyBillingException;
use App\Lib\EnsureBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Shopify\Auth\Session;

class PlansController extends Controller
{
    public function getPlans()
    {
        return response()->json([
            'plans' => [
                [
                    'id' => 'basic',
                    'name' => 'Basic Plan',
                    'price' => 4.99,
                    'interval' => EnsureBilling::INTERVAL_EVERY_30_DAYS,
                    'features' => ['Feature 1', 'Feature 2']
                ],
                [
                    'id' => 'premium',
                    'name' => 'Premium Plan',
                    'price' => 9.99,
                    'interval' => EnsureBilling::INTERVAL_EVERY_30_DAYS,
                    'features' => ['Feature 1', 'Feature 2', 'Feature 3', 'Feature 4']
                ]
            ]
        ]);
    }

    public function subscribe(Request $request)
    {
        try {
            /** @var Session */
            $session = $request->get('shopifySession');

            // Validate session exists
            if (!$session) {
                return response()->json(['error' => 'No valid Shopify session found'], 401);
            }

            $planId = $request->input('planId');

            // Validate plan ID
            if (!in_array($planId, ['basic', 'premium'])) {
                return response()->json(['error' => 'Invalid plan ID'], 400);
            }

            $billingConfig = $this->getBillingConfigForPlan($planId);

            Log::info('Starting subscription process', [
                'shop' => $session->getShop(),
                'planId' => $planId,
                'config' => $billingConfig
            ]);

            [$hasPayment, $confirmationUrl] = EnsureBilling::check($session, $billingConfig);

            Log::info('Billing check result', [
                'shop' => $session->getShop(),
                'hasPayment' => $hasPayment,
                'confirmationUrl' => $confirmationUrl
            ]);

            return response()->json([
                'hasActiveSubscription' => $hasPayment,
                'confirmationUrl' => $confirmationUrl
            ]);
        } catch (ShopifyBillingException $e) {
            Log::error('Shopify billing error in subscribe', [
                'shop' => $session?->getShop(),
                'planId' => $planId ?? 'unknown',
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : []
            ]);

            return response()->json([
                'error' => 'Failed to process subscription',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in subscribe', [
                'shop' => $session?->getShop(),
                'planId' => $planId ?? 'unknown',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }

    public function checkSubscription(Request $request)
    {
        try {
            /** @var Session */
            $session = $request->get('shopifySession');

            if (!$session) {
                return response()->json(['error' => 'No valid Shopify session found'], 401);
            }

            $billingConfig = Config::get('shopify.billing');

            Log::info('Checking subscription status', [
                'shop' => $session->getShop(),
                'config' => $billingConfig
            ]);

            [$hasPayment, $confirmationUrl] = EnsureBilling::check($session, $billingConfig);

            return response()->json([
                'hasActiveSubscription' => $hasPayment
            ]);
        } catch (ShopifyBillingException $e) {
            Log::error('Shopify billing error in checkSubscription', [
                'shop' => $session?->getShop(),
                'message' => $e->getMessage(),
                'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : []

            ]);

            return response()->json([
                'error' => 'Failed to check subscription status',
                'message' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in checkSubscription', [
                'shop' => $session?->getShop(),
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Get billing configuration for a specific plan
     */
    private function getBillingConfigForPlan(string $planId): array
    {
        $baseConfig = Config::get('shopify.billing');

        switch ($planId) {
            case 'basic':
                $baseConfig['chargeName'] = 'Basic Plan';
                $baseConfig['amount'] = 4.99;
                break;
            case 'premium':
                $baseConfig['chargeName'] = 'Premium Plan';
                $baseConfig['amount'] = 9.99;
                break;
            default:
                throw new \InvalidArgumentException("Invalid plan ID: {$planId}");
        }

        return $baseConfig;
    }

    /**
     * Get current active subscription details
     */
    public function getCurrentPlan(Request $request)
    {
        try {
            /** @var Session */
            $session = $request->get('shopifySession');

            if (!$session) {
                return response()->json(['error' => 'No valid Shopify session found'], 401);
            }

            // Check both plans to see which one is active
            $basicConfig = $this->getBillingConfigForPlan('basic');
            $premiumConfig = $this->getBillingConfigForPlan('premium');

            [$hasBasic,] = EnsureBilling::check($session, $basicConfig);
            [$hasPremium,] = EnsureBilling::check($session, $premiumConfig);

            $currentPlan = null;
            if ($hasPremium) {
                $currentPlan = 'premium';
            } elseif ($hasBasic) {
                $currentPlan = 'basic';
            }

            return response()->json([
                'currentPlan' => $currentPlan,
                'hasActiveSubscription' => $currentPlan !== null
            ]);
        } catch (ShopifyBillingException $e) {
            Log::error('Error getting current plan', [
                'shop' => $session?->getShop(),
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to get current plan',
                'currentPlan' => null,
                'hasActiveSubscription' => false
            ], 500);
        }
    }
}
