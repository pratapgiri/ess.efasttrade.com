<?php

use App\Models\Coupon;
use App\Models\Currency;
use App\Models\ExperienceCertificateTemplate;
use App\Models\JoiningLetterTemplate;
use App\Models\NocTemplate;
use App\Models\PaymentSetting;
use App\Models\Plan;
use App\Models\PlanOrder;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

if (! function_exists('getCacheSize')) {
    /**
     * Get the total cache size in MB
     *
     * @return string
     */
    function getCacheSize()
    {
        $file_size = 0;
        $framework_path = storage_path('framework');

        if (is_dir($framework_path)) {
            foreach (\File::allFiles($framework_path) as $file) {
                $file_size += $file->getSize();
            }
        }

        return number_format($file_size / 1000000, 2);
    }
}

if (! function_exists('settings')) {
    function settings($user_id = null)
    {
        // Skip database queries during installation
        if (request()->is('install/*') || request()->is('update/*') || ! file_exists(storage_path('installed'))) {
            return [];
        }

        if (is_null($user_id)) {
            if (auth()->user()) {
                if (isSaas()) {
                    if (! in_array(auth()->user()->type, ['superadmin', 'company'])) {
                        $autherUserCreatedBy = auth()->user()->created_by;
                        $user_id = getCompanyId($autherUserCreatedBy);
                    } else {
                        $user_id = auth()->id();
                    }
                } else {
                    // Non-SaaS: Company is top level
                    if (auth()->user()->type === 'company') {
                        $user_id = auth()->id();
                    } else {
                        $createdBy = getCompanyId(auth()->id());
                        $user_id = $createdBy;
                    }
                }
            } else {
                if (isSaas()) {
                    $user = User::where('type', 'superadmin')->first();
                } else {
                    $user = User::where('type', 'company')->first();
                }
                $user_id = $user ? $user->id : null;
            }
        }

        if (! $user_id) {
            return collect();
        }
        $userSettings = Setting::where('user_id', $user_id)->pluck('value', 'key')->toArray();

        // If user is not superadmin in SaaS mode, merge with superadmin settings for specific keys
        if (isSaas() && auth()->check() && auth()->user()->type !== 'superadmin') {
            $superAdmin = User::where('type', 'superadmin')->first();
            if ($superAdmin) {
                $superAdminKeys = ['decimalFormat', 'defaultCurrency', 'thousandsSeparator', 'floatNumber', 'currencySymbolSpace', 'currencySymbolPosition', 'dateFormat', 'timeFormat', 'calendarStartDay', 'defaultTimezone', 'contactUsUrl', 'contactUsDescription', 'strictlyCookieDescription', 'cookieDescription', 'strictlyCookieTitle', 'cookieTitle', 'strictlyNecessaryCookies', 'enableLogging'];
                $superAdminSettings = Setting::where('user_id', $superAdmin->id)
                    ->whereIn('key', $superAdminKeys)
                    ->pluck('value', 'key')
                    ->toArray();
                $userSettings = array_merge($superAdminSettings, $userSettings);
            }
        }

        return $userSettings;
    }
}

if (! function_exists('formatDateTime')) {
    function formatDateTime($date, $includeTime = true)
    {
        if (! $date) {
            return null;
        }

        $settings = settings();

        $dateFormat = $settings['dateFormat'] ?? 'Y-m-d';
        $timeFormat = $settings['timeFormat'] ?? 'H:i';
        $timezone = $settings['defaultTimezone'] ?? config('app.timezone', 'UTC');

        $format = $includeTime ? "$dateFormat $timeFormat" : $dateFormat;

        return Carbon::parse($date)->timezone($timezone)->format($format);
    }
}

if (! function_exists('getSetting')) {
    function getSetting($key, $default = null, $user_id = null)
    {
        $settings = settings($user_id);

        // If no value found and no default provided, try to get from defaultSettings
        if (! isset($settings[$key]) && $default === null) {
            $defaultSettings = defaultSettings();
            $default = $defaultSettings[$key] ?? null;
        }

        return $settings[$key] ?? $default;
    }
}

if (! function_exists('updateSetting')) {
    function updateSetting($key, $value, $user_id = null)
    {
        if (is_null($user_id)) {
            if (auth()->user()) {
                if (isSaas()) {
                    if (! in_array(auth()->user()->type, ['superadmin', 'company'])) {
                        $user_id = auth()->user()->created_by;
                    } else {
                        $user_id = auth()->id();
                    }
                } else {
                    // Non-SaaS: Company is top level
                    if (auth()->user()->hasRole('company')) {
                        $user_id = auth()->id();
                    } else {
                        $user_id = auth()->user()->created_by;
                    }
                }
            } else {
                if (isSaas()) {
                    $user = User::where('type', 'superadmin')->first();
                } else {
                    $user = User::where('type', 'company')->first();
                }
                $user_id = $user ? $user->id : null;
            }
        }

        if (! $user_id) {
            return false;
        }

        return Setting::updateOrCreate(
            ['user_id' => $user_id, 'key' => $key],
            ['value' => $value]
        );
    }
}

if (! function_exists('isLandingPageEnabled')) {
    function isLandingPageEnabled()
    {
        return getSetting('landingPageEnabled', true) === true || getSetting('landingPageEnabled', true) === '1';
    }
}

if (! function_exists('isUserRegistrationEnabled')) {
    function isUserRegistrationEnabled()
    {
        return getSetting('userRegistrationEnabled', true) === true || getSetting('userRegistrationEnabled', true) === '1';
    }
}

if (! function_exists('defaultRoleAndSetting')) {
    function defaultRoleAndSetting($user)
    {
        $companyRole = Role::where('name', 'company')->first();

        if ($companyRole) {
            $user->assignRole($companyRole);
        }

        // Create default settings for the user
        if ($user->type === 'superadmin') {
            createDefaultSettings($user->id);
        } elseif ($user->type === 'company') {
            copySettingsFromSuperAdmin($user->id);
            $user->companyDefaultData($user);
            // Create NOC Template For Company
            NocTemplate::createTemplatesForCompany($user->id);
            // Create Joining Letter templates For Company
            JoiningLetterTemplate::createTemplatesForCompany($user->id);
            // Create Experience Certificate templates For Company
            ExperienceCertificateTemplate::createTemplatesForCompany($user->id);
        }

        return true;
    }
}

if (! function_exists('getPaymentSettings')) {
    /**
     * Get payment settings for a user
     *
     * @param  int|null  $userId
     * @return array
     */
    function getPaymentSettings($userId = null)
    {
        if (is_null($userId)) {
            if (auth()->check() && auth()->user()->type == 'superadmin') {
                $userId = auth()->id();
            } else {
                $user = User::where('type', 'superadmin')->first();
                $userId = $user ? $user->id : null;
            }
        }

        return PaymentSetting::getUserSettings($userId);
    }
}

if (! function_exists('updatePaymentSetting')) {
    /**
     * Update or create a payment setting
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  int|null  $userId
     * @return \App\Models\PaymentSetting
     */
    function updatePaymentSetting($key, $value, $userId = null)
    {
        if (is_null($userId)) {
            $userId = auth()->id();
        }

        return PaymentSetting::updateOrCreateSetting($userId, $key, $value);
    }
}

