<?php


/**
 * Spedphp (http://www.nfephp.org/)
 *
 * @link      http://github.com/nfephp-org/spedphp for the canonical source repository
 * @copyright Copyright (c) 2008-2013 NFePHP (http://www.nfephp.org)
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @package   Spedphp
 */

namespace SpedPHP\Common\DateTime;

class DateTime
{
    public static function st2uts($dhs)
    {
        if ($dhs) {
            $aDH = explode('T', $dhs);
            $adDH = explode('-', $aDH[0]);
            $atDH = explode(':', $aDH[1]);
            $timestampDH = mktime($atDH[0], $atDH[1], $atDH[2], $adDH[1], $adDH[2], $adDH[0]);
            return $timestampDH;
        } else {
            return '';
        }
    }//fim st2uts
}//fim da classe
