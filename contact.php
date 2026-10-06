//contact.php

<?php
// contact.php - validates the form, emails you, then returns to index.php
session_start();

// ---- Settings: change these ----
$to   = 'hello@laadokitchen.com';      // where enquiries are delivered
$from = 'no-reply@laadokitchen.com';   // must be an address on your own domain
// --------------------------------

function back(bool $ok, string $msg, array $errors = []): void {
    $_SESSION['flash'] = ['ok' => $ok, 'msg' => $msg, 'errors' => $errors];
    header('Location: index.php#contact');
    exit;
}
function one_line(string $v): string { return trim(preg_replace('/[\r\n]+/', ' ', strip_tags($v))); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

// Honeypot: bots fill the hidden field. Pretend it worked.
if (!empty($_POST['website'])) back(true, 'Thank you! We will reply within one working day.');

// One message per 30 seconds per visitor
if (isset($_SESSION['last_sent']) && time() - $_SESSION['last_sent'] < 30) {
    back(false, 'Please wait a moment before sending another message.');
}

$name    = one_line($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = one_line($_POST['phone'] ?? '');
$topic   = one_line($_POST['topic'] ?? 'General');
$message = trim(strip_tags($_POST['message'] ?? ''));

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 80)                  $errors[] = 'Enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))                     $errors[] = 'Enter a valid email address.';
if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,15}$/', $phone)) $errors[] = 'Enter a valid phone number.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 3000)         $errors[] = 'Write a message of 10 to 3000 characters.';
if ($errors) back(false, 'Your message was not sent.', $errors);

$subject = "LAADO Kitchen enquiry: $topic";
$body = "Name: $name\nEmail: $email\nPhone: " . ($phone ?: 'Not given') . "\nTopic: $topic\n\nMessage:\n$message\n";
$headers  = "From: LAADO Kitchen <$from>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8\r\n";

if (mail($to, $subject, $body, $headers)) {
    $_SESSION['last_sent'] = time();
    back(true, 'Thank you! We will reply within one working day.');
}
back(false, 'Sorry, we could not send your message. Please call us instead.');

