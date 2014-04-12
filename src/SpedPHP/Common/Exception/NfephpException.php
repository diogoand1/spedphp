<?php

namespace SpedPHP\Common\Exception;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Common\Exception
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class NfephpException extends \Exception
{
    
    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
        $this->sendNotifications();
        $this->logError();
    }


    protected function sendNotifications()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

 
    protected function logError()
    {
        // fazer algum log aqui
    }
}
