<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use PaulAdams985\Core\Types\Tenant;

class RecipientController extends Controller
{
    public function preview(string $tenant, string $voucher_share): RedirectResponse
    {
        $endpoint = Tenant::from($tenant)->endpoint();

        return redirect()->away($endpoint.request()->getRequestUri());
    }

    public function show(string $tenant, string $recipient_voucher): View
    {
        return view('recipient.download-app');
    }
}