if (! function_exists('isPaymentMethodEnabled')) {
    /**
     * Check if a payment method is enabled
     *
     * @param  string  $method  (stripe, paypal, razorpay, mercadopago, bank)
     * @param  int|null  $userId
     * @return bool
     */
    function isPaymentMethodEnabled($method, $userId = null)
    {
        $settings = getPaymentSettings($userId);
        $key = "is_{$method}_enabled";

        return isset($settings[$key]) && ($settings[$key] === true || $settings[$key] === '1');
    }
}

if (! function_exists('getPaymentMethodConfig')) {
    /**
     * Get configuration for a specific payment method
     *
     * @param  string  $method  (stripe, paypal, razorpay, mercadopago)
     * @param  int|null  $userId
     * @return array
     */
    function getPaymentMethodConfig($method, $userId = null)
    {
        $settings = getPaymentSettings($userId);

        switch ($method) {
            case 'stripe':
                return [
                    'enabled' => isPaymentMethodEnabled('stripe', $userId),
                    'key' => $settings['stripe_key'] ?? null,
                    'secret' => $settings['stripe_secret'] ?? null,
                ];

            case 'paypal':
                return [
                    'enabled' => isPaymentMethodEnabled('paypal', $userId),
                    'mode' => $settings['paypal_mode'] ?? 'sandbox',
                    'client_id' => $settings['paypal_client_id'] ?? null,
                    'secret' => $settings['paypal_secret_key'] ?? null,
                ];

            case 'razorpay':
                return [
                    'enabled' => isPaymentMethodEnabled('razorpay', $userId),
                    'key' => $settings['razorpay_key'] ?? null,
                    'secret' => $settings['razorpay_secret'] ?? null,
                ];

            case 'mercadopago':
                return [
                    'enabled' => isPaymentMethodEnabled('mercadopago', $userId),
                    'mode' => $settings['mercadopago_mode'] ?? 'sandbox',
                    'access_token' => $settings['mercadopago_access_token'] ?? null,
                ];

            case 'paystack':
                return [
                    'enabled' => isPaymentMethodEnabled('paystack', $userId),
                    'public_key' => $settings['paystack_public_key'] ?? null,
                    'secret_key' => $settings['paystack_secret_key'] ?? null,
                ];

            case 'flutterwave':
                return [
                    'enabled' => isPaymentMethodEnabled('flutterwave', $userId),
                    'public_key' => $settings['flutterwave_public_key'] ?? null,
                    'secret_key' => $settings['flutterwave_secret_key'] ?? null,
                ];

            case 'bank':
                return [
                    'enabled' => isPaymentMethodEnabled('bank', $userId),
                    'details' => $settings['bank_detail'] ?? null,
                ];

            case 'paytabs':
                return [
                    'enabled' => isPaymentMethodEnabled('paytabs', $userId),
                    'mode' => $settings['paytabs_mode'] ?? 'sandbox',
                    'profile_id' => $settings['paytabs_profile_id'] ?? null,
                    'server_key' => $settings['paytabs_server_key'] ?? null,
                    'region' => $settings['paytabs_region'] ?? 'ARE',
                ];

            case 'skrill':
                return [
                    'enabled' => isPaymentMethodEnabled('skrill', $userId),
                    'merchant_id' => $settings['skrill_merchant_id'] ?? null,
                    'secret_word' => $settings['skrill_secret_word'] ?? null,
                ];

            case 'coingate':
                return [
                    'enabled' => isPaymentMethodEnabled('coingate', $userId),
                    'mode' => $settings['coingate_mode'] ?? 'sandbox',
                    'api_token' => $settings['coingate_api_token'] ?? null,
                ];

            case 'payfast':
                return [
                    'enabled' => isPaymentMethodEnabled('payfast', $userId),
                    'mode' => $settings['payfast_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payfast_merchant_id'] ?? null,
                    'merchant_key' => $settings['payfast_merchant_key'] ?? null,
                    'passphrase' => $settings['payfast_passphrase'] ?? null,
                ];

            case 'tap':
                return [
                    'enabled' => isPaymentMethodEnabled('tap', $userId),
                    'secret_key' => $settings['tap_secret_key'] ?? null,
                ];

            case 'xendit':
                return [
                    'enabled' => isPaymentMethodEnabled('xendit', $userId),
                    'api_key' => $settings['xendit_api_key'] ?? null,
                ];

            case 'paytr':
                return [
                    'enabled' => isPaymentMethodEnabled('paytr', $userId),
                    'merchant_id' => $settings['paytr_merchant_id'] ?? null,
                    'merchant_key' => $settings['paytr_merchant_key'] ?? null,
                    'merchant_salt' => $settings['paytr_merchant_salt'] ?? null,
                ];

            case 'mollie':
                return [
                    'enabled' => isPaymentMethodEnabled('mollie', $userId),
                    'api_key' => $settings['mollie_api_key'] ?? null,
                ];

            case 'toyyibpay':
                return [
                    'enabled' => isPaymentMethodEnabled('toyyibpay', $userId),
                    'category_code' => $settings['toyyibpay_category_code'] ?? null,
                    'secret_key' => $settings['toyyibpay_secret_key'] ?? null,
                    'mode' => $settings['toyyibpay_mode'] ?? 'sandbox',
                ];

            case 'cashfree':
                return [
                    'enabled' => isPaymentMethodEnabled('cashfree', $userId),
                    'mode' => $settings['cashfree_mode'] ?? 'sandbox',
                    'public_key' => $settings['cashfree_public_key'] ?? null,
                    'secret_key' => $settings['cashfree_secret_key'] ?? null,
                ];

            case 'iyzipay':
                return [
                    'enabled' => isPaymentMethodEnabled('iyzipay', $userId),
                    'mode' => $settings['iyzipay_mode'] ?? 'sandbox',
                    'public_key' => $settings['iyzipay_public_key'] ?? null,
                    'secret_key' => $settings['iyzipay_secret_key'] ?? null,
                ];

            case 'benefit':
                return [
                    'enabled' => isPaymentMethodEnabled('benefit', $userId),
                    'mode' => $settings['benefit_mode'] ?? 'sandbox',
                    'public_key' => $settings['benefit_public_key'] ?? null,
                    'secret_key' => $settings['benefit_secret_key'] ?? null,
                ];

            case 'ozow':
                return [
                    'enabled' => isPaymentMethodEnabled('ozow', $userId),
                    'mode' => $settings['ozow_mode'] ?? 'sandbox',
                    'site_key' => $settings['ozow_site_key'] ?? null,
                    'private_key' => $settings['ozow_private_key'] ?? null,
                    'api_key' => $settings['ozow_api_key'] ?? null,
                ];

            case 'easebuzz':
                return [
                    'enabled' => isPaymentMethodEnabled('easebuzz', $userId),
                    'merchant_key' => $settings['easebuzz_merchant_key'] ?? null,
                    'salt_key' => $settings['easebuzz_salt_key'] ?? null,
                    'environment' => $settings['easebuzz_environment'] ?? 'test',
                ];

            case 'khalti':
                return [
                    'enabled' => isPaymentMethodEnabled('khalti', $userId),
                    'public_key' => $settings['khalti_public_key'] ?? null,
                    'secret_key' => $settings['khalti_secret_key'] ?? null,
                ];

            case 'authorizenet':
                return [
                    'enabled' => isPaymentMethodEnabled('authorizenet', $userId),
                    'mode' => $settings['authorizenet_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['authorizenet_merchant_id'] ?? null,
                    'transaction_key' => $settings['authorizenet_transaction_key'] ?? null,
                    'supported_countries' => ['US', 'CA', 'GB', 'AU'],
                    'supported_currencies' => ['USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD'],
                ];

            case 'fedapay':
                return [
                    'enabled' => isPaymentMethodEnabled('fedapay', $userId),
                    'mode' => $settings['fedapay_mode'] ?? 'sandbox',
                    'public_key' => $settings['fedapay_public_key'] ?? null,
                    'secret_key' => $settings['fedapay_secret_key'] ?? null,
                ];

            case 'payhere':
                return [
                    'enabled' => isPaymentMethodEnabled('payhere', $userId),
                    'mode' => $settings['payhere_mode'] ?? 'sandbox',
                    'merchant_id' => $settings['payhere_merchant_id'] ?? null,
                    'merchant_secret' => $settings['payhere_merchant_secret'] ?? null,
                    'app_id' => $settings['payhere_app_id'] ?? null,
                    'app_secret' => $settings['payhere_app_secret'] ?? null,
                ];

            case 'cinetpay':
                return [
                    'enabled' => isPaymentMethodEnabled('cinetpay', $userId),
                    'site_id' => $settings['cinetpay_site_id'] ?? null,
                    'api_key' => $settings['cinetpay_api_key'] ?? null,
                    'secret_key' => $settings['cinetpay_secret_key'] ?? null,
                ];

            case 'paymentwall':
                return [
                    'enabled' => isPaymentMethodEnabled('paymentwall', $userId),
                    'mode' => $settings['paymentwall_mode'] ?? 'sandbox',
                    'public_key' => $settings['paymentwall_public_key'] ?? null,
                    'private_key' => $settings['paymentwall_private_key'] ?? null,
                ];

            default:
                return [];
        }
    }
}

if (! function_exists('getEnabledPaymentMethods')) {
    /**
     * Get all enabled payment methods
     *
     * @param  int|null  $userId
     * @return array
     */
    function getEnabledPaymentMethods($userId = null)
    {
        $methods = ['stripe', 'paypal', 'razorpay', 'mercadopago', 'paystack', 'flutterwave', 'bank', 'paytabs', 'skrill', 'coingate', 'payfast', 'tap', 'xendit', 'paytr', 'mollie', 'toyyibpay', 'cashfree', 'iyzipay', 'benefit', 'ozow', 'easebuzz', 'khalti', 'authorizenet', 'fedapay', 'payhere', 'cinetpay', 'paymentwall'];
        $enabled = [];

        foreach ($methods as $method) {
            if (isPaymentMethodEnabled($method, $userId)) {
                $enabled[$method] = getPaymentMethodConfig($method, $userId);
            }
        }

        return $enabled;
    }
}

if (! function_exists('validatePaymentMethodConfig')) {
    /**
     * Validate payment method configuration
     *
     * @param  string  $method
     * @param  array  $config
     * @return array [valid => bool, errors => array]
     */
    function validatePaymentMethodConfig($method, $config)
    {
        $errors = [];

        switch ($method) {
            case 'stripe':
                if (empty($config['key'])) {
                    $errors[] = 'Stripe publishable key is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Stripe secret key is required';
                }
                break;

            case 'paypal':
                if (empty($config['client_id'])) {
                    $errors[] = 'PayPal client ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'PayPal secret key is required';
                }
                break;

            case 'razorpay':
                if (empty($config['key'])) {
                    $errors[] = 'Razorpay key ID is required';
                }
                if (empty($config['secret'])) {
                    $errors[] = 'Razorpay secret key is required';
                }
                break;

            case 'mercadopago':
                if (empty($config['access_token'])) {
                    $errors[] = 'MercadoPago access token is required';
                }
                break;

            case 'bank':
                if (empty($config['details'])) {
                    $errors[] = 'Bank details are required';
                }
                break;

            case 'paytabs':
                if (empty($config['server_key'])) {
                    $errors[] = 'PayTabs server key is required';
                }
                if (empty($config['profile_id'])) {
                    $errors[] = 'PayTabs profile id is required';
                }
                if (empty($config['region'])) {
                    $errors[] = 'PayTabs region is required';
                }
                break;

            case 'skrill':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Skrill merchant ID is required';
                }
                if (empty($config['secret_word'])) {
                    $errors[] = 'Skrill secret word is required';
                }
                break;

            case 'coingate':
                if (empty($config['api_token'])) {
                    $errors[] = 'CoinGate API token is required';
                }
                break;

            case 'payfast':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Payfast merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Payfast merchant key is required';
                }
                break;

            case 'tap':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Tap secret key is required';
                }
                break;

            case 'xendit':
                if (empty($config['api_key'])) {
                    $errors[] = 'Xendit api key is required';
                }
                break;

            case 'paytr':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayTR merchant ID is required';
                }
                if (empty($config['merchant_key'])) {
                    $errors[] = 'PayTR merchant key is required';
                }
                if (empty($config['merchant_salt'])) {
                    $errors[] = 'PayTR merchant salt is required';
                }
                break;

            case 'mollie':
                if (empty($config['api_key'])) {
                    $errors[] = 'Mollie API key is required';
                }
                break;

            case 'toyyibpay':
                if (empty($config['category_code'])) {
                    $errors[] = 'toyyibPay category code is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'toyyibPay secret key is required';
                }
                break;

            case 'cashfree':
                if (empty($config['public_key'])) {
                    $errors[] = 'Cashfree App ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Cashfree Secret Key is required';
                }
                break;

            case 'iyzipay':
                if (empty($config['public_key'])) {
                    $errors[] = 'Iyzipay API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Iyzipay secret key is required';
                }
                break;

            case 'benefit':
                if (empty($config['public_key'])) {
                    $errors[] = 'Benefit API key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Benefit secret key is required';
                }
                break;

            case 'ozow':
                if (empty($config['site_key'])) {
                    $errors[] = 'Ozow site key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'Ozow private key is required';
                }
                break;

            case 'easebuzz':
                if (empty($config['merchant_key'])) {
                    $errors[] = 'Easebuzz merchant key is required';
                }
                if (empty($config['salt_key'])) {
                    $errors[] = 'Easebuzz salt key is required';
                }
                break;

            case 'khalti':
                if (empty($config['public_key'])) {
                    $errors[] = 'Khalti public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Khalti secret key is required';
                }
                break;

            case 'authorizenet':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'AuthorizeNet merchant ID is required';
                }
                if (empty($config['transaction_key'])) {
                    $errors[] = 'AuthorizeNet transaction key is required';
                }
                break;

            case 'fedapay':
                if (empty($config['public_key'])) {
                    $errors[] = 'FedaPay public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'FedaPay secret key is required';
                }
                break;

            case 'payhere':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'PayHere merchant ID is required';
                }
                if (empty($config['merchant_secret'])) {
                    $errors[] = 'PayHere merchant secret is required';
                }
                break;

            case 'cinetpay':
                if (empty($config['site_id'])) {
                    $errors[] = 'CinetPay site ID is required';
                }
                if (empty($config['api_key'])) {
                    $errors[] = 'CinetPay API key is required';
                }
                break;

            case 'paiement':
                if (empty($config['merchant_id'])) {
                    $errors[] = 'Paiement Pro merchant ID is required';
                }
                break;

            case 'nepalste':
                if (empty($config['public_key'])) {
                    $errors[] = 'Nepalste public key is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'Nepalste secret key is required';
                }
                break;

            case 'yookassa':
                if (empty($config['shop_id'])) {
                    $errors[] = 'YooKassa shop ID is required';
                }
                if (empty($config['secret_key'])) {
                    $errors[] = 'YooKassa secret key is required';
                }
                break;

            case 'midtrans':
                if (empty($config['secret_key'])) {
                    $errors[] = 'Midtrans secret key is required';
                }
                break;

            case 'aamarpay':
                if (empty($config['store_id'])) {
                    $errors[] = 'Aamarpay store ID is required';
                }
                if (empty($config['signature'])) {
                    $errors[] = 'Aamarpay signature is required';
                }
                break;

            case 'paymentwall':
                if (empty($config['public_key'])) {
                    $errors[] = 'PaymentWall public key is required';
                }
                if (empty($config['private_key'])) {
                    $errors[] = 'PaymentWall private key is required';
                }
                break;

            case 'sspay':
                if (empty($config['secret_key'])) {
                    $errors[] = 'SSPay secret key is required';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}

