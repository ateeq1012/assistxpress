<?php
    use App\Helpers\GeneralHelper;
?>
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
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e7eaec;">
                            <h4 style="font-size: 18px; margin: 0; color: #1ab394;">{{ $data['emailTitle'] ?? 'Notification' }}</h4>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px; font-size: 14px;">
                            <p>{!! nl2br(e($data['salutation'] ?? '')) !!}</p>
                            @if(isset($data['sr']))
                                <table cellpadding="3" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Service Domain:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; background-color: {{ $data['sr']['service_domain']['color'] ?? '#ffffff' }}; color:{{ GeneralHelper::invert_color($data['sr']['service_domain']['color'] ?? '#ffffff') }}">{{ $data['sr']['service_domain']['name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Service:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; background-color: {{ $data['sr']['service']['color'] ?? '#ffffff' }}; color:{{ GeneralHelper::invert_color($data['sr']['service']['color'] ?? '#ffffff') }}">{{ $data['sr']['service']['name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Subject:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['subject'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Description:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['description'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Status ID:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; background-color: {{ $data['sr']['status']['color'] ?? '#ffffff' }}; color:{{ GeneralHelper::invert_color($data['sr']['status']['color'] ?? '#ffffff') }}">{{ $data['sr']['status']['name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Priority ID:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; background-color: {{ $data['sr']['priority']['color'] ?? '#ffffff' }}; color:{{ GeneralHelper::invert_color($data['sr']['priority']['color'] ?? '#ffffff') }}">{{ $data['sr']['priority']['name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Created By:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['creator']['name'] ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Response Time:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['response_time'] ?? 'Not responded' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Time to Ownership (TTO):</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['tto'] ? number_format($data['sr']['tto'] / 3600, 2) . ' hours' : 'Not assigned' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Time to Resolution (TTR):</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['ttr'] ? number_format($data['sr']['ttr'] / 3600, 2) . ' hours' : 'Not resolved' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Created At:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['created_at'] ? date('Y-m-d H:i:s', strtotime($data['sr']['created_at'])) : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Updated At:</td>
                                        <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['updated_at'] ? date('Y-m-d H:i:s', strtotime($data['sr']['updated_at'])) : 'N/A' }}</td>
                                    </tr>
                                </table>
                                @if(isset($data['sr']['sla']) && isset($data['sr']['sla_calculations']))
                                    <p>SLA Information</p>
                                    <table cellpadding="3" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                        <tr>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">SLA Rule Name:</td>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px; background-color: {{ $data['sr']['sla']['color'] ?? '#ffffff' }}; color:{{ GeneralHelper::invert_color($data['sr']['sla']['color'] ?? '#ffffff') }}">{{ $data['sr']['sla']['name'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Response Time SLA:</td>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['sla_calculations']['response_time_sla'] ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px; font-weight: bold;">Resolution Time SLA:</td>
                                            <td style="border: 1px solid #e7eaec; padding: 3px 6px;">{{ $data['sr']['sla_calculations']['resolution_time_sla'] ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                @endif
                            @else
                                <p>No Service Request data available.</p>
                            @endif
                            <p>{!! nl2br(e($data['ending'] ?? '')) !!}</p>
                        </td>
                    </tr>
                    @if(isset($data['actionUrl']) && isset($data['actionText']))
                        <tr>
                            <td style="padding: 5px 10px; background-color: #f8f9fa; border-top: 1px solid #e7eaec; text-align: right;">
                                <a href="{{ $data['actionUrl'] }}" style="display: inline-block; padding: 3px 8px; background-color: #1ab394; color: #ffffff; text-decoration: none; border-radius: 3px; font-size: 14px;">{{ $data['actionText'] }}</a>
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