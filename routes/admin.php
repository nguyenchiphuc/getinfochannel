<?php
// Route xử lý cho admin
Route::namespace('Admin')->group(function () {
   Route::get('/logs', array('as' => 'admin.logs', 'uses' => 'V1\LogController@index'));
});