if (! function_exists('calculatePlanPricing')) {
    function calculatePlanPricing($plan, $couponCode = null, $billingCycle = 'monthly')
    {
        // $originalPrice = $plan->price;
        $originalPrice = $plan->getPriceForCycle($billingCycle);
        $discountAmount = 0;
        $finalPrice = $originalPrice;
        $couponId = null;

        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)
                ->where('status', 1)
                ->first();

            if ($coupon) {
                if ($coupon->type === 'percentage') {
                    $discountAmount = ($originalPrice * $coupon->discount_amount) / 100;
                } else {
                    $discountAmount = min($coupon->discount_amount, $originalPrice);
                }
                $finalPrice = max(0, $originalPrice - $discountAmount);
                $couponId = $coupon->id;
            }
        }

        return [
            'original_price' => $originalPrice,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'coupon_id' => $couponId,
        ];
    }
}

if (! function_exists('createPlanOrder')) {
    function createPlanOrder($data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $pricing = calculatePlanPricing($plan, $data['coupon_code'] ?? null, $data['billing_cycle'] ?? 'monthly');

        return PlanOrder::create([
            'user_id' => $data['user_id'],
            'plan_id' => $plan->id,
            'coupon_id' => $pricing['coupon_id'],
            'billing_cycle' => $data['billing_cycle'],
            'payment_method' => $data['payment_method'],
            'coupon_code' => $data['coupon_code'] ?? null,
            'original_price' => $pricing['original_price'],
            'discount_amount' => $pricing['discount_amount'],
            'final_price' => $pricing['final_price'],
            'payment_id' => $data['payment_id'],
            'status' => $data['status'] ?? 'pending',
            'ordered_at' => now(),
        ]);
    }
}

