<?php
/**
 * Created by PhpStorm.
 * User: Fisher
 * Date: 2018/4/1 0001
 * Time: 22:19
 */

namespace App\Services\SSO;


use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

class OAGuard implements Guard
{
    use GuardHelpers;

    protected $request;

    protected $inputKey;

    protected $storageKey;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->inputKey = 'Authorization';
        $this->storageKey = 'Authorization';
    }

    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }
        $user = null;
        $token = $this->getTokenFromHeader();
        if (!empty($token)) {
            $user = $this->provider->retrieveByCredentials(
                [$this->storageKey => $token]
            );
        }
        return $this->user = $user;
    }

    public function validate(array $credentials = [])
    {
        return true;
    }

    protected function getTokenFromHeader()
    {
        $token = $this->request->header($this->inputKey);
        return $token;
    }
}