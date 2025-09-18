<!doctype html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $details['event'] ?? 'Account Activity' }} — {{ config('app.name') }}</title>

    <!-- Preheader: short preview text shown in inboxes -->
    <style>
        .preheader {
            display: none !important;
            visibility: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
            max-height: 0;
            max-width: 0;
            opacity: 0;
            overflow: hidden;
        }
    </style>

    <!-- Basic responsive + fallback styles (keep small) -->
    <style>
        /* Reset / sensible defaults */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse !important;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            display: block;
        }

        a {
            color: #1a73e8;
            text-decoration: none;
        }

        /* Container */
        .email-wrap {
            width: 100%;
            background-color: #f4f6f8;
            padding: 20px 0;
        }

        .email-body {
            width: 100%;
            max-width: 680px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }

        /* Header */
        .brand {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            display: inline-block;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }

        /* Content */
        .content {
            padding: 20px 24px;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            color: #334155;
        }

        h1 {
            margin: 0 0 8px 0;
            font-size: 20px;
            color: #0f172a;
        }

        p {
            margin: 0 0 14px 0;
            font-size: 15px;
            line-height: 1.5;
            color: #475569;
        }

        .muted {
            color: #6b7280;
            font-size: 13px;
        }

        /* Details box */
        .details {
            width: 100%;
            margin: 14px 0;
            border: 1px solid #eef2f6;
            border-radius: 6px;
            background: #fbfdff;
        }

        .details td {
            padding: 12px 14px;
            font-size: 14px;
            vertical-align: top;
            color: #0f172a;
            border-bottom: 1px solid #f1f5f9;
        }

        .details tr:last-child td {
            border-bottom: 0;
        }

        .label {
            width: 34%;
            font-weight: 600;
            color: #0f172a;
        }

        .value {
            width: 66%;
            color: #334155;
        }

        /* CTA */
        .cta-wrap {
            padding: 18px 24px 28px 24px;
            text-align: left;
        }

        .btn {
            display: inline-block;
            padding: 10px 18px;
            border-radius: 6px;
            background: #0f62fe;
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
        }

        .secondary {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
            font-size: 14px;
            margin-left: 8px;
            text-decoration: none;
        }

        /* Footer */
        .footer {
            padding: 18px 24px;
            font-size: 13px;
            color: #94a3b8;
            background: #ffffff;
            border-top: 1px solid #eef2f6;
        }

        /* Mobile tweaks */
        @media only screen and (max-width:520px) {
            .brand {
                padding: 16px;
            }

            .content {
                padding: 16px;
            }

            .details td {
                display: block;
                width: 100%;
            }

            .label {
                width: 100%;
                font-size: 13px;
                color: #374151;
            }

            .value {
                width: 100%;
                margin-top: 6px;
                font-size: 14px;
                color: #111827;
            }

            .cta-wrap {
                padding: 16px;
                text-align: center;
            }

            .btn {
                width: 100%;
                display: inline-block;
            }

            .secondary {
                display: none;
            }
        }
    </style>
</head>

<body>
    <span class="preheader">
        {{ $details['event'] ?? 'Account activity' }} • {{ $details['time'] ?? '' }} • {{ config('app.name') }}
    </span>

    <table role="presentation" class="email-wrap" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">

                <table role="presentation" class="email-body" cellpadding="0" cellspacing="0">
                    <!-- Header -->
                    <tr>
                        <td class="brand" style="background:linear-gradient(90deg,#ffffff,#fbfdff);">
                            <img src="{{ $details['logo_url'] ?? asset('appLogo/logo.png') }}"
                            alt="{{ config('app.name') }}"
                            class="brand-logo"
                            style="width:40px;height:40px;">
                            <div style="line-height:1;">
                                <div class="brand-title">{{ config('app.name') }}</div>
                                <div class="muted" style="font-size:12px;">Security Alert</div>
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="content">
                            <h1>
                                @if(($details['event'] ?? '') === 'Login') Welcome back.
                                @elseif(($details['event'] ?? '') === 'Logout') You signed out. @else Account activity
                                detected. @endif
                            </h1>

                            @if(($details['event'] ?? '') === 'Login')
                                <p>We detected a successful <strong>sign in</strong> to your account. Below are the details
                                    for your review.</p>
                            @elseif(($details['event'] ?? '') === 'Logout')
                                <p>You have <strong>signed out</strong>. For your records, here are the session details.</p>
                            @else
                                <p>An account event was recorded. Please review the details below.</p>
                            @endif

                            <!-- Details table -->
                            <table role="presentation" class="details" cellpadding="0" cellspacing="0" width="100%"
                                style="margin-top:12px;">
                                <tbody>
                                    <tr>
                                        <td class="label">User</td>
                                        <td class="value">{{ $details['username'] ?? ($details['to_email'] ?? '-') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Event</td>
                                        <td class="value">{{ $details['event'] ?? '-' }} • <span
                                                class="muted">{{ $details['time'] ?? '-' }}</span></td>
                                    </tr>
                                    <tr>
                                        <td class="label">IP Address</td>
                                        <td class="value">{{ $details['ip'] ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="label">Location</td>
                                        <td class="value">
                                            {{ trim(($details['city'] ?? '') . ', ' . ($details['state'] ?? '') . ', ' . ($details['country'] ?? '')) ?: '-' }}
                                            @if(!empty($details['zip'])) • PIN: {{ $details['zip'] }} @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">Browser / Device</td>
                                        <td class="value">{{ $details['browser'] ?? '-' }} on
                                            {{ $details['platform'] ?? '-' }} ({{ $details['device'] ?? '-' }})
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label">User Agent</td>
                                        <td class="value" style="font-size:13px; color:#475569;">
                                            <small>{{ $details['user_agent'] ?? '-' }}</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- CTA: show only on Login or suspicious scenarios -->
                            <div class="cta-wrap">
                                @php
                                    // determine whether to show CTA (example: show on Login)
                                    $showSecureCTA = ($details['event'] ?? '') === 'Login';
                                    // placeholder secure link
                                    $secureLink = $details['secure_link'] ?? url('/security/account');
                                @endphp

                            </div>


                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer">
                            <div style="font-size:13px;">
                                <strong>{{ config('app.name') }}</strong><br>
                                <span class="muted">Security &mdash; {{ config('app.name') }}</span>
                            </div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>
</body>

</html>