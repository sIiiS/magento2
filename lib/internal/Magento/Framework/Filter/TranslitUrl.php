<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Filter;

/**
 * Url compatible translit filter
 *
 * Process string based on convertation table
 */
class TranslitUrl extends Translit
{
    /**
     * Filter value
     *
     * @param string $string
     * @return string
     */
    public function filter($string)
    {
        $string = preg_replace('#()*!~-=+|\/[^0-9a-z%]+#i', '-', $string);
        $string = str_replace(' ', '-', $string);
        $string = mb_strtolower($string,'UTF-8');
        $string = trim($string, '-');

        return $string;
    }
}
