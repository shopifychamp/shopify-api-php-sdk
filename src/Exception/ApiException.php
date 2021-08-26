<?php
namespace Shopify\Exception;
/**
 * Class ApiException
 * @package Shopify\Exception
 */
class ApiException extends \Exception
{
    /**
     * @var string
     * store error message
     */
    protected $message;

    /**
     * ApiException constructor.
     * @param $message
     * @param $code
     */
    function __construct($message,$code)
    {
        $this->message = $message;
        parent::__construct($message, $code);
    }

    /**
     * @return string
     * Get error message
     */
    function getError() {
        return $this->message;
    }
}
