<?php

Route::get('/wizard/finish', "Wizard\WizardController@finish")
    ->name('finish_wizard');

Route::get('/wizard/{step}', "Wizard\WizardController@getStep")
        ->name('wizard');

Route::post('/wizard/{step}', "Wizard\WizardController@saveStep")
    ->name('save_wizard');
