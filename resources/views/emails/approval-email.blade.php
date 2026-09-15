<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Approval Request</title>
</head>

<body style="background-color: #f8fafc; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 0; -webkit-font-smoothing: antialiased;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 550px; background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">

                    <!-- Header Branding Area -->
                    <tr>
                        <td style="background-color: #002e5d; padding: 32px; text-align: center;">
                            <h1 style="color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: 2px; margin: 0; font-family: 'Poppins', Arial, sans-serif;">
                                Schlemmer Indonesia <span style="color: #11caa0; font-weight: 400;">R&D System</span>
                            </h1>
                        </td>
                    </tr>

                    <!-- Content Body -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="color: #1e293b; font-size: 18px; font-weight: 700; margin-top: 0; margin-bottom: 16px;">
                                Hello, {{ $notifiable->name }}
                            </h2>
                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
                                There is a new <strong>{{ $module }}</strong> document waiting for your review and approval.
                            </p>

                            <!-- Document Details Box -->
                            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                                <p style="color: #334155; font-size: 13px; margin: 0 0 8px 0;">
                                    <strong>Document Code:</strong> <span style="color: #005088; font-weight: 600;">{{ $transaction->code ?? '-' }}</span>
                                </p>
                                <p style="color: #334155; font-size: 13px; margin: 0;">
                                    <strong>Version:</strong> {{ $transaction->version ?? '0' }}
                                </p>
                            </div>

                            <!-- CTA Button Area -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ url('/approvals') }}" target="_blank" style="display: inline-block; background-color: #005088; color: #ffffff; font-size: 14px; font-weight: 700; text-decoration: none; padding: 14px 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 80, 136, 0.2);">
                                            Review Approval
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Info Box -->
                            <div style="background-color: #f1f5f9; border-left: 4px solid #11caa0; border-radius: 6px; padding: 12px 16px; margin-bottom: 24px;">
                                <p style="color: #64748b; font-size: 11px; line-height: 1.5; margin: 0;">
                                    <strong>ℹ️ Notice:</strong> Please log in to the system to check the complete details of process flows and core teams before approving.
                                </p>
                            </div>

                            <!-- Closing Paragraph -->
                            <p style="color: #475569; font-size: 13px; line-height: 1.6; margin-bottom: 0;">
                                Thank you for your prompt attention to this matter.
                                <br><br>
                                Best regards,<br>
                                <strong>Schlemmer Indonesia <br>System Administrator</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Polished Footer Area -->
                    <tr>
                        <td style="background-color: #f8fafc; border-top: 1px solid #f1f5f9; padding: 32px; text-align: center;">
                            <!-- Auto-sent Disclaimer -->
                            <p style="color: #64748b; font-size: 11px; margin: 0 0 16px 0; line-height: 1.6;">
                                This email was sent automatically by the Schlemmer Indonesia Platform.<br>
                                Please do not reply to this message.
                            </p>

                            <!-- Official Address -->
                            <p style="color: #334155; font-size: 11px; margin: 0 0 20px 0; line-height: 1.6; font-weight: 600;">
                                PT Schlemmer Automotive Indonesia<br>
                                <span style="color: #94a3b8; font-weight: 400;">Kawasan Industri Delta Silicon 3, Indonesia</span>
                            </p>

                            <!-- Copyright Notice -->
                            <p style="color: #94a3b8; font-size: 11px; margin: 0; line-height: 1.5; letter-spacing: 0.2px;">
                                &copy; {{ date('Y') }} PT Schlemmer Automotive Indonesia. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>
</body>

</html>