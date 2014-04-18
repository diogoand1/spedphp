<?php

namespace SpedPHP\Common\DateTime;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Common\DateTime
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class DateTime
{
    /**
     * Converte data no formato SEFAZ para timestamp php
     * @param type $dhs
     * @return string
     */
    public static function st2uts($dhs = '')
    {
        if (!is_string($dhs)) {
            return '';
        }
        if ($dhs != '') {
            $aDH = explode('T', $dhs);
            $adDH = explode('-', $aDH[0]);
            $atDH = explode(':', $aDH[1]);
            $timestampDH = mktime($atDH[0], $atDH[1], $atDH[2], $adDH[1], $adDH[2], $adDH[0]);
            return $timestampDH;
        } else {
            return '';
        }
    }
}//fim da classe
