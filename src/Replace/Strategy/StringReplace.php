<?php

namespace ptlis\GrepDb\Replace\Strategy;

/**
 * Perform replacement on a simple string.
 */
final class StringReplace implements ReplacementStrategy
{
    /**
     * {@inheritdoc}
     */
    public function canReplace($subject)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function replace($searchTerm, $replaceTerm, $subject)
    {
        return str_replace($searchTerm, $replaceTerm, $subject);
    }
}
