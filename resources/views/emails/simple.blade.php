<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'INX Helpdesk Notification' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f3f4; font-family: Arial, Helvetica, sans-serif; color: #676a6c; line-height: 1.6;">

    <table cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f3f4; padding: 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 4px; border: 1px solid #e7eaec;">
                    <!-- Modal Header -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e7eaec;">
                            <h4 style="font-size: 18px; margin: 0; color: #1ab394;">{{ $emailTitle ?? 'Notification' }}</h4>
                        </td>
                    </tr>
                    <!-- Modal Body -->
                    <tr>
                        <td style="padding: 20px; font-size: 14px;">
                            <p>{!! nl2br(e($body ?? 'No message provided.')) !!}</p>
                        </td>
                    </tr>
                    <!-- Modal Footer -->
                    @if(isset($actionUrl) && isset($actionText))
                        <tr>
                            <td style="padding: 10px 20px; background-color: #f8f9fa; border-top: 1px solid #e7eaec; text-align: right;">
                                    <a href="{{ $actionUrl }}" style="display: inline-block; padding: 5px 10px; background-color: #1ab394; color: #ffffff; text-decoration: none; border-radius: 3px; font-size: 14px; margin-left: 10px;">{{ $actionText }}</a>
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-top: 10px; font-size: 12px; color: #676a6c;">
                <p>Sent by INX Helpdesk | {{ date('Y') }} © All rights reserved.</p>
            </td>
        </tr>
    </table>
</body>
</html>