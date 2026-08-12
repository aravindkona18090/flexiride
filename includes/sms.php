<?php
// Master SMS Gateway Utility - FlexiRide with Live Fast2SMS Gateway Integration

function cleanPhone10($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $phone = ltrim($phone, '0');
    if (strlen($phone) > 10) {
        $phone = substr($phone, -10);
    }
    return $phone;
}

function sendSMS($phone, $message) {
    $phone = cleanPhone10($phone);

    $fast2smsKey = getenv('FAST2SMS_API_KEY') ?: 'Ty7QlZMojhsEHBfze2L3gRF0CWDr5iOnb6cVN9mKtdauSXpI4kJIZNmh3RdYTH2okMOLitfnQBgaXe07';
    
    if (!empty($fast2smsKey)) {
        // Fast2SMS Quick SMS Route (Instant Delivery to Indian Mobiles)
        $smsBody = is_numeric(trim($message)) ? "Your FlexiRide OTP code is " . trim($message) : $message;

        $url = "https://www.fast2sms.com/dev/bulkV2?authorization=" . urlencode($fast2smsKey) . 
               "&route=q&message=" . urlencode($smsBody) . 
               "&language=english&flash=0&numbers=" . urlencode($phone);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "authorization: " . $fast2smsKey,
            "accept: */*",
            "cache-control: no-cache"
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            error_log("Fast2SMS cURL Error: " . $err);
        }
        return $response;
    }
    
    // Dev fallback log
    error_log("SMS dispatched to +91 {$phone}: {$message}");
    return true;
}
?>
