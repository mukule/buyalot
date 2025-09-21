<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        /* Reset styles */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #374151;
        }

        /* Container */
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }

        /* Content */
        .content {
            padding: 40px 30px;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .welcome-title {
            font-size: 24px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 15px 0;
        }

        .welcome-subtitle {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }

        /* Customer Info Card */
        .customer-info {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            border-left: 4px solid #667eea;
        }

        .customer-info h3 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #1f2937;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: #374151;
        }

        .info-value {
            color: #6b7280;
        }

        /* Benefits Section */
        .benefits {
            margin: 30px 0;
        }

        .benefits h3 {
            font-size: 18px;
            color: #1f2937;
            margin: 0 0 20px 0;
            text-align: center;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 6px;
        }

        .benefit-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .benefit-text {
            flex: 1;
        }

        .benefit-title {
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 5px 0;
        }

        .benefit-description {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }

        /* CTA Button */
        .cta-section {
            text-align: center;
            margin: 40px 0;
        }

        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            padding: 15px 35px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .cta-button:hover {
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            background-color: #f9fafb;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }

        .footer-text {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 15px 0;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-link {
            display: inline-block;
            margin: 0 10px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .unsubscribe {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 20px;
        }

        .unsubscribe a {
            color: #6b7280;
            text-decoration: underline;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 30px 20px;
            }

            .customer-info {
                padding: 20px;
            }

            .footer {
                padding: 20px;
            }

            .info-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
<div style="padding: 20px 0;">
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="logo">{{ config('app.name', 'BuyALot') }}</div>
            <p class="header-subtitle">Your Premium Shopping Destination</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Welcome Message -->
            <div class="welcome-message">
                <h1 class="welcome-title">Welcome to {{ config('app.name') }}!</h1>
                <p class="welcome-subtitle">
                    Hi {{ $customer->first_name }}, we're thrilled to have you join our community of smart shoppers.
                </p>
            </div>

            <!-- Customer Information -->
            <div class="customer-info">
                <h3>Your Account Details</h3>
                <div class="info-row">
                    <span class="info-label">Name:</span>
                    <span class="info-value">{{ $customer->first_name }} {{ $customer->last_name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value">{{ $customer->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Customer ID:</span>
                    <span class="info-value">{{ $customer->customer_code }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since:</span>
                    <span class="info-value">{{ $customer->created_at->format('F d, Y') }}</span>
                </div>
            </div>

            <!-- Benefits -->
            <div class="benefits">
                <h3>What You Get as a Member</h3>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <span style="color: white; font-size: 18px;">🛍️</span>
                    </div>
                    <div class="benefit-text">
                        <div class="benefit-title">Exclusive Deals</div>
                        <p class="benefit-description">Access to member-only discounts and early sale notifications</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <span style="color: white; font-size: 18px;">🚚</span>
                    </div>
                    <div class="benefit-text">
                        <div class="benefit-title">Free Delivery</div>
                        <p class="benefit-description">Free shipping on orders over KES 2,000 within Nairobi</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <span style="color: white; font-size: 18px;">⭐</span>
                    </div>
                    <div class="benefit-text">
                        <div class="benefit-title">Loyalty Rewards</div>
                        <p class="benefit-description">Earn points on every purchase and redeem for discounts</p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <span style="color: white; font-size: 18px;">🔒</span>
                    </div>
                    <div class="benefit-text">
                        <div class="benefit-title">Secure Shopping</div>
                        <p class="benefit-description">Safe and secure payment processing with buyer protection</p>
                    </div>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="cta-section">
                <p style="margin-bottom: 25px; color: #6b7280;">Ready to start shopping?</p>
                <a href="{{ config('app.url') }}" class="cta-button">
                    Start Shopping Now
                </a>
            </div>

            <!-- Additional Info -->
            <div style="background-color: #fef3c7; border: 1px solid #85f142; border-radius: 8px; padding: 20px; margin: 30px 0; text-align: center;">
                <h4 style="color: #4a7e29; margin: 0 0 10px 0; font-size: 16px;">🎉 New Member Bonus!</h4>
                <p style="color: #6fa64c; margin: 0; font-size: 14px;">
                    Use code <strong>WELCOME20</strong> to get 20% off your first order (minimum KES 1,000)
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="footer-text">
                Questions? Contact our friendly customer support team at
                <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'buyalot.com' }}" style="color: #667eea;">
                    support@{{ parse_url(config('app.url'), PHP_URL_HOST) ?? 'buyalot.com' }}
                </a>
            </p>

            <div class="social-links">
{{--                <a href="#" class="social-link">Facebook</a>--}}
{{--                <a href="#" class="social-link">Twitter</a>--}}
{{--                <a href="#" class="social-link">Instagram</a>--}}
                <a href="#" class="social-link">WhatsApp</a>
            </div>

            <div class="unsubscribe">
                <p>
                    You received this email because you created an account at {{ config('app.name') }}.<br>
                    <a href="{{ config('app.url') }}/unsubscribe?email={{ $customer->email }}">Unsubscribe</a> |
                    <a href="{{ config('app.url') }}/preferences">Email Preferences</a>
                </p>
            </div>
        </div>
    </div>
</div>
</body>
</html>
