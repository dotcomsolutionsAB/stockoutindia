<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Product Created</title>
</head>
<body style="margin:0;padding:0;background:#f5f6f8;font-family:Arial,Helvetica,sans-serif;color:#111;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f6f8;padding:24px 0;">
    <tr>
      <td align="center">
        <table width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:92%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 6px 24px rgba(0,0,0,.08);">
          
          <!-- Header -->
          <tr>
            <td style="padding:18px 22px;background:#b30000;color:#fff;">
              <div style="font-size:16px;font-weight:700;letter-spacing:.2px;">
                {{ config('app.name') }}
              </div>
              <div style="font-size:13px;opacity:.9;margin-top:4px;">
                New product created successfully
              </div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:22px;">
              <div style="font-size:14px;line-height:1.6;">
                Hi <b>{{ $user->name ?? 'User' }}</b>,<br>
                Your product has been created successfully. Details are below:
              </div>

              <!-- Card -->
              <div style="margin-top:16px;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
                <div style="padding:14px 16px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                  <div style="font-size:14px;font-weight:700;">Product Details</div>
                </div>

                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
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
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#475569;">Status</td>
                    <td style="padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;">
                      @if(($product->status ?? '') === 'in-active')
                        <span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;font-weight:700;">
                          in-active
                        </span>
                      @else
                        <span style="display:inline-block;padding:3px 10px;border-radius:999px;background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;font-weight:700;">
                          active
                        </span>
                      @endif
                    </td>
                  </tr>
                </table>
              </div>

              <!-- Payment Button -->
              @if(($product->status ?? '') === 'in-active')
                <div style="margin-top:18px;font-size:13px;line-height:1.6;color:#334155;">
                  Your product is currently <b>in-active</b>. Please complete payment to activate it.
                </div>

                <div style="margin-top:14px;">
                  <a href="{{ $paymentUrl }}"
                     style="display:inline-block;background:#0ea5e9;color:#fff;text-decoration:none;font-weight:700;font-size:14px;padding:12px 18px;border-radius:10px;">
                    Make Payment
                  </a>
                </div>

                <div style="margin-top:10px;font-size:12px;color:#64748b;line-height:1.5;">
                  If the button doesn’t work, copy and paste this link:<br>
                  <span style="word-break:break-all;">{{ $paymentUrl }}</span>
                </div>
              @endif

              <div style="margin-top:18px;font-size:13px;color:#475569;">
                Thanks,<br>
                <b>{{ config('app.name') }}</b>
              </div>
            </td>
          </tr>

          <!-- Footer -->
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
