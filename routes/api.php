<?php

use App\Http\Controllers\API\V1\Admin\State\StateController;
use App\Http\Controllers\API\V1\Auth\CustomerAuthController;
use App\Http\Controllers\API\V1\Customer\CustomerController;
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
        Route::post('/customer/updateprofile', [CustomerController::class, 'changeprofile']);
        Route::post('/customer/updateaddress', [CustomerController::class, 'changeaddressinfo']);
        Route::post('/customer/updateemail', [CustomerController::class, 'changeemail']);
        Route::post('/customer/changeprofilepicture', [CustomerController::class, 'uploadimage']);
        Route::post('/customer/changepassword', [CustomerController::class, 'changepassword']);
        Route::post('/customer/deleteaccount', [CustomerController::class, 'deleteaccount']);
        Route::post('/customer/deactivateaccount', [CustomerController::class, 'deactivateaccount']);
        Route::post('/customer/toggleemailnotification', [CustomerController::class, 'toggleemailnotification']);
        Route::post('/customer/toggledesktopnotification', [CustomerController::class, 'toggledesktopnotification']);
        Route::post('/customer/togglesubscriptionduenotification', [CustomerController::class, 'togglesubscriptionduenotification']);
        Route::post('/customer/togglecheckupschedulednotification', [CustomerController::class, 'togglecheckupschedulednotification']);

        //Complaint
        Route::get('/customer/complaint/fetchall', [ComplaintController::class, 'index']);
        Route::post('/customer/complaint/makeacomplain', [ComplaintController::class, 'addcomplaint']);
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

        Route::middleware(['restrictothers'])->group(function () {
            Route::post('/admin/create', [AdminController::class, 'register']);
            Route::post('/admin/edit/{admin}', [AdminController::class, 'update']);
            Route::get('/admin/get-admins', [AdminController::class, 'index']);
            Route::delete('/admin/delete/{admin}', [AdminController::class, 'destroy']);
        });

    });
    Route::post('/admin/auth/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/auth/verifyotp', [AdminAuthController::class, 'check_otp']);


    Route::middleware(['auth:sanctum'])->group(function () {
        //account
        Route::apiResource('/user/accounts', AccountController::class);
    });

    //states
    Route::apiResource('/states', StateController::class);

});

