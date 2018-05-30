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
    public function t(string $string, array $args = null);
}
