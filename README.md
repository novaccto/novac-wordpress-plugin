# Novac Payment Plugin

WordPress payment plugin for accepting payments via Novac Payment Gateway.

## Features

### Admin Section
- **Settings Page**: Configure your Novac API keys (public and secret), mode (test/live), and webhook URL
- **Transactions Page**: View all transactions with search, filtering, and pagination
- Beautiful, interactive UI built with React and WordPress components

### Frontend
- **Shortcode**: `[novac_payment_form]` - Add a payment form anywhere
- **Gutenberg Block**: "Novac Payment Form" block for block editor
- Responsive, modern payment form design
- Real-time payment processing with Novac API

### Backend Integration
- Automatic transaction recording in database
- Webhook support for payment verification
- REST API endpoints for managing transactions
- Secure API communication with Novac

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to "Novac Payments" in the admin menu
4. Configure your API keys and settings

## Configuration

### API Keys
1. Get your API keys from [Novac Dashboard](https://dashboard.novacpayment.com)
2. Go to **Novac Payments > Settings**
3. Enter your **Public Key** and **Secret Key**
4. Select **Mode**: Test (for development) or Live (for production)
5. Copy the **Webhook URL** and configure it in your Novac dashboard

### Webhook Setup
Configure the webhook URL in your Novac dashboard to receive payment notifications:
```
https://yoursite.com/?novac-webhook=1
```

## Usage

### Using Shortcode

#### Basic Form (User Enters Amount)
```
[novac_payment_form]
```

#### Fixed Amount Form
```
[novac_payment_form amount="1000" currency="NGN" description="Product Purchase" button_text="Pay Now"]
```

#### Form Customization
```
[novac_payment_form amount="1000" currency="NGN" description="Product Purchase" button_text="Pay Now" text_color="#F4F4F5" container_color="#111827" button_color="#EAB308" button_text_color="#111827"]
```

**Shortcode Parameters:**
- `amount` - Fixed amount (optional, leave empty for user input)
- `currency` - Currency code (default: NGN)
- `description` - Payment description (default: "Payment")
- `button_text` - Button text (default: "Pay Now")
- `text_color` - Text Color (default: #111111)
- `container_color` - Container Color
- `button_color` - Button Color
- `button_text_color` - Button Text Color
  

### Using Gutenberg Block

1. In the block editor, click the (+) button to add a block
2. Search for "Novac Payment Form"
3. Add the block to your page
4. Configure settings in the block inspector:
   - Fixed Amount (optional)
   - Currency
   - Description
   - Button Text

### Managing Transactions

1. Go to **Novac Payments > Transactions**
2. View all transactions with details:
   - Transaction reference
   - Customer information
   - Amount and currency
   - Payment status
   - Date and time
3. Search transactions by email, name, or reference
4. Filter by status (Successful, Pending, Failed)
5. Paginate through large transaction lists

## Payment Flow

1. Customer fills out the payment form
2. Form submits to WordPress via AJAX
3. WordPress initiates checkout with Novac API
4. Customer is redirected to Novac payment page
5. Customer completes payment
6. Novac sends webhook notification
7. WordPress verifies and updates transaction status
8. Customer is redirected back with payment status

## API Endpoints

### Settings
- `GET /wp-json/novac/v1/settings` - Get current settings
- `POST /wp-json/novac/v1/settings` - Update settings

### Transactions
- `GET /wp-json/novac/v1/transactions` - List transactions (with pagination, search, filtering)
- `GET /wp-json/novac/v1/transactions/{id}` - Get single transaction

## Database

The plugin creates a `wp_novac_transactions` table to store transaction data:
- Transaction reference
- Customer information (email, name)
- Amount and currency
- Payment status
- Payment method
- Metadata
- Timestamps

## Permissions

The plugin adds custom capabilities:
- `novac_manage_settings` - Manage plugin settings
- `novac_view_transactions` - View transactions
- `novac_refund_transactions` - Process refunds (for future use)
- `novac_export_transactions` - Export transaction data (for future use)

Administrators automatically get all capabilities.

## Custom Roles

Two custom roles are created:
- **Novac Payment Manager** - Full access to all features
- **Novac Finance Analyst** - View and export transactions only

## Development

### Building Assets

```bash
composer install
npm install
npm run build
```

This builds:
- Admin React app (`includes/admin/build/`)
- Gutenberg block (`includes/blocks/build/`)

### File Structure

```
novac/
├── includes/
│   ├── admin/           # Admin interface
│   │   ├── src/        # React source
│   │   └── build/      # Compiled admin assets
│   ├── api/            # Novac API client
│   ├── blocks/         # Gutenberg blocks
│   │   ├── src/        # Block source
│   │   └── build/      # Compiled blocks
│   ├── database/       # Database handlers
│   ├── frontend/       # Frontend forms
│   ├── users/          # Roles & capabilities
│   └── webhooks/       # Webhook handlers
├── frontend/
│   ├── css/           # Frontend styles
│   └── js/            # Frontend JavaScript
└── novac.php          # Main plugin file
```

## Support

For support, please visit:
- [Novac Documentation](https://developer.novacpayment.com)
- [Plugin Support](https://github.com/bajoski34/novac/issues)

## License

GNU General Public License v3.0
