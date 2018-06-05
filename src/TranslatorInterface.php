<?php

declare(strict_types = 1);

namespace Philipp15b;

/**
 * Interface TranslatorInterface
 *
 * @package Philipp15b
 */
interface TranslatorInterface
{
    /**
     * @param string   $string
     * @param string[] $args
     *
     * @return string
     */
    public function t(string $string, array $args = null): string;
}
