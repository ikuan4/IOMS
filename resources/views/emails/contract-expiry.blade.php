<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Expiry Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #0B6BBD 0%, #0956a5 100%);
            color: #ffffff;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }
        .content {
            padding: 30px 20px;
        }
        .alert-box {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-box.warning {
            background: #fee2e2;
            border-left-color: #ef4444;
        }
        .alert-box.test {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }
        .alert-box strong {
            display: block;
            font-size: 16px;
            margin-bottom: 5px;
            color: #1f2937;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        .info-table tr:last-child {
            border-bottom: none;
        }
        .info-table td {
            padding: 12px 0;
        }
        .info-table td:first-child {
            font-weight: 600;
            color: #6b7280;
            width: 40%;
        }
        .info-table td:last-child {
            color: #1f2937;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #0B6BBD;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 0;
        }
        .footer {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Contract Expiry Notification</h1>
            <p>Integrated Office Management System (IOMS)</p>
        </div>

        <div class="content">
            @if($daysRemaining === 0)
                <div class="alert-box test">
                    <strong>🔔 Test Notification</strong>
                    This is a test notification for contract expiry alerts.
                </div>
            @elseif($daysRemaining > 0)
                <div class="alert-box">
                    <strong>⚠️ Contract Expiring Soon</strong>
                    This contract will expire in <strong>{{ $daysRemaining }} day(s)</strong>.
                </div>
            @else
                <div class="alert-box warning">
                    <strong>🚨 Contract Expired</strong>
                    This contract has expired and requires immediate attention.
                </div>
            @endif

            <h2 style="color: #1f2937; font-size: 18px; margin-top: 20px;">Contract Details</h2>

            <table class="info-table">
                <tr>
                    <td>Contract Number:</td>
                    <td><strong>{{ $contract->contract_number }}</strong></td>
                </tr>
                <tr>
                    <td>Contract Type:</td>
                    <td>{{ $contract->contractType->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td>Contract With:</td>
                    <td>{{ $contract->contract_with }}</td>
                </tr>
                <tr>
                    <td>Branch:</td>
                    <td>{{ $contract->branch->name ?? 'N/A' }}</td>
                </tr>
                @if($contract->latestVersion)
                    <tr>
                        <td>Start Date:</td>
                        <td>{{ $contract->latestVersion->start_date->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</td>
                    </tr>
                    <tr>
                        <td>End Date:</td>
                        <td>{{ $contract->latestVersion->end_date->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</td>
                    </tr>
                    <tr>
                        <td>Current Version:</td>
                        <td>{{ $contract->latestVersion->version_number }}</td>
                    </tr>
                @endif
                <tr>
                    <td>Grace Period:</td>
                    <td>{{ $contract->grace_period_days }} days</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><strong style="color: {{ $contract->status === 'Expired' ? '#dc2626' : ($contract->status === 'Expiring Soon' ? '#f59e0b' : '#10b981') }};">
                        {{ $contract->status }}
                    </strong></td>
                </tr>
            </table>

            @if($contract->latestVersion && $contract->latestVersion->description)
                <h2 style="color: #1f2937; font-size: 18px; margin-top: 20px;">Description</h2>
                <p style="background: #f9fafb; padding: 15px; border-radius: 8px; color: #4b5563;">
                    {{ $contract->latestVersion->description }}
                </p>
            @endif
        </div>

        <div class="footer">
            <p><strong>Integrated Office Management System (IOMS)</strong></p>
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p style="margin-top: 10px; font-size: 12px;">
                © {{ date('Y') }} IOMS. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
