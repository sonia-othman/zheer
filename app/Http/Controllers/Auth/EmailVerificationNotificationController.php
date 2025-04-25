<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    // app/Http/Controllers/Auth/EmailVerificationNotificationController.php

public function store(Request $request)
{
    if ($request->user()->hasVerifiedEmail()) {
        return redirect()->intended('/dashboard');
    }

    // Throttle to 1 email per minute
    if (RateLimiter::tooManyAttempts('verification:'.$request->ip(), 1)) {
        return back()->withErrors([
            'email' => 'Please wait before requesting another verification email.'
        ]);
    }

    RateLimiter::hit('verification:'.$request->ip(), 60);
    
    $request->user()->sendEmailVerificationNotification();

    return back()->with('status', 'verification-link-sent');
}
}