if (! function_exists('assignPlanToUser')) {
    function assignPlanToUser($user, $plan, $billingCycle)
    {
        $expiresAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

        \Log::info('Assigning plan '.$plan->id.' to user '.$user->id.' with billing cycle '.$billingCycle);

        $updated = $user->update([
            'plan_id' => $plan->id,
            'plan_expire_date' => $expiresAt,
            'plan_is_active' => 1,
        ]);

        \Log::info('Plan assignment result: '.($updated ? 'success' : 'failed'));
    }
}

if (! function_exists('processPaymentSuccess')) {
    function processPaymentSuccess($data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $user = User::findOrFail($data['user_id']);

        $planOrder = createPlanOrder(array_merge($data, ['status' => 'approved']));
        assignPlanToUser($user, $plan, $data['billing_cycle']);

        // Verify the plan was assigned
        $user->refresh();

        // Create referral record if user was referred
        \App\Http\Controllers\ReferralController::createReferralRecord($user);

        return $planOrder;
    }
}

if (! function_exists('getPaymentGatewaySettings')) {
    function getPaymentGatewaySettings()
    {
        $superAdminId = User::where('type', 'superadmin')->first()?->id;

        return [
            'payment_settings' => PaymentSetting::getUserSettings($superAdminId),
            'general_settings' => Setting::getUserSettings($superAdminId),
            'super_admin_id' => $superAdminId,
        ];
    }
}

if (! function_exists('validatePaymentRequest')) {
    function validatePaymentRequest($request, $additionalRules = [])
    {
        $baseRules = [
            'plan_id' => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'coupon_code' => 'nullable|string',
        ];

        return $request->validate(array_merge($baseRules, $additionalRules));
    }
}

if (! function_exists('handlePaymentError')) {
    function handlePaymentError($e, $method = 'payment')
    {
        return back()->withErrors(['error' => __('Payment processing failed: :message', ['message' => $e->getMessage()])]);
    }
}

if (! function_exists('defaultSettings')) {
    function defaultSettings()
    {
        $productName = isSaas() ? 'HRM SaaS' : 'HRM';
        $settings = [
            'defaultLanguage' => 'en',
            'dateFormat' => 'Y-m-d',
            'timeFormat' => 'H:i',
            'calendarStartDay' => 'sunday',
            'defaultTimezone' => 'UTC',
            'emailVerification' => false,
            'landingPageEnabled' => true,
            'userRegistrationEnabled' => true,

            'logoDark' => 'logo/logo-dark.png',
            'logoLight' => 'logo/logo-light.png',
            'favicon' => 'logo/favicon.png',
            'titleText' => $productName,
            'footerText' => '© 2026 '.$productName.'. All rights reserved.',
            'themeColor' => 'green',
            'customColor' => '#10b77f',
            'sidebarVariant' => 'inset',
            'sidebarStyle' => 'plain',
            'layoutDirection' => 'left',
            'themeMode' => 'light',

            'storage_type' => 'local',
            'storage_file_types' => 'jpg,png,webp,gif,pdf,doc,docx,txt,csv',
            'storage_max_upload_size' => 2048,
            'aws_access_key_id' => '',
            'aws_secret_access_key' => '',
            'aws_default_region' => 'us-east-1',
            'aws_bucket' => '',
            'aws_url' => '',
            'aws_endpoint' => '',
            'wasabi_access_key' => '',
            'wasabi_secret_key' => '',
            'wasabi_region' => 'us-east-1',
            'wasabi_bucket' => '',
            'wasabi_url' => '',
            'wasabi_root' => '',

            'decimalFormat' => 2,
            'defaultCurrency' => 'USD',
            'decimalSeparator' => '.',
            'thousandsSeparator' => ',',
            'floatNumber' => true,
            'currencySymbolSpace' => false,
            'currencySymbolPosition' => 'before',

            'working_days' => '[1,2,3,4,5]',

            'metaKeywords' => $productName.' - All-in-One HR Management Software',
            'metaDescription' => 'Simplify employee management, payroll, attendance, recruitment, and performance with '.$productName.' — a modern HR management platform.',
            'metaImage' => 'seo/seo-banner.jpg',
        ];

        if (isDemo()) {
            $cookieSettingArray = [
                'enableLogging' => true,
                'strictlyNecessaryCookies' => true,
                'cookieTitle' => 'Cookie Consent',
                'strictlyCookieTitle' => 'Strictly Necessary Cookies',
                'cookieDescription' => 'We use cookies to enhance your browsing experience and provide personalized content.',
                'strictlyCookieDescription' => 'These cookies are essential for the website to function properly.',
                'contactUsDescription' => 'If you have any questions about our cookie policy, please contact us.',
                'contactUsUrl' => 'https://example.com/contact',
            ];
            $settings = array_merge($settings, $cookieSettingArray);
        }

        return $settings;
    }
}

