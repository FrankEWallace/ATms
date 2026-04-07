<?php

namespace App\Http\Controllers;

use App\Traits\TenantAuthorization;

abstract class Controller
{
    use TenantAuthorization;
}
