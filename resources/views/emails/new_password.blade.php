<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Your New Password</title>
</head>
<body>
    <p>Dear {{ $name ?? 'User' }},</p>

    <p>Your password has been reset. Below is your new login password:</p>

    <p><strong>Password:</strong> {{ $newPassword }}</p>

    <p>Please use this password to log in and change it after login for better security.</p>

    <p>Regards,<br/>{{ config('app.name') }}</p>
</body>
</html>
