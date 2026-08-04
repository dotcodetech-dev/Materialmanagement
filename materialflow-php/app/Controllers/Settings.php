<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Settings extends BaseController
{
    private const BRAND_KEYS = ['company_name', 'tagline', 'logo_icon', 'address', 'phone', 'email'];

    public function index()
    {
        return view('settings/index', [
            'title'   => 'Settings',
            'heading' => 'Settings',
            'brand'   => mf_settings(),
            'users'   => model(UserModel::class)
                ->select('id, full_name, email, role, is_active, created_at')
                ->orderBy('created_at', 'DESC')
                ->findAll(),
            'roles'   => UserModel::ROLES,
        ]);
    }

    public function save()
    {
        $db = db_connect();

        foreach (self::BRAND_KEYS as $key) {
            $value = $this->request->getPost($key);
            if ($value === null) {
                continue;
            }
            $db->query(
                'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)'
                . ' ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
                [$key, (string) $value]
            );
        }

        return redirect()->to('/settings')->with('notice', 'Branding saved.');
    }

    public function createUser()
    {
        $post     = $this->request->getPost();
        $fullName = trim((string) ($post['full_name'] ?? ''));
        $email    = strtolower(trim((string) ($post['email'] ?? '')));
        $password = (string) ($post['password'] ?? '');
        $role     = (string) ($post['role'] ?? 'STOREKEEPER');

        if ($fullName === '' || $email === '' || $password === '') {
            return redirect()->to('/settings')->withInput()->with('error', 'Name, email, and password are required.');
        }
        if (! in_array($role, UserModel::ROLES, true)) {
            return redirect()->to('/settings')->withInput()->with('error', 'Role must be one of: ' . implode(', ', UserModel::ROLES));
        }

        try {
            model(UserModel::class)->insert([
                'full_name'     => $fullName,
                'email'         => $email,
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'role'          => $role,
            ]);
        } catch (DatabaseException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return redirect()->to('/settings')->withInput()->with('error', 'This email is already registered.');
            }
            log_message('error', 'User create failed: ' . $e->getMessage());

            return redirect()->to('/settings')->with('error', 'Could not create user.');
        }

        return redirect()->to('/settings')->with('notice', 'User "' . $fullName . '" created.');
    }

    public function updateUser(string $id)
    {
        $model = model(UserModel::class);
        if ($model->find($id) === null) {
            return redirect()->to('/settings')->with('error', 'User not found.');
        }

        $post = $this->request->getPost();
        $data = [];

        if (trim((string) ($post['full_name'] ?? '')) !== '') {
            $data['full_name'] = trim($post['full_name']);
        }
        if (trim((string) ($post['email'] ?? '')) !== '') {
            $data['email'] = strtolower(trim($post['email']));
        }
        if (($post['role'] ?? '') !== '') {
            if (! in_array($post['role'], UserModel::ROLES, true)) {
                return redirect()->to('/settings')->with('error', 'Invalid role.');
            }
            $data['role'] = $post['role'];
        }
        if (($post['password'] ?? '') !== '') {
            $data['password_hash'] = password_hash($post['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        if (isset($post['is_active'])) {
            if ($id === session('user_id') && ! (int) $post['is_active']) {
                return redirect()->to('/settings')->with('error', 'You cannot deactivate your own account.');
            }
            $data['is_active'] = (int) (bool) $post['is_active'];
        }

        if ($data === []) {
            return redirect()->to('/settings');
        }

        try {
            $model->update($id, $data);
        } catch (DatabaseException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return redirect()->to('/settings')->with('error', 'This email is already registered.');
            }
            log_message('error', 'User update failed: ' . $e->getMessage());

            return redirect()->to('/settings')->with('error', 'Could not update user.');
        }

        return redirect()->to('/settings')->with('notice', 'User updated.');
    }

    public function deactivateUser(string $id)
    {
        if ($id === session('user_id')) {
            return redirect()->to('/settings')->with('error', 'You cannot deactivate your own account.');
        }

        $model = model(UserModel::class);
        if ($model->find($id) === null) {
            return redirect()->to('/settings')->with('error', 'User not found.');
        }

        $model->update($id, ['is_active' => 0]);

        return redirect()->to('/settings')->with('notice', 'User deactivated.');
    }
}
