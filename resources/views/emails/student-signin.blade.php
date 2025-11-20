<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sign-In Notification</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .header p {
            color: #e0e7ff;
            margin: 10px 0 0 0;
            font-size: 16px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 20px;
        }
        .info-box {
            background-color: #f9fafb;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 12px 0;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }
        .info-value {
            color: #1f2937;
            font-weight: 500;
            font-size: 14px;
            text-align: right;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .status-ontime {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-late {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .location-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .location-inside {
            background-color: #dcfce7;
            color: #166534;
        }
        .location-outside {
            background-color: #fef3c7;
            color: #92400e;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            color: #6b7280;
            font-size: 13px;
            margin: 5px 0;
        }
        .emoji {
            font-size: 24px;
            margin-right: 8px;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }
            .content {
                padding: 20px;
            }
            .info-row {
                flex-direction: column;
            }
            .info-value {
                text-align: left;
                margin-top: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="brand">
                <div class="logo">📍</div>
                <div>
                    <h1>PinPoint</h1>
                    <p>Attendance Notification</p>
                </div>
            </div>
            <div class="timestamp">
                {{ now()->format('l • F j, Y • g:i A') }}
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <div class="greeting-card">
                <p class="greeting">Hello {{ $guardianName }},</p>
                <p class="summary">
                    Your child <strong>{{ $studentName }}</strong> has just signed in.
                </p>
            </div>

            <!-- Sign-In Details -->
            <div class="card sign-in-card">
                <div class="card-header">
                    <div class="card-icon">📝</div>
                    <div>
                        <p class="card-eyebrow">Attendance</p>
                        <h3>Sign-In Details</h3>
                    </div>
                </div>

                <div class="details-grid">
                    <div class="detail">
                        <span>Class</span>
                        <p>{{ $className }}</p>
                    </div>
                    <div class="detail">
                        <span>Teacher</span>
                        <p>{{ $teacherName }}</p>
                    </div>
                    <div class="detail">
                        <span>Date</span>
                        <p>{{ $signInDate }}</p>
                    </div>
                    <div class="detail">
                        <span>Time</span>
                        <p>{{ $signInTime }}</p>
                    </div>
                    <div class="detail">
                        <span>Status</span>
                        <p>
                            <span class="status-chip {{ $status === 'On Time' ? 'status-success' : 'status-warning' }}">
                                {{ $status === 'On Time' ? '✓ On Time' : '⚠ Late' }}
                            </span>
                        </p>
                    </div>
                    <div class="detail">
                        <span>Building</span>
                        <p>{{ $buildingName ?? 'Assigned Building' }}</p>
                    </div>
                </div>
            </div>

            @if(!$isWithinGeofence || $status === 'Late')
            <div class="alerts">
                @if(!$isWithinGeofence)
                <div class="alert warning">
                    <div class="alert-icon">📍</div>
                    <div>
                        <p><strong>Outside building geofence</strong></p>
                        <p>Your child signed in away from {{ $buildingName ?? 'the assigned building' }}.</p>
                    </div>
                </div>
                @endif
                @if($status === 'Late')
                <div class="alert danger">
                    <div class="alert-icon">⏰</div>
                    <div>
                        <p><strong>Late arrival</strong></p>
                        <p>Your child arrived after the late threshold for this class.</p>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <div class="card footer-card">
                <p>
                    You're receiving this automated notification because your email address
                    is registered as the guardian contact for <strong>{{ $studentName }}</strong>.
                </p>
                <p>
                    If you have any questions, please contact your school administrator.
                </p>
                <div class="footer-brand">
                    <span>PinPoint Attendance System</span>
                    <span>Real-time attendance tracking for peace of mind</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

