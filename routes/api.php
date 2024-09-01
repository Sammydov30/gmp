<?php

use App\Http\Controllers\API\V1\AccountController;
use App\Http\Controllers\API\V1\Admin\AdminController;
use App\Http\Controllers\API\V1\Admin\Plan\PlanController;
use App\Http\Controllers\API\V1\Admin\State\StateController;
use App\Http\Controllers\API\V1\Auth\AdminAuthController;
use App\Http\Controllers\API\V1\Auth\CustomerAuthController;
use App\Http\Controllers\API\V1\BankController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\ComplaintController;
use App\Http\Controllers\API\V1\CountryController;
use App\Http\Controllers\API\V1\Customer\CustomerController;
use App\Http\Controllers\API\V1\Customer\LogisticsController;
use App\Http\Controllers\API\V1\DepositHistoryController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\FeedBackRatingController;
use App\Http\Controllers\API\V1\GeneralController;
use App\Http\Controllers\API\V1\HaulageController;
use App\Http\Controllers\API\V1\MarketPlaceController;
use App\Http\Controllers\API\V1\NotificationController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\PickupCenterController;
use App\Http\Controllers\API\V1\PickupCentersController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ProductReportingController;
use App\Http\Controllers\API\V1\RegionController;
use App\Http\Controllers\API\V1\RegionsController;
use App\Http\Controllers\API\V1\SpecialItemController;
use App\Http\Controllers\API\V1\StoreController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\TransactionController;
use App\Http\Controllers\API\V1\WishlistController;
use App\Http\Controllers\API\V1\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1')->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    // Only for customers
    Route::middleware(['auth:sanctum', 'type.customer'])->group(function () {
        Route::post('/customer/logout', [CustomerAuthController::class, 'logout']);
        Route::get('/customer', [CustomerController::class, 'index']);
        Route::get('/customer/getbalance', [CustomerController::class, 'getbalance']);
        Route::get('/customer/fetchprofile', [CustomerController::class, 'editprofile']);
        Route::post('/customer/updateprofile', [CustomerController::class, 'updateprofile']);
        Route::get('/customer/listaddress', [CustomerController::class, 'listaddress']);
        Route::post('/customer/addaddress', [CustomerController::class, 'addaddress']);
        Route::post('/customer/updateaddress', [CustomerController::class, 'editaddress']);
        Route::post('/customer/deleteaddress', [CustomerController::class, 'deleteaddress']);
        Route::post('/customer/changeprofilepicture', [CustomerController::class, 'updateprofilepicture']);
        Route::post('/customer/changepassword', [CustomerController::class, 'updatepassword']);
        Route::post('/customer/deleteaccount', [CustomerController::class, 'deleteaccount']);
        Route::post('/customer/deactivateaccount', [CustomerController::class, 'deactivateaccount']);
        Route::post('/customer/toggleemailnotification', [CustomerController::class, 'toggleemailnotification']);
        Route::post('/customer/toggledesktopnotification', [CustomerController::class, 'toggledesktopnotification']);
        Route::post('/customer/togglesubscriptionduenotification', [CustomerController::class, 'togglesubscriptionduenotification']);
        Route::post('/customer/togglecheckupschedulednotification', [CustomerController::class, 'togglecheckupschedulednotification']);

        //PIN
        Route::post('/customer/pin/checkpin', [CustomerController::class, 'checkpin']);
        Route::post('/customer/pin/setpin', [CustomerController::class, 'setpin']);
        Route::post('/customer/pin/changepin', [CustomerController::class, 'updatepin']);

        //Feedback Rating
        Route::get('/customer/order/feedback/fetchall', [FeedBackRatingController::class, 'index']);
        Route::post('/customer/order/feedback/addfeedback', [FeedBackRatingController::class, 'store']);

        //Product Report
        Route::get('/product/report/fetchall', [ProductReportingController::class, 'index']);
        Route::post('/product/report/addreport', [ProductReportingController::class, 'store']);

        //Logistics
        Route::get('/customer/logistics/fetchall', [LogisticsController::class, 'index']);
        Route::get('/customer/logistics/fetchrecent', [LogisticsController::class, 'fetchrecent']);
        Route::get('/customer/logistics/fetch', [LogisticsController::class, 'getshipment']);
        Route::get('/customer/logistics/track', [LogisticsController::class, 'track']);
        Route::post('/customer/logistics/makelogistics', [LogisticsController::class, 'store']);
        Route::post('/customer/logistics/getquote', [LogisticsController::class, 'getquote']);
        Route::post('/customer/logistics/verifypayment', [LogisticsController::class, 'verifypayment']);

        //Haulages
        Route::get('/customer/haulages/fetchall', [HaulageController::class, 'index']);
        Route::post('/customer/haulages/bookhaulage', [HaulageController::class, 'store']);
        Route::get('/customer/haulages/fetch/{haulage}', [HaulageController::class, 'show']);

        //Deposit
        Route::get('/customer/deposit/fetchall', [DepositHistoryController::class, 'index']);
        Route::get('/customer/deposit/getdeposit/{id}', [DepositHistoryController::class, 'show']);
        Route::post('/customer/deposit/fundaccount', [DepositHistoryController::class, 'fundaccount']);
        Route::post('/customer/deposit/verifypayment', [DepositHistoryController::class, 'verifypayment']);

        //Withdrawal
        Route::get('/customer/withdrawal/fetchall', [WithdrawalController::class, 'index']);
        Route::get('/customer/withdrawal/getwithdrawal/{id}', [WithdrawalController::class, 'show']);
        Route::post('/customer/withdrawal/makewithdrawal', [WithdrawalController::class, 'makewithdrawal']);

        //FundingHistory
        Route::get('/customer/funding/fetchall', [TransactionController::class, 'index']);
        Route::get('/customer/funding/fetchrecent', [TransactionController::class, 'fetchrecenttransactions']);
        Route::get('/customer/funding/getfunding/{id}', [TransactionController::class, 'show']);

        //subscriptions
        Route::get('/customer/subscription/checksubscription', [SubscriptionController::class, 'checkseller']);
        Route::post('/customer/subscription/sellongmp', [SubscriptionController::class, 'sellongmp']);
        Route::post('/customer/subscription/subscribe', [SubscriptionController::class, 'addsubscription']);

        //store
        Route::apiResource('/customer/gmpstores', StoreController::class);

        //product
        Route::apiResource('/customer/products', ProductController::class);
        Route::post('/customer/product/makeavailable', [ProductController::class, 'available']);
        Route::post('/customer/product/makeunavailable', [ProductController::class, 'unavailable']);

        //Cart
        Route::get('/customer/cart/getcartitems', [CartController::class, 'index']);
        Route::post('/customer/cart/addtocart', [CartController::class, 'addtocart']);
        Route::post('/customer/cart/addtocartgroup', [CartController::class, 'addtocartgroup']);
        Route::post('/customer/cart/removefromcart', [CartController::class, 'removefromcart']);
        Route::post('/customer/cart/increase', [CartController::class, 'increaseQuantity']);
        Route::post('/customer/cart/decrease', [CartController::class, 'decreaseQuantity']);
        //Route::get('/customer/cart/confirmavailability', [CartController::class, 'confirmavailability']);
        Route::get('/customer/cart/checkout', [CartController::class, 'checkout']);
        Route::post('/customer/cart/getshippingrate', [CartController::class, 'getShippingRate']);
        Route::post('/customer/cart/addtoorder', [CartController::class, 'addToOrder']);
        Route::post('/customer/cart/buynow', [CartController::class, 'BuyNow']);
        Route::post('/customer/cart/verifypayment', [CartController::class, 'verifypayment']);

        //wishlist
        Route::get('/customer/wishlist/getwishlistitems', [WishlistController::class, 'index']);
        Route::post('/customer/wishlist/addtowishlist', [WishlistController::class, 'addtowishlist']);
        Route::post('/customer/wishlist/removefromwishlist', [WishlistController::class, 'removefromwishlist']);
        Route::post('/customer/wishlist/addtocartfromwishlist', [WishlistController::class, 'addtocartfromwishlist']);
        //Route::get('/customer/wishlist/confirmavailability', [WishlistController::class, 'confirmavailability']);
        // Route::get('/customer/wishlist/checkout', [WishlistController::class, 'checkout']);
        // Route::post('/customer/wishlist/addtoorder', [WishlistController::class, 'addToOrder']);
        // Route::post('/customer/wishlist/buynow', [WishlistController::class, 'BuyNow']);
        // Route::post('/customer/wishlist/verifypayment', [WishlistController::class, 'verifypayment']);

        //orders
        Route::get('/customer/order/getorders', [OrderController::class, 'index']);
        Route::get('/all/order/getorderitems', [OrderController::class, 'getOrderItems']);
        Route::get('/customer/order/getorder', [OrderController::class, 'getSingleOrder']);
        //Route::get('/customer/seller/order/getorder', [OrderController::class, 'getSingleOrder']);

        //Notification
        Route::get('/customer/notification/fetchnotifications', [NotificationController::class, 'fetchnotificationforuser']);
        Route::post('/customer/notification/markread', [NotificationController::class, 'readnotification']);
    });
    //Un auth routes
    Route::post('/customer/auth/getstarted', [CustomerAuthController::class, 'getstarted']);
    Route::post('/customer/auth/register', [CustomerAuthController::class, 'register']);
    Route::post('/customer/auth/resetpassword', [CustomerAuthController::class, 'resetpassword']);
    Route::post('/customer/auth/forgotpasswordwithemail', [CustomerAuthController::class, 'forgotpasswordwithemail']);
    Route::post('/customer/auth/checkotp', [CustomerAuthController::class, 'checkotp']);
    Route::post('/customer/auth/login', [CustomerAuthController::class, 'login']);

    // Only for admin
    Route::middleware(['auth:sanctum', 'type.admin'])->group(function () {
        Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
        Route::get('/admin', [AdminController::class, 'getA']);
        Route::post('/admin/updateprofile', [AdminController::class, 'changeprofile']);
        Route::post('/admin/changepassword', [AdminController::class, 'changepassword']);
        //plan
        Route::apiResource('/admin/plans', PlanController::class);
        //Deposit
        Route::get('/admin/deposit/fetchall', [DepositHistoryController::class, 'index']);
        Route::get('/admin/deposit/getdeposit/{id}', [DepositHistoryController::class, 'show']);

        //Withdrawal
        Route::get('/admin/withdrawal/fetchall', [WithdrawalController::class, 'index']);
        Route::get('/admin/withdrawal/getwithdrawal/{id}', [WithdrawalController::class, 'show']);

        //marketplace
        Route::apiResource('/admin/marketplaces', MarketPlaceController::class);
        //category
        Route::apiResource('/admin/categories', CategoryController::class);

        //Restricted
        Route::middleware(['restrictothers'])->group(function () {
            Route::post('/admin/create', [AdminController::class, 'register']);
            Route::post('/admin/edit/{admin}', [AdminController::class, 'update']);
            Route::get('/admin/get-admins', [AdminController::class, 'index']);
            Route::delete('/admin/delete/{admin}', [AdminController::class, 'destroy']);

            //Withdrawal
            Route::post('/admin/withdrawal/acceptwithdrawal', [WithdrawalController::class, 'confirmwithdrawal']);
            Route::post('/admin/withdrawal/declinewithdrawal', [WithdrawalController::class, 'declinewithdrawal']);
        });
        ///End Restricted

    });
    Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/auth/verifyotp', [AdminAuthController::class, 'check_otp']);

    Route::middleware(['auth:sanctum'])->group(function () {
        //account
        Route::apiResource('/user/accounts', AccountController::class);
    });

    //Banks
    Route::get('/fetchbanks', [BankController::class, 'fetchbanks']);
    Route::get('/fetchaccountdetails', [AccountController::class, 'getAccountName']);
    //states
    Route::apiResource('/states', StateController::class);
    //regions
    Route::get('/regions', [RegionsController::class, 'index']);
    Route::get('/region/{id}', [RegionsController::class, 'show']);
    Route::post('/region/add', [RegionsController::class, 'store']);
    Route::post('/region/update/{id}', [RegionsController::class, 'update']);
    Route::post('/region/delete/{id}', [RegionsController::class, 'destroy']);
    //pickupcenters
    Route::get('/pickupcenters', [PickupCentersController::class, 'index']);
    Route::get('/pickupcenter/{id}', [PickupCentersController::class, 'show']);
    Route::post('/pickupcenter/add', [PickupCentersController::class, 'store']);
    Route::post('/pickupcenter/update/{id}', [PickupCentersController::class, 'update']);
    Route::post('/pickupcenter/delete/{id}', [PickupCentersController::class, 'destroy']);
    //country
    Route::apiResource('/admin/countries', CountryController::class);
    //marketplace
    Route::apiResource('/marketplaces', MarketPlaceController::class);
    //category
    Route::apiResource('/categories', CategoryController::class);
    //Products
    Route::get('/general/products', [ProductController::class, 'index']);
    Route::get('/general/product/{id}', [ProductController::class, 'show']);
    Route::post('/general/productarray', [ProductController::class, 'getproductgroup']);
    //Stores
    Route::apiResource('/general/stores', StoreController::class);
    //specialitems
    Route::get('/specialitems', [SpecialItemController::class, 'index']);
    Route::get('/specialitem', [SpecialItemController::class, 'getSpecialItem']);

    Route::get('/getestimate', [GeneralController::class, 'getquote']);
    Route::get('/getpickupvehicle', [GeneralController::class, 'fetchvehicles']);

    Route::get('/track', [LogisticsController::class, 'track']);
    Route::get('/subscriptionamount', [GeneralController::class, 'getsubamount']);

    Route::post('/logistics/createshipment3p', [GeneralController::class, 'createshipmentfor3p']);
    Route::get('/track3p', [GeneralController::class, 'trackfor3p']);
    Route::post('/logistics/getquote', [LogisticsController::class, 'getquote']);


});

