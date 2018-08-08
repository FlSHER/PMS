<?php

declare(strict_types=1);

namespace Fisher\Schedule\API\Controllers;

class HomeController
{
    public function index()
    {
        return trans('schedule::messages.success');
    }
}
