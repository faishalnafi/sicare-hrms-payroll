<?php
$ch = curl_init('http://localhost:8000/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW']);
$body = "------WebKitFormBoundary7MA4YWxkTrZu0gW\r\nContent-Disposition: form-data; name=\"email\"\r\n\r\ntest@test.com\r\n------WebKitFormBoundary7MA4YWxkTrZu0gW--\r\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
echo curl_exec($ch);
