<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign-In Notification</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; font-family:'Segoe UI', Arial, sans-serif; color:#162645;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center" style="padding:24px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:600px; max-width:100%; background:#ffffff; border-radius:28px; overflow:hidden; box-shadow:0 20px 60px rgba(20,30,61,0.12);">
                    <!-- Header -->
                    <tr>
                        <td style="padding:32px; background:linear-gradient(120deg,#4d64ff 0%,#2a3b8f 100%);">
                            <table width="100%">
                                <tr>
                                    <td style="color:#ffffff;">
                                        <div style="font-size:32px; font-weight:700; letter-spacing:0.5px;">PinPoint</div>
                                        <div style="font-size:16px; opacity:0.85;">Attendance Notification</div>
                                    </td>
                                    <td align="right" style="color:#f3f5ff; font-size:13px;">
                                        {{ now()->format('l, F j, Y • g:i A') }}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Greeting -->
                    <tr>
                        <td style="padding:32px 36px 12px 36px;">
                            <div style="font-size:17px; color:#0f1e3d; margin-bottom:8px;">Hello {{ $guardianName }},</div>
                            <div style="font-size:15px; color:#4b5675;">
                                Your child <strong style="color:#0f1e3d;">{{ $studentName }}</strong> has just signed in.
                            </div>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td style="padding:0 36px 32px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f7f9ff; border:1px solid #e0e6ff; border-radius:20px; padding:24px;">
                                <tr>
                                    <td style="font-size:13px; letter-spacing:1.2px; color:#2a3b8f; text-transform:uppercase; padding-bottom:8px;">Attendance</td>
                                </tr>
                                <tr>
                                    <td style="font-size:22px; font-weight:700; color:#0f1e3d; padding-bottom:18px;">Sign-In Details</td>
                                </tr>
                                <!-- Details rows -->
                                @foreach ([
                                    ['label' => 'Class', 'value' => $className],
                                    ['label' => 'Teacher', 'value' => $teacherName],
                                    ['label' => 'Date', 'value' => $signInDate],
                                    ['label' => 'Time', 'value' => $signInTime],
                                    ['label' => 'Building', 'value' => $buildingName ?? 'Assigned Building'],
                                ] as $item)
                                <tr>
                                    <td style="padding:10px 0; border-bottom:1px solid #e4e9ff;">
                                        <table width="100%">
                                            <tr>
                                                <td style="font-size:13px; text-transform:uppercase; letter-spacing:1px; color:#7e8bb6;">{{ $item['label'] }}</td>
                                                <td align="right" style="font-size:15px; color:#0f1e3d; font-weight:600;">{{ $item['value'] }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td style="padding:16px 0 0 0;">
                                        <table width="100%">
                                            <tr>
                                                <td style="font-size:13px; text-transform:uppercase; letter-spacing:1px; color:#7e8bb6;">Status</td>
                                                <td align="right">
                                                    <span style="
                                                        display:inline-block;
                                                        padding:8px 18px;
                                                        border-radius:999px;
                                                        font-size:13px;
                                                        font-weight:600;
                                                        letter-spacing:0.5px;
                                                        background:{{ $status === 'On Time' ? '#e7f8ef' : '#fff1eb' }};
                                                        color:{{ $status === 'On Time' ? '#118744' : '#c04800' }};
                                                    ">
                                                        {{ $status === 'On Time' ? '✓ On Time' : '⚠ Late' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Alerts -->
                    @if(!$isWithinGeofence || $status === 'Late')
                    <tr>
                        <td style="padding:0 36px 24px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-radius:18px; border:1px solid #ffe1c6; background:#fff9f3; padding:18px 20px;">
                                <tr>
                                    <td width="32" valign="top" style="font-size:20px;">📍</td>
                                    <td style="font-size:14px; color:#8a4b05;">
                                        <strong>Heads up!</strong><br>
                                        @if(!$isWithinGeofence)
                                            Your child signed in away from {{ $buildingName ?? 'the assigned building' }}.
                                        @endif
                                        @if(!$isWithinGeofence && $status === 'Late')<br>@endif
                                        @if($status === 'Late')
                                            They were also marked as late for this class.
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif

                    <!-- Footer -->
                    <tr>
                        <td style="padding:0 36px 36px 36px;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ffffff; border:1px solid #ebeffa; border-radius:18px; padding:20px;">
                                <tr>
                                    <td style="font-size:13px; color:#6c789b; line-height:1.6;">
                                        You're receiving this automated notification because your email address is registered
                                        as the guardian contact for <strong>{{ $studentName }}</strong>.
                                        If you have any questions, please contact your school administrator.
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top:16px; font-size:12px; color:#9aa3c1; text-transform:uppercase; letter-spacing:1.2px;">
                                        PinPoint Attendance • Real-time tracking for peace of mind
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

