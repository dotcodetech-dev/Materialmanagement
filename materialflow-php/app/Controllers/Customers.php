<?php

namespace App\Controllers;

use App\Models\CustomerModel;

class Customers extends BaseController
{
    public function index()
    {
        return view('customers/index', [
            'title'     => 'Customers',
            'heading'   => 'Customers',
            'customers' => model(CustomerModel::class)->activeCustomers(),
            'canEdit'   => $this->canEdit(),
        ]);
    }

    public function store()
    {
        [$data, $error] = $this->validated();
        if ($error !== null) {
            return redirect()->to('/customers')->withInput()->with('error', $error);
        }

        model(CustomerModel::class)->insert($data);

        return redirect()->to('/customers')->with('notice', 'Customer "' . $data['name'] . '" saved.');
    }

    public function edit(string $id)
    {
        $customer = model(CustomerModel::class)->where(['id' => $id, 'is_active' => 1])->first();
        if ($customer === null) {
            return redirect()->to('/customers')->with('error', 'Customer not found.');
        }

        return view('customers/form', [
            'title'    => 'Edit customer',
            'heading'  => 'Edit customer',
            'customer' => $customer,
        ]);
    }

    public function update(string $id)
    {
        $model = model(CustomerModel::class);
        if ($model->where(['id' => $id, 'is_active' => 1])->first() === null) {
            return redirect()->to('/customers')->with('error', 'Customer not found.');
        }

        [$data, $error] = $this->validated();
        if ($error !== null) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $model->update($id, $data);

        return redirect()->to('/customers')->with('notice', 'Customer updated.');
    }

    public function deactivate(string $id)
    {
        $model = model(CustomerModel::class);
        if ($model->where(['id' => $id, 'is_active' => 1])->first() === null) {
            return redirect()->to('/customers')->with('error', 'Customer not found.');
        }

        $model->update($id, ['is_active' => 0]);

        return redirect()->to('/customers')->with('notice', 'Customer deleted.');
    }

    /**
     * @return array{0: array, 1: ?string}
     */
    private function validated(): array
    {
        $post = $this->request->getPost();

        $data = [
            'name'    => trim((string) ($post['name'] ?? '')),
            'phone'   => trim((string) ($post['phone'] ?? '')) ?: null,
            'email'   => trim((string) ($post['email'] ?? '')) ?: null,
            'address' => trim((string) ($post['address'] ?? '')) ?: null,
        ];

        if ($data['name'] === '') {
            return [$data, 'Customer name is required.'];
        }

        return [$data, null];
    }
}
