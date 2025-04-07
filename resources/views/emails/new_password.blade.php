<!DOCTYPE html>
<html>
<head>
    <title>Your New Password</title>
</head>
<body>
    <p>Dear {{ $name }},</p>

    <p>Your password has been reset. Below is your new login password:</p>

    <p><strong>Password:</strong> {{ $password }}</p>

    <p>Please use this password to log in and consider changing it after login for better security.</p>

    <p>Regards,<br/>Team Stock-out India</p>
</body>
</html>
