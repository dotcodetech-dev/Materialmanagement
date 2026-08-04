<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['url', 'form', 'uuid', 'settings'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);
    }

    /**
     * The logged-in user from the session, shaped like the old SessionUser.
     */
    protected function currentUser(): ?array
    {
        if (! session('user_id')) {
            return null;
        }

        return [
            'id'       => session('user_id'),
            'fullName' => session('full_name'),
            'email'    => session('email'),
            'role'     => session('role'),
        ];
    }

    protected function jsonError(string $message, int $status = 400, array $extra = []): ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON(['error' => $message] + $extra);
    }
}
