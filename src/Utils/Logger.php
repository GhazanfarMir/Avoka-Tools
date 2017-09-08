<?php

namespace EburyLabs\Utils;

/**
 * Class Logger
 * @package Ebury\Utils
 */

class Logger
{

    const EBURY_ERROR_DIR = '/var/log/apache2/appforms_tools_debug.log';

    /**
     * @var array
     */
    public static $recipients = ['ghazanfar.mir@ebury.com'];

    /**
     * @param $message
     * @param string $messageType
     */
    public static function log($message, $messageType = 'debug')
    {

        $timestamp = date('D M d h:i:s');

        switch (strtolower($messageType)) {

            case 'error':
            case 'warning':
            case 'debug':
            case 'info':

                $prefix = '['.$timestamp.'] ' . '[' . strtoupper($messageType) . ']: ';
                break;

            default:
                $prefix = '['.$timestamp.'] ' . '[OTHER]: ';
        }

        if (is_array($message) || is_object($message)) {

            error_log(print_r($message, true) . PHP_EOL, 3, self::EBURY_ERROR_DIR);

        } else {

            error_log($prefix . $message . PHP_EOL, 3, self::EBURY_ERROR_DIR);
        }
    }

    /**
     * @param string $subject
     * @param string $message
     * @param null $headers
     * @param null $attachments
     */
    public static function Email($subject = 'EBuryLabs Errors', $message = 'There were problems in the Au10Tix process.', $headers = null, $attachments = null)
    {

        //@FIXME implement email attachments
        if ( ! empty($attachment)) {

        }

        if ( ! empty(self::$recipients)) {
            if (empty($headers)) {
                $headers = [
                    "From: Au10Tix Microservice <noreply@ebury.com>" . "\r\n",
                    "Content-Type: text/html; charset=UTF-8"
                ];
            }

            if ( ! is_array(self::$recipients)) {
                self::$recipients = (array) self::$recipients;
            }

            try {
                foreach (self::$recipients as $to) {
                    mail($to, $subject, $message, $headers);
                }

            } catch ( Exception $e ) {

                // perhaps some message
            }
        }
    }
}