<?php
/**
 * Created by PhpStorm.
 * User: Fisher
 * Date: 2018/4/1 0001
 * Time: 20:46
 */

namespace App\Services\SSO;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

class OAUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        $user = app('curl')
            ->get(config('oa.host') . '/api/staff/' . $identifier);
        return $this->getOAUser($user);
    }

    public function retrieveByToken($identifier, $token)
    {
        // TODO: Implement retrieveByToken() method.
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    public function retrieveByCredentials(array $credentials)
    {
        $credentials['Accept'] = 'application/json';
        $user = app('curl')->setHeader($credentials)
            ->get(config('oa.host') . '/api/current-user');
        return $this->getOAUser($user);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        // TODO: Implement validateCredentials() method.
    }

    protected function getOAUser($user)
    {
        if (is_array($user) && array_has($user, 'staff_sn')) {
            return new OAUser($user);
        }
    }

}