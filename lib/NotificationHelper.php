<?php
namespace quayph\quaffa;

use quayph\quaffa\config\AppConfig;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class NotificationHelper {

    static function notify($backup) {
        
        $to = self::getRecipientAdresses($backup);
        if (count($to)) {
            try {
                $mail = new PHPMailer(TRUE);
                
                if(AppConfig::$smtp && AppConfig::$smtpServer) {
                    $mail->SMTPDebug = $backup->isDebug() ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF; 
                    $mail->isSMTP();
                    $mail->Host = AppConfig::$smtpServer;
                    $mail->Port = AppConfig::$smtpPort;
                    if (AppConfig::$smtpUser) {
                        $mail->SMTPAuth   = true;
                        $mail->Username   = AppConfig::$smtpUser; 
                        $mail->Password   = AppConfig::$smtpPasswd;
                    }
                    if (AppConfig::$smtpSecure) {
                        $mail->SMTPSecure = constant("PHPMailer::".AppConfig::$smtpSecure);
                    } 
                }
                $mail->setFrom(AppConfig::$emailFromAddress, AppConfig::$emailFromName);
                foreach($to as $addr) {
                    $mail->addAddress($addr);
                }
                $mail->isHTML(false);
                $mail->Subject = self::getMailSubject($backup);
                $mail->Body = self::getMailMessage($backup);;
                $mail->send();
            }
            catch (Exception $e)
            {
                return 'Mailer Error: '.$mail->ErrorInfo;
            }
            return true;
        }
    }

    private static function getRecipientAdresses($backup) {
        $to = [];
        if (AppConfig::$adminEmail) {
            if (is_scalar(AppConfig::$adminEmail)) {
                $to[] = AppConfig::$adminEmail; 
            }
            else {
                $to = AppConfig::$adminEmail;
            }
        }

        if ($backup->jobconf->extraEmailNotifications) {
            if (is_scalar($backup->jobconf->extraEmailNotifications)) {
                $to[] = $backup->jobconf->extraEmailNotifications; 
            }
            else {
                $to = array_merge($to, $backup->jobconf->extraEmailNotifications);
            }
        }
        return $to;
    }

    private static function getMailSubject($backup) {
        return 'Quaffa report';
    }

    private static function getMailMessage($backup) {
        return "TODO";
    }
}