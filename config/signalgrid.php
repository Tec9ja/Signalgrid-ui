<?php

return [

    /*
    | The first Super Admin is created only when both the email and the
    | password are non-empty. An account that already uses that email keeps
    | its current password. Leave the values blank to skip this step.
    */

    'admin_name' => env('SIGNALGRID_ADMIN_NAME'),
    'admin_email' => env('SIGNALGRID_ADMIN_EMAIL'),
    'admin_password' => env('SIGNALGRID_ADMIN_PASSWORD'),

];
