<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\rowDataToCsvController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/createCsvFromData',[rowDataToCsvController::class,'createCsvFromData'])->name('createCsvFromData');
Route::get('/synccategory',[CategoriesController::class,'syncCategory'])->name('syncCategory');
Route::get('/syncproduct',[ProductsController::class,'syncProduct'])->name('syncProduct');
