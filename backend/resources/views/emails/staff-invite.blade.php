<x-mail::message>
# Welcome to HBDRP Back-Office

Hi {{ $fullName }},

An account has been created for you on the Harmonized Births and Deaths
Registry Portal back-office.

- **Staff ID:** {{ $staffId }}
- **Role:** {{ $role }}
- **Temporary password:** {{ $temporaryPassword }}

Please sign in and change your password as soon as possible.

Thanks,<br>
HBDRP
</x-mail::message>
