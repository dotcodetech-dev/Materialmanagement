<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('user_id')) {
            return null;
        }

        if (self::wantsJson($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Unauthorized']);
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    public static function wantsJson(RequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/api/')
            || $request->hasHeader('X-Requested-With');
    }
}
