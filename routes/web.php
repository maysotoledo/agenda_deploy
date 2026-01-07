<?php

use Illuminate\Support\Facades\Route;
use Filament\Facades\Filament;

Route::get('/', function () {
   // return view('welcome');
    return redirect()->to(Filament::getUrl());
});
