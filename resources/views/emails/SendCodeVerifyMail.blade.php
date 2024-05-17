<x-mail::message>
Hello {{ $name }}! <br><br>

We accept orders to verify your email ! <br><br>
This the code to verify your email and is only valid for 30 minutes!
<br>

# <h2 style="text-align: center; font-size: 40px; color: #1a202c; letter-spacing: 15px">{{ $code }}</h2>

Thanks,<br> <br>
{{ config('app.name') }}
</x-mail::message>
