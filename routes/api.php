<?php

use App\Http\Controllers\Api\AppDownloadController;
use App\Http\Controllers\Api\OpreationUser;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/
    Route::post("/create_device",[OpreationUser::class,'create_device']);
    Route::get("/getDevice",[OpreationUser::class,'get_device']);
    Route::post("/activateDevice",[OpreationUser::class,'activate_device']);
    Route::get("/getPlans",[OpreationUser::class,'get_plans']);
    Route::post("/check_device",[OpreationUser::class,'checkDevice']);
    Route::post("/update_my_data",[OpreationUser::class,'updateMyData']);
    Route::get("/send_plan_notifications",[OpreationUser::class,'sendPlanNotifications']);
    Route::get("/test_send_notifications",[OpreationUser::class,'testSendNotifications']);
    Route::get('/app-download', [AppDownloadController::class, 'index'])->name('app.download');
    Route::post('/add_review', [AppDownloadController::class, 'add_review']);
