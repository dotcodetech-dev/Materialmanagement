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
        'admin'     => ['ADMIN'],
        'editor'    => ['ADMIN', 'MANAGER', 'STOREKEEPER'],
        // STAFF can scan (inward/outward) but cannot edit items/customers.
        'scanner'   => ['ADMIN', 'MANAGER', 'STOREKEEPER', 'STAFF'],
        // Everything STAFF may NOT see (dashboard, ledger, reports, batches, customers).
        'non_staff' => ['ADMIN', 'MANAGER', 'STOREKEEPER', 'VIEWER'],
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

        // STAFF has no dashboard — send them to their landing page instead.
        $landing = session('role') === 'STAFF' ? '/inward' : '/';

        return redirect()->to($landing);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
