<?php

declare(strict_types=1);

namespace Fisher\SSO\API\Controllers;

class HomeController
{
    public function index()
    {
    	// $dept = app('ssoService')->client()->getDepartmenets(212);
    	dd(getSize(107374182142111, false));
        return trans('sso::messages.success');
    }
}
