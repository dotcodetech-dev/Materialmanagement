<?php

namespace App\Controllers;

class Scan extends BaseController
{
    public function inward()
    {
        return $this->page('inward');
    }

    public function outward()
    {
        return $this->page('outward');
    }

    private function page(string $direction)
    {
        $types = $direction === 'inward'
            ? ['INWARD', 'RETURN_IN', 'ADJUSTMENT_IN']
            : ['OUTWARD', 'RETURN_OUT', 'ADJUSTMENT_OUT'];

        return view('scan/scan', [
            'title'     => $direction === 'inward' ? 'Inward' : 'Outward',
            'heading'   => $direction === 'inward' ? 'Inward' : 'Outward',
            'direction' => $direction,
            'types'     => $types,
        ]);
    }
}
