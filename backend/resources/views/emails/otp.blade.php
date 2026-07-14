<x-mail::message>
# Republic of Ghana — Birth & Death Registry Portal

Your verification code for {{ $purpose === 'login' ? 'signing in' : 'creating your account' }} is:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

This code expires in 10 minutes. If you didn't request this, you can safely ignore this email.

Thanks,<br>
HBDRP
</x-mail::message>
