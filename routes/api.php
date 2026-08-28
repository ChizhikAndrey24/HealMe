<?php

use Illuminate\Support\Facades\Route;

/*
| API routes historically hosted public triage/match endpoints.
| Those endpoints now require session auth + permissions under routes/web.php
| so CSRF/session cookies apply consistently with the SPA.
*/

Route::middleware('api')->group(function (): void {
    //
});
