<?php

declare(strict_types=1);

namespace Fisher\SSO\API\Controllers;

class HomeController
{
    public function index()
    {
        return trans('package-sso::messages.success');
    }
}
