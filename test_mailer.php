<?php
/**
 * FlexiRide - Resend Mailer Test Page
 * DELETE THIS FILE before going to production!
 */

$result     = null;
$httpStatus = null;
$apiKey     = '';
$testEmail  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey    = trim($_POST['api_key'] ?? '');
    $testEmail = trim($_POST['test_email'] ?? '');
    $fromEmail = trim($_POST['from_email'] ?? 'onboarding@resend.dev');

    if ($apiKey && $testEmail) {
        $html = "
            <div style='font-family:Arial,sans-serif;padding:30px;background:#0f172a;color:#f8fafc;border-radius:12px;max-width:500px;'>
                <h2 style='color:#38bdf8;'>✅ FlexiRide Resend Test</h2>
                <p>This is a test email from the <strong>FlexiRide</strong> Resend mailer.</p>
                <p>If you received this, the Resend API is working correctly!</p>
                <div style='margin-top:20px;padding:15px;background:#1e293b;border-radius:8px;'>
                    <p style='color:#4ade80;font-weight:bold;font-size:18px;'>🎉 Mailer is working!</p>
                </div>
            </div>
        ";

        $payload = json_encode([
            'from'    => "FlexiRide <{$fromEmail}>",
            'to'      => [$testEmail],
            'subject' => '✅ FlexiRide Resend Test Email',
            'html'    => $html,
        ]);

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response   = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        // If test passes, save the API key to mailer.php automatically
        if ($httpStatus === 200 || $httpStatus === 201) {
            $mailerPath = __DIR__ . '/mailer.php';
            $mailerContent = file_get_contents($mailerPath);
            $mailerContent = str_replace(
                "'YOUR_RESEND_API_KEY_HERE'",
                "'" . addslashes($apiKey) . "'",
                $mailerContent
            );
            $mailerContent = str_replace(
                "'onboarding@resend.dev'",
                "'" . addslashes($fromEmail) . "'",
                $mailerContent
            );
            file_put_contents($mailerPath, $mailerContent);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Mailer Test - FlexiRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 24px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
        }
        .badge {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(239,68,68,0.15); color: #f87171;
            border: 1px solid rgba(239,68,68,0.3);
            padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;
            margin-bottom: 20px;
        }
        h1 { font-size: 26px; font-weight: 800; color: #38bdf8; margin-bottom: 6px; }
        p.sub { font-size: 14px; color: #64748b; margin-bottom: 28px; }

        label { display: block; font-size: 13px; color: #94a3b8; font-weight: 500; margin-bottom: 6px; }
        input[type=text], input[type=email], input[type=password] {
            width: 100%; padding: 13px 16px; background: #0f172a;
            border: 1px solid #334155; border-radius: 10px; color: #f8fafc;
            font-size: 14px; outline: none; transition: border 0.2s;
            margin-bottom: 16px;
        }
        input:focus { border-color: #38bdf8; }
        .toggle-key { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .toggle-key input { width: auto; margin-bottom: 0; }
        .toggle-key label { margin-bottom: 0; cursor: pointer; }

        .btn {
            width: 100%; padding: 15px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #0284c7, #38bdf8);
            color: white; font-size: 16px; font-weight: 700; cursor: pointer;
            transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(2,132,199,0.4); }

        .result {
            margin-top: 24px; padding: 20px; border-radius: 14px;
            animation: fadeIn 0.4s ease;
        }
        .result.success { background: rgba(74,222,128,0.1); border: 1px solid rgba(74,222,128,0.3); }
        .result.error   { background: rgba(239,68,68,0.1);  border: 1px solid rgba(239,68,68,0.3);  }
        .result h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .result.success h3 { color: #4ade80; }
        .result.error h3   { color: #f87171; }
        .result p  { font-size: 13px; color: #94a3b8; }
        .result code { display:block; margin-top:10px; background:#0f172a; padding:10px; border-radius:8px; font-size:12px; color:#e2e8f0; word-break:break-all; }

        .info-box { background: rgba(56,189,248,0.08); border: 1px solid rgba(56,189,248,0.2); border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 13px; color: #94a3b8; }
        .info-box strong { color: #38bdf8; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        hr { border: none; border-top: 1px solid #334155; margin: 24px 0; }
    </style>
</head>
<body>
<div class="card">
    <div class="badge"><i class='bx bx-test-tube'></i> DEV ONLY — DELETE AFTER TESTING</div>
    <h1><i class='bx bxs-send'></i> Resend Mailer Test</h1>
    <p class="sub">Send a test email to verify Resend API is configured correctly.</p>

    <div class="info-box">
        <strong>💡 Tip:</strong> Get your API key from <strong>resend.com/api-keys</strong>.<br>
        Use <code style="color:#4ade80">onboarding@resend.dev</code> as From email if your domain isn't verified yet.
        On success, the API key is <strong>auto-saved</strong> to <code>mailer.php</code>.
    </div>

    <form method="POST">
        <label>Resend API Key</label>
        <input type="password" name="api_key" id="api_key" placeholder="re_xxxxxxxxxxxxxxxxxxxxxxxx"
               value="<?php echo htmlspecialchars($_POST['api_key'] ?? ''); ?>" required>

        <div class="toggle-key">
            <input type="checkbox" id="show_key" onclick="toggleKey()">
            <label for="show_key" style="font-size:13px; color:#64748b;">Show API Key</label>
        </div>

        <label>From Email Address</label>
        <input type="text" name="from_email" placeholder="onboarding@resend.dev"
               value="<?php echo htmlspecialchars($_POST['from_email'] ?? 'onboarding@resend.dev'); ?>">

        <label>Send Test Email To</label>
        <input type="email" name="test_email" placeholder="your@email.com"
               value="<?php echo htmlspecialchars($_POST['test_email'] ?? ''); ?>" required>

        <button type="submit" class="btn"><i class='bx bx-send'></i> Send Test Email</button>
    </form>

    <?php if ($result !== null): ?>
        <hr>
        <?php if ($httpStatus === 200 || $httpStatus === 201): ?>
            <div class="result success">
                <h3>✅ Email Sent Successfully!</h3>
                <p>The test email was delivered. API key has been <strong>auto-saved to mailer.php</strong>.</p>
                <p style="margin-top:8px;">Check your inbox at: <strong><?php echo htmlspecialchars($testEmail); ?></strong></p>
                <code>Response: <?php echo htmlspecialchars(json_encode($result)); ?></code>
            </div>
        <?php else: ?>
            <div class="result error">
                <h3>❌ Failed (HTTP <?php echo $httpStatus; ?>)</h3>
                <p><?php echo htmlspecialchars($result['message'] ?? $result['name'] ?? 'Unknown error from Resend API.'); ?></p>
                <code>Response: <?php echo htmlspecialchars(json_encode($result)); ?></code>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleKey() {
    const input = document.getElementById('api_key');
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
