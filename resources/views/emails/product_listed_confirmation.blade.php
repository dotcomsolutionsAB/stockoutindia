<!DOCTYPE html>
<html>
<body>
    <p>Dear {{ $name }},</p>

    <p>We’re pleased to inform you that your product has been successfully listed on Stockout.</p>

    <p>
        It is now available for viewing by interested buyers. To enhance visibility, we recommend ensuring your listing includes clear images, specifications, and pricing.
    </p>

    <p>
        You may access or edit your listing here:
        <a href="{{ $listingLink }}">{{ $listingLink }}</a>
    </p>

    <p>Thank you for choosing Stockout.<br>Best regards,<br>Team Stockout</p>
</body>
</html>
