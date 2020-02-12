<?php

/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.8.0
 *  LICENSE: RU9018RLF896 - https://www.flynax.ru/user-agreement.html
 *  PRODUCT: Real Estate Classifieds
 *  DOMAIN: domozhil.ru
 *  FILE: INDEX.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2020 | All copyrights reserved.
 *  
 *  https://www.flynax.ru
 ******************************************************************************/

namespace Flynax\Utils;

/**
 * Helpful methods to work with string data
 *
 * @since 4.6.0
 */
class StringUtil
{
    /**
     * Replace all occurrences of the search string with the replacement
     *
     * @deprecated  4.7.1 - Use `strtr()` function instead
     *
     * @param  string $subject       - The string being replaced
     * @param  array  $replace_pairs - An array in the form ['from' => 'to', ...]
     * @return string
     */
    public static function replaceAssoc($subject, array $replace_pairs)
    {
        return (string) str_replace(array_keys($replace_pairs), array_values($replace_pairs), $subject);
    }
}
