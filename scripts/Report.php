<?php

class Report
{
    //-----------------------------------------------------------------------------------------------
    // Message types
    //-----------------------------------------------------------------------------------------------
    const INFO         = 'info';
    const WARNING      = 'warning';
    const ERROR        = 'error';
    const DEFAULT_TYPE = 'info';

    static $types = array(self::INFO, self::WARNING, self::ERROR);

    /** @var $logger Monolog\Logger */
    private $logger;

    /**
     * Constructor
     * @param Monolog\Logger $logger Optional logger to log messages (debug mode)
     */
    public function __construct(Monolog\Logger $logger = null)
    {
        if (!session_id()) {
            session_start();
        }

        // Create the session array if it doesn't already exist
        if (!array_key_exists('messages', $_SESSION)) {
            $_SESSION['messages'] = array();
        }

        $this->logger = $logger;
    }

    /**
     * Add a message to the queue
     *
     * @param string $type The type of message to add
     * @param string $message The message
     *
     */
    public function add($message, $type = self::DEFAULT_TYPE)
    {
        if (is_string($message) && !empty($message) && in_array($type, self::$types)) {
            if (!array_key_exists($type, $_SESSION['messages'])) {
                $_SESSION['messages'][$type] = array();
            }
            $_SESSION['messages'][$type][] = $message;

            if ($this->logger) {
                $this->logger->{$type}("[REPORT]: $message");
            }
        }
    }

    /**
     * Get queued messages
     *
     * @param string $type The type of messages; if this is specified, the messages won't be cleared
     * @param bool $last Pop the last message of the specified type; will be deleted
     * @return array
     *
     */
    public static function getMessages($type = null, $last = false)
    {
        if (!empty($_SESSION['messages'])) {
            if ($type) {
                if (!empty($_SESSION['messages'][$type])) {
                    if ($last) {
                        return array_pop($_SESSION['messages'][$type]);
                    } else {
                        return $_SESSION['messages'][$type];
                    }
                } else {
                    return null;
                }
            } else {
                $messages = $_SESSION['messages'];
                self::clear();
                return $messages;
            }
        } else {
            return null;
        }
    }

    /**
     * Check to see if there are any queued error messages
     *
     * @return bool
     *
     */
    public static function hasErrors()
    {
        return !empty($_SESSION['messages'][self::ERROR]);
    }

    /**
     * Check to see if there are any messages queued
     *
     * @param string $type The type of messages to check for
     * @return bool
     *
     */
    public static function hasMessages($type = null)
    {
        return $type ? !empty($_SESSION['messages'][$type]) : !empty($_SESSION['messages']);
    }

    /**
     * Clear messages from the session data
     *
     * @param string $type The type of messages to clear
     *
     */
    public static function clear($type = null)
    {
        if (isset($_SESSION['messages'])) {
            if ($type) {
                $_SESSION['messages'][$type] = array();
            } else {
                $_SESSION['messages'] = array();
            }
        }
    }

    public function __toString()
    {
        return $this->hasMessages() ? '1' : '0';
    }
}