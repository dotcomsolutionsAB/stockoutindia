<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Product Listed Confirmation</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#111;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;padding:24px 0;">
    <tr>
      <td align="center">
        <table width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:92%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.08);">

          <tr>
            <td style="padding:18px 22px;background:#0f172a;color:#fff;">
              <div style="font-size:16px;font-weight:700;">{{ config('app.name') }}</div>
              <div style="font-size:13px;opacity:.9;margin-top:4px;">Product listed successfully</div>
            </td>
          </tr>

          <tr>
            <td style="padding:22px;">
              <div style="font-size:14px;line-height:1.6;">
                Hi <b>{{ $user->name ?? 'User' }}</b>,<br>
                Your payment is successful and your product is now <b>Active</b>.
              </div>

              <div style="margin-top:16px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <div style="padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                  <div style="font-size:14px;font-weight:700;">Product Details</div>
                </div>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;width:40%;font-size:13px;color:#475569;">Product ID</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;"><b>{{ $product->id }}</b></td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Product Name</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;"><b>{{ $product->product_name }}</b></td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Original Price</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->original_price }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Selling Price</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->selling_price }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Offer Quantity</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->offer_quantity }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Minimum Quantity</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->minimum_quantity }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Unit</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->unit }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">City</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->city ?? '-' }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">State ID</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $product->state_id ?? '-' }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;font-size:13px;color:#475569;">Validity</td>
                    <td style="padding:10px 16px;font-size:13px;"><b>{{ $validity ?? '-' }}</b></td>
                  </tr>
                </table>
              </div>

              <div style="margin-top:16px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <div style="padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                  <div style="font-size:14px;font-weight:700;">Payment Details</div>
                </div>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                  <tr>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;width:40%;font-size:13px;color:#475569;">Razorpay Payment ID</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $razorpay_payment_id ?? '-' }}</td>
                  </tr>
                  <tr>
                    <td style="padding:10px 16px;font-size:13px;color:#475569;">Mode</td>
                    <td style="padding:10px 16px;font-size:13px;">{{ $mode_of_payment ?? '-' }}</td>
                  </tr>
                </table>
              </div>

              <div style="margin-top:18px;font-size:13px;color:#475569;">
                Thanks,<br>
                <b>{{ config('app.name') }}</b>
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:14px 22px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.5;">
              This is an automated email. Please do not reply.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
