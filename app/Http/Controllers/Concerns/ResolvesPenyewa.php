<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Penyewa;
use Symfony\Component\HttpFoundation\Response;

trait ResolvesPenyewa
{
    /**
     * The penyewa profile tied to the authenticated portal user, or abort.
     */
    protected function currentPenyewa(): Penyewa
    {
        $penyewa = request()->user()?->penyewa;

        abort_if(
            $penyewa === null,
            Response::HTTP_FORBIDDEN,
            'Akun kamu belum tertaut ke data penyewa.',
        );

        return $penyewa;
    }
}
