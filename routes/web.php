<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CategoriesController;
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
Route::get('/test',[ProductsController::class,'test'])->name('test');
Route::get('/testdata',[ProductsController::class,'testdata'])->name('testdata');
Route::get('/testcategory',[CategoriesController::class,'syncCategory'])->name('syncCategory');