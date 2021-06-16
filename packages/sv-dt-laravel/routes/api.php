<?php

use Illuminate\Support\Facades\Route;
use SeoulVentures\SvDataTable\Http\Controllers\DataTableApiController;

Route::get('/table/headers', [ DataTableApiController::class, 'headers' ]);
Route::get('/table/data', [ DataTableApiController::class, 'read' ]);
