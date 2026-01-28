<?php
/**
 * Email Configuratie - De Gouden Schoen
 * Brevo SMTP - Poort 465 (SSL) - vaak betrouwbaarder dan 587
 */

return [
    // SMTP AAN
    'use_smtp' => true,
    
    // Brevo SMTP Server - POORT 465 MET SSL
    'smtp_host' => 'smtp-relay.brevo.com',
    'smtp_port' => 465,                    // ← GEWIJZIGD naar 465
    'smtp_secure' => 'ssl',                // ← GEWIJZIGD naar SSL
    
    // Brevo Credentials
    'smtp_username' => 'a0ed10001@smtp-brevo.com',
    'smtp_password' => 'xsmtpsib-651451c613f015d84de8639158f86ca78af7adf19b0f16be80959238d2c02fd1-XInT4P03QFzgaSwo',
    
    // Afzender
    'from_email' => 'cdivyun@gmail.com',
    'from_name' => 'De Gouden Schoen',
    
    // Timeout
    'timeout' => 10,
    
    // Debug
    'debug' => false
];