<?php

use App\Http\Controllers\API\V1\AccountController;
use App\Http\Controllers\API\V1\Admin\State\StateController;
use App\Http\Controllers\API\V1\Auth\CustomerAuthController;
use App\Http\Controllers\API\V1\BankController;
use App\Http\Controllers\API\V1\Customer\CustomerController;
use App\Http\Controllers\API\V1\Customer\LogisticsController;
use App\Http\Controllers\API\V1\DepositHistoryController;
use App\Http\Controllers\API\V1\PickupCenterController;
use App\Http\Controllers\API\V1\RegionController;
use App\Http\Controllers\API\V1\SpecialItemController;
use App\Http\Controllers\API\V1\TransactionController;
use App\Http\Controllers\API\V1\WithdrawalController;
use App\Models\DepositHistory;
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
        Route::get('/customer/fetchprofile', [CustomerController::class, 'editprofile']);
        Route::post('/customer/updateprofile', [CustomerController::class, 'updateprofile']);
        Route::get('/customer/listaddress', [CustomerController::class, 'listaddress']);
        Route::post('/customer/addaddress', [CustomerController::class, 'addaddress']);
        Route::post('/customer/updateaddress', [CustomerController::class, 'editaddress']);
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

        //Complaint
        Route::get('/customer/complaint/fetchall', [ComplaintController::class, 'index']);
        Route::post('/customer/complaint/makeacomplain', [ComplaintController::class, 'addcomplaint']);

        //Logistics
        Route::get('/customer/logistics/fetchall', [LogisticsController::class, 'index']);
        Route::post('/customer/logistics/makelogistics', [LogisticsController::class, 'store']);
        Route::post('/customer/logistics/getquote', [LogisticsController::class, 'getquote']);

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
        Route::get('/customer/funding/getfunding/{id}', [TransactionController::class, 'show']);
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
    Route::get('/regions', [RegionController::class, 'index']);
    Route::get('/region', [RegionController::class, 'getRegionName']);
    //pickupcenters
    Route::get('/pickupcenters', [PickupCenterController::class, 'index']);
    Route::get('/pickupcenter', [PickupCenterController::class, 'getCenter']);
    //specialitems
    Route::get('/specialitems', [SpecialItemController::class, 'index']);
    Route::get('/specialitem', [SpecialItemController::class, 'getSpecialItem']);

});

