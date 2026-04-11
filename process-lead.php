<?php
// 1. Capture the JSON data sent from your frontend
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

// 2. FORWARD TO PRIVYR WEBHOOK
$privyrWebhookUrl = "https://www.privyr.com/api/v1/incoming-leads/0vZfjMQw/GA3w5rex";
$chPrivyr = curl_init($privyrWebhookUrl);
curl_setopt($chPrivyr, CURLOPT_POST, 1);
curl_setopt($chPrivyr, CURLOPT_POSTFIELDS, $inputJSON);
curl_setopt($chPrivyr, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($chPrivyr, CURLOPT_RETURNTRANSFER, true);
curl_exec($chPrivyr);
curl_close($chPrivyr);

// 3. SEND CONVERSIONS API EVENT TO META
$pixelId = '2105194773656211'; 
$accessToken = 'EAAUZALZAMJrn4BRMyp0JdTvulzJ40lhaAR4dEosEirWQUCrx4omSdo0Ce80EoPPaOrJumxcpHNfzrE8GGBtDnEIdY35ZBPAv0VVZBk6YlUtJVnMozBCyN23YtIDFeHQ2EVs0ihdQGGS8FhiDTIXXC6oio8ILL8byjgUJ7z4V85VmiUoo0fUZA5uZA1Fa5EFBKlxQZDZD'; // You will replace this in Step 3

// Clean and Hash data (Meta requires SHA256 and strictly numbers for phones)
$emailClean = strtolower(trim($data['email']));
$phoneClean = preg_replace('/[^0-9]/', '', $data['phone']); // Removes the '+' sign

$hashedEmail = hash('sha256', $emailClean);
$hashedPhone = hash('sha256', $phoneClean);

$metaData = [
    "data" => [
        [
            "event_name" => "Lead",
            "event_time" => time(),
            "action_source" => "website",
            "event_id" => $data['eventID'], 
            "event_source_url" => $data['page_url'],
            "user_data" => [
                "em" => [$hashedEmail],
                "ph" => [$hashedPhone]
            ],
            "custom_data" => [
                "project" => $data['project']
            ]
        ]
    ]
];

$metaUrl = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$accessToken}";
$chMeta = curl_init($metaUrl);
curl_setopt($chMeta, CURLOPT_POST, 1);
curl_setopt($chMeta, CURLOPT_POSTFIELDS, json_encode($metaData));
curl_setopt($chMeta, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
curl_setopt($chMeta, CURLOPT_RETURNTRANSFER, true);
curl_exec($chMeta);
curl_close($chMeta);

// Return success to the landing page
echo json_encode(["status" => "success"]);
?>