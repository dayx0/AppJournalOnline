<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Trait ini menyediakan $this->authorize() yang dipakai
    // controller Wali (Policy). Tanpa ini: "Call to undefined method".
    use AuthorizesRequests;
}
