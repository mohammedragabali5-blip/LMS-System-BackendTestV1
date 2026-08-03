<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد البريد الإلكتروني</title>
    <style>
        /* Inline styles are safer, but put some here for clients that support */
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .inner-padding { padding: 20px 15px !important; }
            .button { padding: 12px 20px !important; font-size: 14px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Tahoma, Arial, sans-serif;">

    <!-- Main table -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5; padding:20px 0;">
        <tr>
            <td align="center">
                <!-- Container -->
                <table class="container" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:15px; box-shadow:0 5px 20px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="inner-padding" style="padding:40px 30px; text-align:center;">
                            <!-- Optional logo -->
                            <img src="https://raw.githubusercontent.com/alqys281fare-create/LMS-System/main/LOGO.png" alt="الشعار" style="max-height:250px; width:auto; margin-bottom:5px;">
                            <div style="
                                color:#006666;
                                font-size:18px;
                                font-weight:bold;
                                margin-top:10px;
                            ">
                               للخدمات التعليمية
                            </div>
                            <h1 style="color:#01477c; font-size:24px; margin:0 0 10px;">✔ تأكيد البريد الإلكتروني</h1>

                            <p style="font-size:16px; color:#333; line-height:1.6;">
                                مرحباً {{ $user->name ?? 'عميلنا العزيز' }}،
                            </p>
                            <p style="font-size:16px; color:#555; line-height:1.6;">
                                شكراً لتسجيلك في منصة سبل للتعلم الإلكترونى. يرجى تأكيد بريدك الإلكتروني بالضغط على الزر أدناه.
                            </p>

                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin:30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $url }}" class="button" style="display:inline-block; background:#2563eb; color:#ffffff; padding:14px 28px; font-size:16px; font-weight:bold; text-decoration:none; border-radius:8px;">
                                            تأكيد البريد الإلكتروني
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size:14px; color:#999; line-height:1.5;">
                                إذا لم تقم بإنشاء هذا الحساب، يمكنك تجاهل هذه الرسالة.
                            </p>
                            <p style="font-size:14px; color:#999; line-height:1.5;">
                                تنتهي صلاحية الرابط خلال {{ config('auth.verification.expire', 60) }} دقيقة.
                            </p>

                            <hr style="border:0; border-top:1px solid #eee; margin:25px 0;">

                            <p style="font-size:12px; color:#aaa; text-align:center;">
                                &copy; {{ date('Y') }} . جميع الحقوق محفوظة لمنصة وأكاديمية سبل لتعلم الإلكترونى
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

</body>
</html>