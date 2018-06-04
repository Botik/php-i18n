<?php

declare(strict_types = 1);

namespace Philipp15b;

/**
 * Class Translator
 *
 * @package Philipp15b
 */
class Translator implements TranslatorInterface
{
    protected $_keys = [];

    public function t(string $string, array $args = null) {
        if (!isset($this->_keys[$string])) {
            return $string;
        }

        if ($args) {
            return str_replace(array_keys($args), array_values($args), $this->_keys[$string]);
        }

        return $this->_keys[$string];
    }
}
