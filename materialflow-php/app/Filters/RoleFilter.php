<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Route-level role enforcement. Usage: 'role:editor' or 'role:admin'.
 * Implies authentication (an anonymous request never has a role).
 */
class RoleFilter implements FilterInterface
{
    private const GROUPS = [
        'admin'  => ['ADMIN'],
        'editor' => ['ADMIN', 'MANAGER', 'STOREKEEPER'],
    ];

    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session('user_id')) {
            return (new AuthFilter())->before($request);
        }

        $group   = $arguments[0] ?? 'admin';
        $allowed = self::GROUPS[$group] ?? [];

        if (in_array(session('role'), $allowed, true)) {
            return null;
        }

        if (AuthFilter::wantsJson($request)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['error' => $group === 'admin' ? 'Admin access required.' : 'You do not have permission to do this.']);
        }

        session()->setFlashdata('error', 'You do not have permission to access that page.');

        return redirect()->to('/');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
