<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Dados do envio
$to = "marcos.araujo@supleti.com";
$subject = "Teste API Gmail";
$body = "Corpo do email de teste";

// Constrói mensagem RFC 2822
$encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$message = "From: me\r\n";
$message .= "To: {$to}\r\n";
$message .= "Subject: {$encodedSubject}\r\n";
$message .= "MIME-Version: 1.0\r\n";
$message .= "Content-Type: text/plain; charset=utf-8\r\n";
$message .= "Content-Transfer-Encoding: 8bit\r\n";
$message .= "\r\n";
$message .= $body;

// Base64 URL-safe
$raw = rtrim(strtr(base64_encode($message), '+/', '-_'), '=');

echo "Mensagem RFC 2822:\n";
echo $message;
echo "\n\n";

echo "Base64 URL-safe:\n";
echo substr($raw, 0, 100) . "...\n";
echo "\n";

// Busca token (simulado - substitua pelo seu token real)
echo "Cole o token de acesso do Gmail aqui e pressione Enter:\n";
$token = trim(fgets(STDIN));

if (empty($token)) {
    die("Token vazio\n");
}

// Envia via Gmail API
$response = Http::withToken($token)
    ->timeout(30)
    ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
        'raw' => $raw,
    ]);

echo "Status: " . $response->status() . "\n";
echo "Response Body:\n";
echo $response->body() . "\n";
