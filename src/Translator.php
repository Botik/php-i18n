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
    /**
     * @var string[]
     */
    protected $_keys = [];

    /**
     * @param string   $string
     * @param string[] $args
     *
     * @return string
     */
    public function t(string $string, array $args = null): string
    {
        return isset($this->_keys[$string]) ? vsprintf($this->_keys[$string], $args) : $string;
    }
}
