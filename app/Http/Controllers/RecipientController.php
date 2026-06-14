<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PaulAdams985\Core\Types\Tenant;

class RecipientController extends Controller
{
    public function preview(string $tenant, string $voucher_share): RedirectResponse
    {
        $endpoint = Tenant::from($tenant)->endpoint();

        $path = Str::replaceFirst('/recipient', '/recipient-inbound', request()->getRequestUri());

        return redirect()->away($endpoint.$path);
    }

    public function show(string $tenant, string $recipient_voucher): View
    {
        return view('recipient.download-app');
    }
}