if (! function_exists('createDefaultSettings')) {
    function createDefaultSettings($userId)
    {
        $defaults = defaultSettings();
        $settingsData = [];

        foreach ($defaults as $key => $value) {
            $settingsData[] = [
                'user_id' => $userId,
                'key' => $key,
                'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Setting::insert($settingsData);
    }
}

if (! function_exists('copySettingsFromSuperAdmin')) {
    function copySettingsFromSuperAdmin($companyUserId)
    {
        // $superAdmin = User::where('type', 'superadmin')->first();
        // if (!$superAdmin) {
        //     createDefaultSettings($companyUserId);
        //     return;
        // }

        if (isSaas()) {
            $superAdmin = User::where('type', 'superadmin')->first();
            if (! $superAdmin) {
                createDefaultSettings($companyUserId);

                return;
            }
        } else {
            // Non-SaaS: Create default settings directly
            createDefaultSettings($companyUserId);

            return;
        }

        // Settings to copy from superadmin (system and brand settings only)
        $settingsToCopy = [
            'defaultLanguage',
            'dateFormat',
            'timeFormat',
            'calendarStartDay',
            'defaultTimezone',
            'emailVerification',
            // 'landingPageEnabled',
            'logoDark',
            'logoLight',
            'favicon',
            'titleText',
            'footerText',
            'themeColor',
            'customColor',
            'sidebarVariant',
            'sidebarStyle',
            'layoutDirection',
            'themeMode',
        ];

        $superAdminSettings = Setting::where('user_id', $superAdmin->id)
            ->whereIn('key', $settingsToCopy)
            ->get();

        $settingsData = [];

        // Only copy existing superadmin settings
        foreach ($superAdminSettings as $setting) {
            $settingsData[] = [
                'user_id' => $companyUserId,
                'key' => $setting->key,
                'value' => $setting->value,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        //Setting::insertOrIgnore($settingsData);

        foreach ($settingsData as $row) {
                Setting::updateOrCreate(
                [
                    'user_id' => $row['user_id'],
                    'key' => $row['key'],
                ],
                [
                    'value' => $row['value'],
                    'updated_at' => now(),
                ]
                );
            }
        }
}

if (! function_exists('createdBy')) {
    function createdBy()
    {
        if (Auth::user()->type == 'superadmin') {
            return Auth::user()->id;
        } elseif (Auth::user()->type == 'company') {
            return Auth::user()->id;
        } else {
            return Auth::user()->created_by;
        }
    }
}

if (! function_exists('creatorId')) {
    function creatorId()
    {
        return Auth::user()->id;
    }
}

// For Auth User
if (! function_exists('getCompanyAndUsersId')) {
    function getCompanyAndUsersId()
    {
        $user = Auth::user();
        if ($user->hasRole(['company'])) {
            $companyId = getCompanyId($user->id);
            if ($companyId) {
                // Get all users in the company hierarchy
                $allUsers = getAllCompanyUsers($companyId);
                $allUsers[] = $companyId; // Include company itself

                return array_unique($allUsers);
            }

            return [];

            // Old code
            // $companyUserIds = User::where('created_by', $user->id)->pluck('id')->toArray();
            // $companyUserIds[] = $user->id;
            // return $companyUserIds;

        } else {
            // Find the root company ID using recursive function
            $companyId = getCompanyId($user->id);
            if ($companyId) {
                // Get all users in the company hierarchy
                $allUsers = getAllCompanyUsers($companyId);
                $allUsers[] = $companyId; // Include company itself

                return array_unique($allUsers);
            }

            return [];
        }
    }
}

// Recursive Function For Get the All Users of the company in tree hierarchy
if (! function_exists('getAllCompanyUsers')) {
    function getAllCompanyUsers($companyId, &$allUsers = [])
    {
        // Get direct users created by this company/user
        $directUsers = User::where('created_by', $companyId)->pluck('id')->toArray();
        foreach ($directUsers as $userId) {
            if (! in_array($userId, $allUsers)) {
                $allUsers[] = $userId;
                // Recursively get users created by this user
                getAllCompanyUsers($userId, $allUsers);
            }
        }

        return $allUsers;
    }
}

// For Non Auth User For Career page
if (! function_exists('getCompanyUsers')) {
    function getCompanyUsers($companyId)
    {
        $user = User::where('id', $companyId)->first();
        if (! $user) {
            return [];
        }
        if ($user->hasRole(['company'])) {
            $companyId = $user->id;

            if ($companyId) {
                // Get all users in the company hierarchy
                $allUsers = getAllCompanyUsers($companyId);
                $allUsers[] = $companyId; // Include company itself

                return array_unique($allUsers);
            }

            return [];

            // Old Code
            // $companyUserIds = User::where('created_by', $user->id)->pluck('id')->toArray();
            // $companyUserIds[] = $user->id;
            // return $companyUserIds;
        } else {
            $userCreatedBy = User::where('id', $user->created_by)->value('id');
            $companyUserIds = User::where('created_by', $userCreatedBy)->pluck('id')->toArray();
            $companyUserIds[] = $userCreatedBy;

            return $companyUserIds;
        }
    }
}

// Get Image URL Path
if (! function_exists('getImageUrlPrefix')) {
    function getImageUrlPrefix(): string
    {
        $settings = settings();
        $storageType = $settings['storage_type'] ?? 'local';
        switch ($storageType) {
            case 's3':
                $endpoint = $settings['aws_endpoint'];
                if ($endpoint) {
                    return rtrim($endpoint, '/').'/media/';
                }
                $bucket = $settings['aws_bucket'];
                $region = $settings['aws_default_region'];

                return "https://{$bucket}.s3.{$region}.amazonaws.com/media/";

            case 'wasabi':
                $url = $settings['wasabi_url'];

                return $url ? rtrim($url, '/').'/media/' : url('/storage/media/');

            case 'local':
            default:
                return url('/');
        }
    }
}

// Get Company and User
if (! function_exists('getUser')) {
    function getUser()
    {
        $autheUser = Auth::user();
        if ($autheUser->hasRole('superadmin')) {
            return $autheUser;
        } elseif ($autheUser->hasRole('company')) {
            return $autheUser;
        } else {
            $company = User::where('id', $autheUser->created_by)->first();

            return $company;
        }
    }
}

if (! function_exists('getStorageFilePath')) {
    /**
     * Get storage file path for downloads
     */
    function getStorageFilePath($filename)
    {
        if (empty($filename)) {
            return null;
        }

        // Remove any path separators to ensure only filename
        $filename = basename($filename);

        return storage_path('app/public/media/'.$filename);
    }
}

if (! function_exists('randomImage')) {
    function randomImage()
    {
        if (isSaas() && isDemo()) {
            $images = [
                'apex-industries-building-exterior.png',
                'apex-industries-business-card.png',
                'default-avatar.png',
                'global-systems-inc-social-banner.png',
                'apex-industries-logo.png',
                'phoenix-corporation-team-photo.png',
                'stellar-enterprises-social-banner.png',
                'techcorp-solutions-office-photo.png',
                'vortex-systems-building-exterior.png',
                'vortex-systems-business-card.png',
                'techcorp-solutions-business-card.png',
                'quantum-dynamics-office-photo.png',
                'phoenix-corporation-building-exterior.png',
                'infinity-solutions-office-photo.png',
                'nexus-technologies-business-card.png',
                'loading-animation.png',
                'global-systems-inc-office-photo.png',
                'digital-innovations-ltd-team-photo.png',
                'certificate-template.png',
                'apex-industries-team-photo.png',
            ];
        } else {
            $images = [
                'company-logo.png',
                'company-office-photo.png',
                'company-business-card.png',
                'company-letterhead.png',
                'company-team-photo.png',
                'company-building-exterior.png',
                'company-social-banner.png',
            ];
        }

        $randomImage = collect($images)->random();

        return $randomImage;
    }
}

if (! function_exists('isSaas')) {
    function isSaas()
    {
        $isSaas = config('app.is_saas');

        return $isSaas;
    }
}
if (! function_exists('isDemo')) {
    function isDemo()
    {
        $isDemo = config('app.is_demo');

        return $isDemo;
    }
}

if (! function_exists('isNotEditableRoles')) {
    function isNotEditableRoles()
    {
        // Roles that cannot be edited
        $notEditableRoles = [
            'employee',
            'hr',

        ];

        return $notEditableRoles;
    }
}

if (! function_exists('isNotDeletableRoles')) {
    function isNotDeletableRoles()
    {
        $notDeletableRoles = [
            'employee',
            'hr',
        ];

        return $notDeletableRoles;
    }
}

if (! function_exists('formatCurrency')) {
    function formatCurrency($amount, $user_id = null)
    {
        $settings = settings($user_id);
        $currencyCode = $settings['defaultCurrency'] ?? 'USD';

        // Get currency symbol from database
        $currency = Currency::where('code', $currencyCode)->first();
        $symbol = $currency ? $currency->symbol : $currencyCode;

        $decimalPlaces = (int) ($settings['decimalFormat'] ?? 2);
        $decimalSeparator = $settings['decimalSeparator'] ?? '.';
        $thousandsSeparator = $settings['thousandsSeparator'] ?? ',';
        $symbolPosition = $settings['currencySymbolPosition'] ?? 'before';
        $symbolSpace = $settings['currencySymbolSpace'] ?? false;

        $formattedAmount = number_format($amount, $decimalPlaces, $decimalSeparator, $thousandsSeparator);

        if ($symbolPosition === 'before') {
            return $symbol.($symbolSpace ? ' ' : '').$formattedAmount;
        } else {
            return $formattedAmount.($symbolSpace ? ' ' : '').$symbol;
        }
    }

    // For generate the Unique Slug for Missing Slug Users
    if (! function_exists('fixMissingUserSlugs')) {
        function fixMissingUserSlugs()
        {
            $updatedCount = 0;

            $users = User::whereNull('slug')
                ->orWhere('slug', '')
                ->get();

            foreach ($users as $user) {
                if (empty($user->name)) {
                    continue;
                }

                $baseSlug = Str::slug($user->name);
                $slug = $baseSlug;
                $counter = 1;

                while (
                    User::where('slug', $slug)
                        ->where('id', '!=', $user->id)
                        ->exists()
                ) {
                    $slug = $baseSlug.'-'.$counter;
                    $counter++;
                }

                $user->slug = $slug;
                $user->save();

                $updatedCount++;
            }

            return $updatedCount;
        }
    }
}

if (! function_exists('getCompanyId')) {
    function getCompanyId($userId)
    {
        $user = User::find($userId);

        if (! $user) {
            return null;
        }
        if ($user->type === 'company' || $user->hasRole('company')) {
            return $user->id;
        }

        if ($user->created_by) {
            return getCompanyId($user->created_by);
        }

        return null;
    }
}

// Set Email Configurations
if (! function_exists('setEmailConfigurations')) {

    function setEmailConfigurations(): void
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return;
            }
            if (isSaas()) {
                if ($user->hasRole('superadmin')) {
                    $user = $user;
                } elseif ($user->hasRole('company')) {
                    $user = $user;
                } else {
                    $getUserCreatedBy = getCompanyId($user->id);
                    $user = User::where('id', $getUserCreatedBy)->first();
                }
            } else {
                if ($user->hasRole('company')) {
                    $user = $user;
                } else {
                    $getUserCreatedBy = getCompanyId($user->id);
                    $user = User::where('id', $getUserCreatedBy)->first();
                }
            }

            $getSettings = settings($user->id);

            $settings = [
                'driver' => $getSettings['email_driver'] ?? '',
                'host' => $getSettings['email_host'] ?? '',
                'port' => $getSettings['email_port'] ?? '',
                'username' => $getSettings['email_username'] ?? '',
                'password' => $getSettings['email_password'] ?? '',
                'encryption' => $getSettings['email_encryption'] ?? '',
                'fromAddress' => $getSettings['email_from_address'] ?? '',
                'fromName' => $getSettings['email_from_name'] ?? '',
            ];

            Config::set([
                'mail.default' => $settings['driver'],
                'mail.mailers.smtp.host' => $settings['host'],
                'mail.mailers.smtp.port' => $settings['port'],
                'mail.mailers.smtp.encryption' => $settings['encryption'] === 'none' ? null : $settings['encryption'],
                'mail.mailers.smtp.username' => $settings['username'],
                'mail.mailers.smtp.password' => $settings['password'],
                'mail.from.address' => $settings['fromAddress'],
                'mail.from.name' => $settings['fromName'],
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Email config error: '.$e->getMessage());
        }
    }
}

// Set Email Configurations
if (! function_exists('getDeviceType')) {

    function getDeviceType($userAgent)
    {
        $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
        $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';

        if (preg_match_all($mobile_regex, $userAgent)) {
            return 'mobile';
        } else {

            if (preg_match_all($tablet_regex, $userAgent)) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }
    }
}

// Get super admin settings
if (! function_exists('getAdminAllSetting')) {
    function getAdminAllSetting()
    {
        // Laravel cache
        return Cache::rememberForever('admin_settings', function () {
            if (isSaas()) {
                $superAdmin = User::where('type', 'superadmin')->first();
            } else {
                $superAdmin = User::where('type', 'company')->first();
            }

            $settings = [];
            if ($superAdmin) {
                $settings = Setting::where('user_id', $superAdmin->id)->pluck('value', 'key')->toArray();
            }

            return $settings;
        });
    }
}

// File Upload Function
if (! function_exists('upload_file')) {
    function upload_file($request, $key_name, $name, $path, $custom_validation = [])
    {
        try {
            $storage_settings = getAdminAllSetting();

            if (isset($storage_settings['storage_type'])) {
                if ($storage_settings['storage_type'] == 'wasabi') {
                    config(
                        [
                            'filesystems.disks.wasabi.driver' => 's3',
                            'filesystems.disks.wasabi.key' => $storage_settings['wasabi_access_key'],
                            'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret_key'],
                            'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'] ?? 'us-east-1',
                            'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                            'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                            'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                            'filesystems.disks.use_path_style_endpoint' => false,
                            'filesystems.disks.wasabi.visibility' => 'public',
                        ]
                    );
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                } elseif ($storage_settings['storage_type'] == 'aws_s3') {
                    config(
                        [
                            'filesystems.disks.s3.driver' => 's3',
                            'filesystems.disks.s3.key' => $storage_settings['aws_access_key_id'],
                            'filesystems.disks.s3.secret' => $storage_settings['aws_secret_access_key'],
                            'filesystems.disks.s3.region' => $storage_settings['aws_default_region'] ?? 'us-east-1',
                            'filesystems.disks.s3.bucket' => $storage_settings['aws_bucket'],
                            'filesystems.disks.s3.url' => $storage_settings['aws_url'],
                            'filesystems.disks.s3.endpoint' => $storage_settings['aws_endpoint'],
                            'filesystems.disks.s3.use_path_style_endpoint' => false,
                            'filesystems.disks.s3.visibility' => 'public',
                        ]
                    );
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                } else {
                    $max_size = ! empty($storage_settings['storage_max_upload_size']) ? $storage_settings['storage_max_upload_size'] : '2048';
                    $mimes = ! empty($storage_settings['storage_file_types']) ? $storage_settings['storage_file_types'] : 'jpeg,jpg,png,svg,zip,txt,gif,docx';
                }
                $file = $request->$key_name;

                $extension = strtolower($file->getClientOriginalExtension());
                $allowed_extensions = explode(',', $mimes);

                if (empty($extension) || ! in_array($extension, $allowed_extensions)) {
                    return [
                        'status' => false,
                        'msg' => 'The '.$key_name.' must be a file of type: '.implode(', ', $allowed_extensions).'.',
                    ];
                }

                if (count($custom_validation) > 0) {
                    $validation = $custom_validation;
                } else {
                    $validation = [
                        'mimes:'.$mimes,
                        'max:'.$max_size,
                    ];
                }
                $validator = Validator::make($request->all(), [
                    $key_name => $validation,
                ]);
                if ($validator->fails()) {
                    $res = [
                        'status' => false,
                        'msg' => $validator->messages()->first(),
                    ];

                    return $res;
                } else {
                    $storageType = $settings['storage_type'] ?? 'local';
                    $diskName = match ($storageType) {
                        'local' => 'public',
                        'aws_s3' => 's3',
                        'wasabi' => 'wasabi',
                        default => 'public'
                    };

                    // Store file directly to storage
                    $file->storeAs('media/'.$path, $name, $diskName);

                    $res = [
                        'status' => true,
                        'msg' => 'success',
                        'url' => $path.'/'.$name,
                    ];

                    return $res;
                }
            } else {
                $res = [
                    'status' => false,
                    'msg' => __('Not set configurations'),
                ];

                return $res;
            }
        } catch (\Exception $e) {
            $res = [
                'status' => false,
                'msg' => $e->getMessage(),
            ];

            return $res;
        }
    }
}

if (! function_exists('check_file')) {
    function check_file($path)
    {
        try {
            if (empty($path)) {
                return false;
            }
            $storage_settings = getAdminAllSetting();
            if (! isset($storage_settings['storage_type'])) {
                return false;
            }

            $storageType = $storage_settings['storage_type'];

            // Handle local storage
            if ($storageType === 'local' || $storageType === null) {
                // Check in public storage path
                $publicPath = storage_path('app/public/media/'.ltrim($path, '/'));
                if (file_exists($publicPath)) {
                    return true;
                }

                // Check in base path as fallback
                $basePath = base_path($path);

                return file_exists($basePath);
            }

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3') {
                if (empty($storage_settings['aws_access_key_id']) ||
                    empty($storage_settings['aws_secret_access_key']) ||
                    empty($storage_settings['aws_default_region']) ||
                    empty($storage_settings['aws_bucket'])) {
                    return false;
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['aws_access_key_id'],
                    'filesystems.disks.s3.secret' => $storage_settings['aws_secret_access_key'],
                    'filesystems.disks.s3.region' => $storage_settings['aws_default_region'] ?? 'us-east-1',
                    'filesystems.disks.s3.bucket' => $storage_settings['aws_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/'.ltrim($path, '/');

                return Storage::disk('s3')->exists($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (empty($storage_settings['wasabi_access_key']) ||
                    empty($storage_settings['wasabi_secret_key']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_url']) ||
                    empty($storage_settings['wasabi_root'])) {
                    return false;
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_access_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret_key'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'] ?? 'us-east-1',
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'] ?? null,
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'] ?? '',
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/'.ltrim($path, '/');

                return Storage::disk('wasabi')->exists($wasabiPath);
            }

            // Unknown storage type
            return false;

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('check_file error: '.$e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}

if (! function_exists('get_file')) {
    function get_file($path)
    {
        try {
            // Return empty string if path is empty
            if (empty($path)) {
                return '';
            }

            $storage_settings = getAdminAllSetting();

            // Check if storage settings exist, fallback to local
            if (!isset($storage_settings['storage_type'])) {
                return url('storage/media/' . ltrim($path, '/'));
            }

            $storageType = $storage_settings['storage_type'];

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3' || $storageType === 's3') {
                if (empty($storage_settings['s3_key']) ||
                    empty($storage_settings['s3_secret']) ||
                    empty($storage_settings['s3_region']) ||
                    empty($storage_settings['s3_bucket'])) {
                    return url('storage/media/' . ltrim($path, '/'));
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['s3_key'],
                    'filesystems.disks.s3.secret' => $storage_settings['s3_secret'],
                    'filesystems.disks.s3.region' => $storage_settings['s3_region'],
                    'filesystems.disks.s3.bucket' => $storage_settings['s3_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/' . ltrim($path, '/');
                return Storage::disk('s3')->url($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (empty($storage_settings['wasabi_key']) ||
                    empty($storage_settings['wasabi_secret']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_root']) ||
                    empty($storage_settings['wasabi_url'])) {
                    return url('storage/media/' . ltrim($path, '/'));
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'],
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/' . ltrim($path, '/');
                return Storage::disk('wasabi')->url($wasabiPath);
            }

            // Handle local storage (default) — public disk via storage symlink
            $normalized = ltrim($path, '/');
            if (str_starts_with($normalized, 'media/') || str_starts_with($normalized, 'claims/') || str_starts_with($normalized, 'trips/')) {
                return asset('storage/'.$normalized);
            }

            return asset('storage/media/'.$normalized);

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('get_file error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            // Return asset path as fallback
            return asset($path);
        }
    }
}

if (! function_exists('delete_file')) {
    function delete_file($path)
    {
        try {
            // Return false if path is empty
            if (empty($path)) {
                return false;
            }

            // Check if file exists first
            if (!check_file($path)) {
                return false;
            }

            $storage_settings = getAdminAllSetting();

            // Check if storage settings exist
            if (!isset($storage_settings['storage_type'])) {
                return false;
            }

            $storageType = $storage_settings['storage_type'];

            // Handle local storage
            if ($storageType === 'local' || $storageType === null) {
                $publicPath = storage_path('app/public/media/' . ltrim($path, '/'));
                if (file_exists($publicPath)) {
                    return unlink($publicPath);
                }
                return false;
            }

            // Handle AWS S3 storage
            if ($storageType === 'aws_s3' || $storageType === 's3') {
                if (empty($storage_settings['s3_key']) ||
                    empty($storage_settings['s3_secret']) ||
                    empty($storage_settings['s3_region']) ||
                    empty($storage_settings['s3_bucket'])) {
                    return false;
                }

                config([
                    'filesystems.disks.s3.key' => $storage_settings['s3_key'],
                    'filesystems.disks.s3.secret' => $storage_settings['s3_secret'],
                    'filesystems.disks.s3.region' => $storage_settings['s3_region'],
                    'filesystems.disks.s3.bucket' => $storage_settings['s3_bucket'],
                ]);

                // Normalize path for S3
                $s3Path = 'media/' . ltrim($path, '/');
                return Storage::disk('s3')->delete($s3Path);
            }

            // Handle Wasabi storage
            if ($storageType === 'wasabi') {
                if (empty($storage_settings['wasabi_key']) ||
                    empty($storage_settings['wasabi_secret']) ||
                    empty($storage_settings['wasabi_region']) ||
                    empty($storage_settings['wasabi_bucket']) ||
                    empty($storage_settings['wasabi_root']) ||
                    empty($storage_settings['wasabi_url'])) {
                    return false;
                }

                config([
                    'filesystems.disks.wasabi.key' => $storage_settings['wasabi_key'],
                    'filesystems.disks.wasabi.secret' => $storage_settings['wasabi_secret'],
                    'filesystems.disks.wasabi.region' => $storage_settings['wasabi_region'],
                    'filesystems.disks.wasabi.bucket' => $storage_settings['wasabi_bucket'],
                    'filesystems.disks.wasabi.root' => $storage_settings['wasabi_root'],
                    'filesystems.disks.wasabi.endpoint' => $storage_settings['wasabi_url'],
                ]);

                // Normalize path for Wasabi
                $wasabiPath = 'media/' . ltrim($path, '/');
                return Storage::disk('wasabi')->delete($wasabiPath);
            }

            // Unknown storage type
            return false;

        } catch (\Exception $e) {
            // Log error for debugging
            Log::error('delete_file error: ' . $e->getMessage(), [
                'path' => $path,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}

if (! function_exists('bootstrapClaimPermissions')) {
    /**
     * Ensure claim module permissions exist and are assigned to standard roles.
     * Safe to call on each request (runs once per process).
     */
    function bootstrapClaimPermissions(): void
    {
        static $bootstrapped = false;
        if ($bootstrapped) {
            return;
        }
        $bootstrapped = true;

        if (! file_exists(storage_path('installed'))) {
            return;
        }

        try {
            $definitions = [
                ['name' => 'manage-claims', 'module' => 'claims', 'label' => 'Manage Claims', 'description' => 'Can manage claims'],
                ['name' => 'manage-own-claims', 'module' => 'claims', 'label' => 'Manage Own Claims', 'description' => 'Can manage own claims'],
                ['name' => 'manage-claim-approvals', 'module' => 'claims', 'label' => 'Manage Claim Approvals', 'description' => 'Can approve claims'],
                ['name' => 'view-claims', 'module' => 'claims', 'label' => 'View Claims', 'description' => 'View claims'],
                ['name' => 'create-claims', 'module' => 'claims', 'label' => 'Create Claims', 'description' => 'Can create claims'],
                ['name' => 'edit-claims', 'module' => 'claims', 'label' => 'Edit Claims', 'description' => 'Can edit claims'],
                ['name' => 'delete-claims', 'module' => 'claims', 'label' => 'Delete Claims', 'description' => 'Can delete claims'],
            ];

            foreach ($definitions as $def) {
                \Spatie\Permission\Models\Permission::firstOrCreate(
                    ['name' => $def['name'], 'guard_name' => 'web'],
                    [
                        'module' => $def['module'],
                        'label' => $def['label'],
                        'description' => $def['description'],
                    ]
                );
            }

            $allClaimPermissions = \Spatie\Permission\Models\Permission::whereIn(
                'name',
                array_column($definitions, 'name')
            )->where('guard_name', 'web')->get();

            $employeePerms = \Spatie\Permission\Models\Permission::whereIn('name', [
                'manage-dashboard',
                'view-dashboard',
                'manage-own-claims',
                'create-claims',
                'view-claims',
            ])->where('guard_name', 'web')->get();

            $approverPerms = $allClaimPermissions->whereIn('name', [
                'manage-claim-approvals', 'manage-own-claims', 'view-claims', 'create-claims', 'edit-claims', 'delete-claims',
            ]);

            foreach (['company', 'employee', 'manager', 'hr'] as $roleName) {
                $roles = \Spatie\Permission\Models\Role::where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->get();

                foreach ($roles as $role) {
                    if ($roleName === 'company') {
                        $role->givePermissionTo($allClaimPermissions);
                    } elseif ($roleName === 'employee') {
                        $role->givePermissionTo($employeePerms);
                    } else {
                        $role->givePermissionTo($approverPerms);
                    }
                }
            }

            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Silently skip during install / when DB unavailable
        }
    }
}

if (! function_exists('ensureAuthenticatedUserClaimAccess')) {
    /**
     * No-op at runtime — use ClaimRolePermissionsSeeder once. Avoids slow DB sync on every request.
     */
    function ensureAuthenticatedUserClaimAccess(): void
    {
        // Permissions are assigned via ClaimRolePermissionsSeeder / role sync.
    }
}
