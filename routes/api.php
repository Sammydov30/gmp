<?php

use App\Http\Controllers\API\V1\AccountController;
use App\Http\Controllers\API\V1\Admin\Customer\CustomerController as CustomerCustomerController;
use App\Http\Controllers\API\V1\Admin\Order\OrderController as OrderOrderController;
use App\Http\Controllers\API\V1\Admin\Plan\PlanController;
use App\Http\Controllers\API\V1\Admin\State\StateController;
use App\Http\Controllers\API\V1\Auth\AdminAuthController;
use App\Http\Controllers\API\V1\Auth\CustomerAuthController;
use App\Http\Controllers\API\V1\Auth\RepController;
use App\Http\Controllers\API\V1\BankController;
use App\Http\Controllers\API\V1\CategoryController;
use App\Http\Controllers\API\V1\CountryController;
use App\Http\Controllers\API\V1\Customer\CustomerController;
use App\Http\Controllers\API\V1\Customer\LogisticsController;
use App\Http\Controllers\API\V1\DepositHistoryController;
use App\Http\Controllers\API\V1\CartController;
use App\Http\Controllers\API\V1\ConversationController;
use App\Http\Controllers\API\V1\Customer\ShipmentController;
use App\Http\Controllers\API\V1\FeedBackRatingController;
use App\Http\Controllers\API\V1\GeneralController;
use App\Http\Controllers\API\V1\GeneralStoreController;
use App\Http\Controllers\API\V1\HaulageController;
use App\Http\Controllers\API\V1\MarketPlaceController;
use App\Http\Controllers\API\V1\MessageController;
use App\Http\Controllers\API\V1\MonnifyWebHookController;
use App\Http\Controllers\API\V1\NotificationController;
use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\OrderReportController;
use App\Http\Controllers\API\V1\PickupCenterController;
use App\Http\Controllers\API\V1\PickupCentersController;
use App\Http\Controllers\API\V1\ProductController;
use App\Http\Controllers\API\V1\ProductForAdminController;
use App\Http\Controllers\API\V1\ProductReportingController;
use App\Http\Controllers\API\V1\RegionController;
use App\Http\Controllers\API\V1\RegionsController;
use App\Http\Controllers\API\V1\SpecialItemController;
use App\Http\Controllers\API\V1\SpecialItemsController;
use App\Http\Controllers\API\V1\StoreController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\TransactionController;
use App\Http\Controllers\API\V1\WishlistController;
use App\Http\Controllers\API\V1\WithdrawalController;
use Illuminate\Support\Facades\Broadcast;
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
        Route::post('/customer/setdefaultaddress', [CustomerController::class, 'setdefaultaddress']);
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
        Route::post('/customer/order/feedback/updatefeedback/{id}', [FeedBackRatingController::class, 'update']);

        //Product Report
        Route::get('/product/report/fetchall', [ProductReportingController::class, 'index']);
        Route::post('/product/report/addreport', [ProductReportingController::class, 'store']);

        //Logistics
        Route::get('/customer/logistics/fetchall', [ShipmentController::class, 'index']);
        Route::get('/customer/logistics/fetchrecent', [ShipmentController::class, 'fetchrecent']);
        Route::get('/customer/logistics/fetch', [ShipmentController::class, 'getshipment']);
        Route::get('/customer/logistics/track', [ShipmentController::class, 'track']);
        Route::post('/customer/logistics/makelogistics', [ShipmentController::class, 'store']);
        Route::post('/customer/logistics/getquote', [ShipmentController::class, 'getquote']);
        Route::post('/customer/logistics/verifypayment', [ShipmentController::class, 'verifypayment']);

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
        Route::post('/customer/product/topup/{id}', [ProductController::class, 'topup']);
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
        Route::post('/customer/cart/getshippingratesingle', [CartController::class, 'getShippingRate2']);
        Route::post('/customer/cart/addtoorder', [CartController::class, 'addToOrder']);
        Route::post('/customer/cart/buynow', [CartController::class, 'BuyNow']);
        Route::post('/customer/cart/verifypayment', [CartController::class, 'verifypayment']);
        Route::post('/customer/cart/verifypaymentbuynow', [CartController::class, 'verifypaymentbuynow']);

        //wishlist
        Route::get('/customer/wishlist/getwishlistitems', [WishlistController::class, 'index']);
        Route::post('/customer/wishlist/addtowishlist', [WishlistController::class, 'addtowishlist']);
        Route::post('/customer/wishlist/removefromwishlist', [WishlistController::class, 'removefromwishlist']);
        Route::post('/customer/wishlist/removeproductfromwishlist', [WishlistController::class, 'removeproductfromwishlist']);
        Route::post('/customer/wishlist/addtocartfromwishlist', [WishlistController::class, 'addtocartfromwishlist']);
        Route::get('/customer/wishlist/getwishlistItemsarray', [WishlistController::class, 'getwishlistItemsarray']);
        //Route::get('/customer/wishlist/confirmavailability', [WishlistController::class, 'confirmavailability']);
        // Route::get('/customer/wishlist/checkout', [WishlistController::class, 'checkout']);
        // Route::post('/customer/wishlist/addtoorder', [WishlistController::class, 'addToOrder']);
        // Route::post('/customer/wishlist/buynow', [WishlistController::class, 'BuyNow']);
        // Route::post('/customer/wishlist/verifypayment', [WishlistController::class, 'verifypayment']);

        //orders
        Route::get('/customer/order/getorders', [OrderController::class, 'index']);
        Route::get('/all/order/getorderitems', [OrderController::class, 'getOrderItems']);
        Route::get('/customer/order/getorder', [OrderController::class, 'getSingleOrder']);

        //Order Report
        Route::get('/seller/order/report/fetchall', [OrderReportController::class, 'index']);
        Route::post('/seller/order/report/makereport', [OrderReportController::class, 'store']);

        //Seller Action
        Route::get('/seller/order/getorders', [OrderController::class, 'sellerorderlist']);
        Route::get('/seller/order/getorder', [OrderController::class, 'getSingleOrder']);
        Route::post('/seller/order/markaccepted', [OrderController::class, 'markAccepted']);
        Route::post('/seller/order/markready', [OrderController::class, 'markReady']);
        Route::post('/seller/order/markcancelled', [OrderController::class, 'markCancelled']);

        //Analytics
        Route::get('/seller/analytics/items/details', [GeneralController::class, 'countItems']);
        Route::get('/seller/analytics/sales/details', [GeneralController::class, 'countSales']);
        Route::get('/seller/analytics/sales/saleschart', [GeneralController::class, 'saleschart']);
        Route::get('/seller/analytics/customers/details', [GeneralController::class, 'countCustomers']);


        //Notification
        Route::get('/customer/notification/fetchnotifications', [NotificationController::class, 'fetchnotificationforuser']);
        Route::get('/customer/notification/getnotificationcount', [NotificationController::class, 'getnotificationcount']);
        Route::post('/customer/notification/markread', [NotificationController::class, 'readnotification']);
        Route::post('/customer/notification/markallread', [NotificationController::class, 'readallnotification']);
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
    Route::get('/marketplaces', [MarketPlaceController::class, 'index2']);
    Route::get('/marketplaces/{id}', [MarketPlaceController::class, 'show']);
    //category
    Route::apiResource('/categories', CategoryController::class);
    //Products
    Route::get('/general/products', [ProductController::class, 'index2']);
    Route::get('/general/product/{id}', [ProductController::class, 'show']);
    Route::post('/general/productarray', [ProductController::class, 'getproductgroup']);
    //Stores
    Route::apiResource('/general/stores', GeneralStoreController::class);
    //specialitems
    // Route::get('/specialitems', [SpecialItemController::class, 'index']);
    // Route::get('/specialitem', [SpecialItemController::class, 'getSpecialItem']);
    Route::get('/specialitems', [SpecialItemsController::class, 'index']);
    Route::get('/specialitem', [SpecialItemsController::class, 'show']);

    Route::get('/getestimate', [GeneralController::class, 'getquote']);
    Route::get('/getpickupvehicle', [GeneralController::class, 'fetchvehicles']);

    Route::get('/track', [ShipmentController::class, 'track']);
    Route::get('/subscriptionamount', [GeneralController::class, 'getsubamount']);

    Route::post('/logistics/createshipment3p', [GeneralController::class, 'createshipmentfor3p']);
    Route::get('/track3p', [GeneralController::class, 'trackfor3p']);
    Route::post('/logistics/getquote', [LogisticsController::class, 'getquote']);
    Route::get('/getirspricelist', [GeneralController::class, 'getirspricelist']);

    // Route::post('/customer/order/fillorderitem', [GeneralController::class, 'runquery']);



    // Only for Solvent
    Route::middleware(['bearer.admin_solvent'])->group(function () {
        //marketplace
        Route::apiResource('/admin/marketplaces', MarketPlaceController::class);
        Route::post('/admin/marketplace/changestatus', [MarketPlaceController::class, 'changestatus']);

        //store
        Route::get('/admin/stores', [StoreController::class, 'index2']);
        Route::get('/admin/stores/{id}', [StoreController::class, 'show']);
        Route::delete('/admin/stores/{id}', [StoreController::class, 'destroy']);

        //product
        Route::apiResource('/admin/products', ProductForAdminController::class);
        Route::post('/admin/product/approve', [ProductForAdminController::class, 'approve']);
        Route::post('/admin/product/topup/{id}', [ProductForAdminController::class, 'topup']);

        //customers
        Route::get('/admin/customers', [CustomerCustomerController::class, 'index']);
        Route::get('/admin/customers/show', [CustomerCustomerController::class, 'editprofile']);
        Route::post('/admin/customers/updateprofile', [CustomerCustomerController::class, 'updateprofile']);
        Route::post('/admin/customers/updatepassword', [CustomerCustomerController::class, 'updatepassword']);

        //FundingHistory
        Route::get('/admin/funding/fetchall', [TransactionController::class, 'index']);
        Route::get('/admin/funding/getfunding/{id}', [TransactionController::class, 'show']);

        //orders
        Route::get('/admin/order/getorders', [OrderOrderController::class, 'index']);
        Route::get('/admin/order/getorderitems', [OrderOrderController::class, 'getOrderItems']);
        Route::get('/admin/order/getorder', [OrderOrderController::class, 'getSingleOrder']);

        //Order Action
        Route::post('/admin/order/markaccepted', [OrderOrderController::class, 'markAccepted']);
        Route::post('/admin/order/markready', [OrderOrderController::class, 'markReady']);
        Route::post('/admin/order/markpickedup', [OrderOrderController::class, 'markPickedUp']);
        Route::post('/admin/order/markdelivered', [OrderOrderController::class, 'markDelivered']);
        Route::post('/admin/order/markcancelled', [OrderOrderController::class, 'markCancelled']);

        //category
        Route::apiResource('/admin/categories', CategoryController::class);

        //plan
        Route::apiResource('/admin/plans', PlanController::class);
        //Deposit
        Route::get('/admin/deposit/fetchall', [DepositHistoryController::class, 'index']);
        Route::get('/admin/deposit/getdeposit/{id}', [DepositHistoryController::class, 'show']);

        //Withdrawal
        Route::get('/admin/withdrawal/fetchall', [WithdrawalController::class, 'index']);
        Route::get('/admin/withdrawal/getwithdrawal/{id}', [WithdrawalController::class, 'show']);
    });
    Route::post('/handlemonnifydepositsolvent', [MonnifyWebHookController::class, 'depositSolventAction']);
});

// Broadcast::routes();
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v3')->group(function () {
    Route::post('/conversations', [ConversationController::class, 'startConversation']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'sendMessage']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'fetchMessages']);
    Route::patch('/conversations/{conversation}/close', [ConversationController::class, 'endConversation']);
    Route::get('/conversations/{id}/rep', [ConversationController::class, 'fetchRepConversation']); //fetch rep's conversations



    Route::post('/rep/auth/login', [RepController::class, 'login']);
    Route::post('/rep/auth/register', [RepController::class, 'register']);

});
