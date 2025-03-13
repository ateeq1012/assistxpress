<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>{{ $subject ?? 'Notification Email' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f6f6f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .content {
            font-size: 16px;
            color: #333;
            line-height: 1.6;
        }
        .btn-primary {
            display: inline-block;
            padding: 10px 15px;
            margin-top: 20px;
            color: #ffffff;
            background-color: #007bff;
            text-decoration: none;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #777;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<table class="body-wrap">
    <tr>
        <td></td>
        <td class="container">
            <div class="content">
                <table class="main" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="content-wrap">
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td>
                                        <img class="img-fluid" src="{{ asset('img/header.jpg') }}" alt="Header Image" width="100%" />
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block">
                                        <h3>{{ $subject ?? 'Welcome!' }}</h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block">
                                        {!! nl2br(e($body ?? 'No message provided.')) !!}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="content-block aligncenter">
                                        <a href="{{ $actionUrl ?? '#' }}" class="btn-primary">Confirm Email Address</a>
                                    </td>
                                </tr>
                              </table>
                        </td>
                    </tr>
                </table>
                <div class="footer">
                    <table width="100%">
                        <tr>
                            <td class="aligncenter content-block">
                                Follow <a href="https://twitter.com/Company">@Company</a> on Twitter.
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </td>
        <td></td>
    </tr>
</table>

</body>
</html>
