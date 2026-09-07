<?php

namespace App\Http\Controllers\Web\Admin;

/**
 * "Hisa anzia" — entrance / opening shares. Identical behaviour to
 * {@see ShareController}, backed by the same table with kind = 'opening'.
 */
class OpeningShareController extends ShareController
{
    protected string $kind = 'opening';
    protected string $rp = 'admin.opening-shares';
    protected string $i18n = 'openingShares';
}
