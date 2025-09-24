# Payment Integration Setup Guide

This guide walks you through setting up the Stripe payment integration for the AI Resume Analyzer application.

## Prerequisites

1. Laravel 11 application with Inertia.js and React
2. Multi-tenant architecture using Spatie Laravel Multitenancy
3. Stripe account (get started at [stripe.com](https://stripe.com))

## Step 1: Install Dependencies

The Stripe PHP SDK is already included in `composer.json`. If you need to install it manually:

```bash
composer require stripe/stripe-php
```

Then run:

```bash
composer install
```

## Step 2: Stripe Dashboard Configuration

### 2.1 Create Stripe Products and Prices

In your Stripe Dashboard:

1. Go to Products → Create product
2. Create three products:
   - **Starter Plan**: $9.99/month
   - **Professional Plan**: $19.99/month
   - **Enterprise Plan**: $49.99/month

3. For each product, note down the **Price ID** (starts with `price_`)

### 2.2 Set up Webhooks

1. Go to Developers → Webhooks → Add endpoint
2. Add your webhook URL: `https://yourdomain.com/webhooks/stripe`
3. Select events to listen for:
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`

4. Copy the **Webhook Secret** (starts with `whsec_`)

### 2.3 Get API Keys

1. Go to Developers → API Keys
2. Copy your **Publishable Key** (starts with `pk_`)
3. Copy your **Secret Key** (starts with `sk_`)

## Step 3: Environment Configuration

Update your `.env` file with the Stripe configuration:

```env
# Stripe Payment Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_your_stripe_publishable_key_here
STRIPE_SECRET_KEY=sk_test_your_stripe_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_stripe_webhook_secret_here

# Stripe Price IDs (from Step 2.1)
STRIPE_PRICE_STARTER_MONTHLY=price_your_starter_price_id
STRIPE_PRICE_PROFESSIONAL_MONTHLY=price_your_professional_price_id
STRIPE_PRICE_ENTERPRISE_MONTHLY=price_your_enterprise_price_id

# Enable Stripe payments
ENABLE_STRIPE_PAYMENTS=true
```

## Step 4: Database Migration

Run the database migrations to add Stripe fields to the subscription table:

```bash
# For the main database (landlord)
php artisan migrate

# For tenant databases
php artisan tenants:migrate
```

## Step 5: Update PaymentService Configuration

The `PaymentService` class automatically reads configuration from:
- `config/services.php` - Stripe API keys
- `config/stripe.php` - Price IDs and settings
- `.env` - Environment-specific values

Ensure these files are properly configured with your Stripe settings.

## Step 6: Frontend Integration

### 6.1 Add CSRF Token Meta Tag

Ensure your main layout includes the CSRF token:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### 6.2 Test Frontend Components

The subscription pages are located at:
- `/subscription` - Main subscription management
- `/subscription/success` - Successful payment
- `/subscription/cancel-checkout` - Cancelled payment

## Step 7: Testing

### 7.1 Use Stripe Test Mode

- Use test API keys (starting with `pk_test_` and `sk_test_`)
- Use test credit cards provided by Stripe

### 7.2 Test Card Numbers

- **Success**: `4242424242424242`
- **Declined**: `4000000000000002`
- **Requires Authentication**: `4000002500003155`

### 7.3 Test Webhooks

Use Stripe CLI to test webhooks locally:

```bash
# Install Stripe CLI
# Follow installation instructions at https://stripe.com/docs/stripe-cli

# Login to your Stripe account
stripe login

# Forward webhooks to your local server
stripe listen --forward-to localhost:8000/webhooks/stripe

# Test specific events
stripe trigger checkout.session.completed
```

## Step 8: Production Deployment

### 8.1 Switch to Live Mode

1. Replace test API keys with live keys (starting with `pk_live_` and `sk_live_`)
2. Update webhook endpoints to production URLs
3. Test thoroughly in production environment

### 8.2 Security Considerations

- Never expose secret keys in frontend code
- Use HTTPS in production
- Validate webhook signatures
- Implement rate limiting
- Monitor for suspicious activity

## Troubleshooting

### Common Issues

1. **Webhook signature verification fails**
   - Check webhook secret matches your .env file
   - Ensure raw request body is used for signature verification

2. **Checkout sessions fail to create**
   - Verify API keys are correct
   - Check Stripe price IDs exist
   - Ensure sufficient permissions on API keys

3. **Database errors**
   - Run migrations: `php artisan migrate` and `php artisan tenants:migrate`
   - Check database connection settings

4. **Frontend errors**
   - Verify CSRF token is included in requests
   - Check browser console for JavaScript errors
   - Ensure routes are properly configured

### Debug Mode

Enable debug mode to see detailed error messages:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Logs

Check Laravel logs for detailed error information:

```bash
tail -f storage/logs/laravel.log
```

## Support

- [Stripe Documentation](https://stripe.com/docs)
- [Laravel Documentation](https://laravel.com/docs)
- [Inertia.js Documentation](https://inertiajs.com/)

## Security Notes

1. **API Keys**: Store securely and never commit to version control
2. **Webhooks**: Always verify webhook signatures
3. **HTTPS**: Required for production Stripe integration
4. **PCI Compliance**: Stripe handles card data, but follow security best practices
5. **Error Handling**: Don't expose sensitive information in error messages

---

**Important**: This integration uses Stripe Checkout for secure payment processing. Customer payment information never touches your servers, ensuring PCI compliance and security.