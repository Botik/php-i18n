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
        return isset($this->_keys[$string]) ? vsprintf($this->_keys[$string], $args) : $string;
    }
}
