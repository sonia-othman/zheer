@component('mail::message')
# Password Reset

Click the button below to reset your password:

@component('mail::button', ['url' => route('password.reset', [
    'token' => $token,
    'email' => $user->email
])])
Reset Password
@endcomponent

This link will expire in {{ config('auth.passwords.users.expire') }} minutes.

If you didn't request a password reset, please ignore this email.
@endcomponent