# FitSMS PHP SDK (v4)

The official PHP SDK for the [FitSMS.lk](https://fitsms.lk) gateway, maintained by Global Cloud Media. This package provides a seamless way to integrate SMS capabilities into PHP and Laravel applications using the FitSMS v4 API.

---

## 🚀 Features

- **Auto-Formatting**: Converts Sri Lankan numbers into `947XXXXXXXX` format.
- **v4 Support**: Fully compatible with FitSMS v4 REST API.
- **Guzzle Powered**: Reliable HTTP requests using Guzzle.
- **Robust Error Handling**: Extracts meaningful API error messages.

---

## 📦 Installation

Install via Composer:

```bash
composer require global-cloud-media/fitsms
```

---

## ⚡ Quick Start

### 1. Initialize the Client

```php
use GlobalCloudMedia\FitSMS\FitSMS;

$apiToken = 'YOUR_V4_API_TOKEN';
$senderId = 'GlobalCloud';

$sms = new FitSMS($apiToken, $senderId);
```

---

### 2. Send an SMS

```php
try {
    $response = $sms->send('0761234567', 'Hello from Global Cloud Media!');

    if ($response['status'] === 'success') {
        echo "Message Sent! RUID: " . $response['data']['ruid'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

---

### 3. Check Account Balance

```php
$balance = $sms->getBalance();
echo "Remaining Units: " . $balance['data']['sms_unit'];
```

---

## 📖 API Reference

| Method        | Parameters                  | Description |
|--------------|---------------------------|------------|
| send()       | recipient(s), message     | Send SMS |
| getBalance() | none                      | Get SMS balance |
| getStatus()  | ruid, recipient           | Check delivery status |

---

## ⚙️ Advanced Usage

### Manual Status Reconciliation

```php
$status = $sms->getStatus('REF123456', '0761234567');
print_r($status);
```

---

### 🇱🇰 Sri Lankan Number Formatting

The SDK automatically formats numbers:

```
0761234567   → 94761234567
761234567    → 94761234567
+94761234567 → 94761234567
```

---

## 🔔 Webhook Integration (Recommended)

FitSMS v4 primarily uses webhooks for delivery updates.

### Sample Webhook Payload

```json
{
    "status": "success",
    "data": {
        "to": "94770000000",
        "from": "MyBrand",
        "message": "Test",
        "sms_type": "plain",
        "sms_count": 1,
        "cost": 1.25,
        "send_by": "api",
        "ruid": "fe424939fc3c4b6dbcc876994517d712",
        "received_at": "2025-09-05T23:24:22+05:30",
        "expired_at": "2025-09-05T23:25:22+05:30"
    }
}
```

### Example Laravel Controller

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SmsLog; // Assuming you have an SmsLog model

class FitSmsWebhookController extends Controller
{
    /**
     * Handle the incoming DLR from FitSMS v4
      
       NOTE: Since FitSMS is an external service, it cannot provide a Laravel CSRF token
     */

    public function handle(Request $request)
    {
        // 1. Log the raw data for debugging (helpful for your dev logs)
        Log::channel('sms')->info('FitSMS Webhook Received', $request->all());

        // 2. Extract key data from the 'data' nesting or root
        // Note: Check if FitSMS sends the 'data' wrapper in the webhook 
        // or just the raw fields. Most v4 webhooks are flat.
        $ruid = $request->input('data.ruid') ?? $request->input('ruid');
        $status = $request->input('data.status') ?? $request->input('status');

        if (!$ruid) {
            return response()->json(['error' => 'RUID missing'], 400);
        }

        // 3. Update your database
        $sms = SmsLog::where('ruid', $ruid)->first();

        if ($sms) {
            $sms->update([
                'delivery_status' => strtolower($status),
                'updated_at' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Record updated']);
        }

        // Return 200 even if record not found to stop FitSMS from retrying
        return response()->json(['status' => 'not_found'], 200);
    }
}
```

---

## 🛡 Best Practices

- Always store `ruid` for tracking
- Use webhooks instead of polling
- Implement retry logic for failed sends
- Log all webhook events for audit/debugging

---

## 📄 License

This project is licensed under the MIT License.

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

---

## 👨‍💻 Maintainer

Maintained by [Global Cloud Media (pvt) Ltd.](https://globalcloudmedia.lk)