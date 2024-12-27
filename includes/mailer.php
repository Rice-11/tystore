<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private $mailer;

    public function __construct() {
        try {
            $this->mailer = new PHPMailer(true);
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = 'tls';
            $this->mailer->Port = SMTP_PORT;
            
            // Default settings
            $this->mailer->isHTML(true);
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $this->mailer->CharSet = 'UTF-8';
        } catch (\Exception $e) {
            error_log("Mailer initialization error: " . $e->getMessage());
            throw new \Exception("Failed to initialize mailer");
        }
    }

    public function sendPasswordReset($email, $token) {
        try {
            $reset_link = SITE_URL . "/modules/security/reset_password.php?token=" . $token;
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = "Password Reset Request";
            $this->mailer->Body = "
                <h2>Password Reset Request</h2>
                <p>Click the link below to reset your password:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p>If you didn't request this, please ignore this email.</p>
                <p>This link will expire in 1 hour.</p>
            ";

            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendOrderStatusUpdate($order_id, $status, $email) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = "Order #$order_id Status Update";
            $this->mailer->Body = "
                <h2>Order Status Update</h2>
                <p>Your order #$order_id has been updated to: <strong>$status</strong></p>
                <p>You can check your order details by logging into your account.</p>
            ";

            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendPaymentConfirmation($order_id, $amount, $email) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email);
            $this->mailer->Subject = "Payment Confirmation - Order #$order_id";
            $this->mailer->Body = "
                <h2>Payment Confirmation</h2>
                <p>We have received your payment of $" . number_format($amount, 2) . " for order #$order_id.</p>
                <p>Your order is now being processed.</p>
                <p>You can track your order status by logging into your account.</p>
            ";

            $this->mailer->send();
            return true;
        } catch (\Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }
} 