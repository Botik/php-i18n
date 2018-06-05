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
        if (!isset($this->_keys[$string])) {
            return $string;
        }

        if ($args) {
            $search = $replace = [];

            foreach ($args as $k => $v) {
                $search[] = '{{'.$k.'}}';
                $replace[] = $v;
            }

            return str_replace($search, $replace, $this->_keys[$string]);
        }

        return $this->_keys[$string];
    }
}
