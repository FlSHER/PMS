<?php

declare(strict_types=1);

namespace Fisher\Schedule\Web\Controllers;

class HomeController
{
    public function index()
    {
        return view('schedule::welcome');
    }
}
