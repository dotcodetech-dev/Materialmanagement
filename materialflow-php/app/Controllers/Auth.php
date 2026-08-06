<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session('user_id')) {
            return redirect()->to(self::landingFor(session('role')));
        }

        return view('auth/login', ['error' => session()->getFlashdata('error')]);
    }

    private static function landingFor(?string $role): string
    {
        return $role === 'STAFF' ? '/inward' : '/';
    }

    public function attempt()
    {
        $throttler = service('throttler');
        if ($throttler->check('login-' . md5($this->request->getIPAddress()), 5, MINUTE) === false) {
            return $this->loginFailed('Too many attempts. Try again in a minute.');
        }

        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return $this->loginFailed('Email and password are required.');
        }

        $user = model(UserModel::class)->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return $this->loginFailed('Invalid email or password.');
        }

        if (! $user['is_active']) {
            return $this->loginFailed('Account is deactivated. Contact your admin.');
        }

        session()->regenerate(true);
        session()->set([
            'user_id'   => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role'],
        ]);

        return redirect()->to(self::landingFor($user['role']));
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    private function loginFailed(string $message)
    {
        return redirect()->to('/login')->withInput()->with('error', $message);
    }
}
