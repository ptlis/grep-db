<?php

namespace ptlis\GrepDb\Replace\ReplacementStrategy;

/**
 * Interface that replacement strategies must implement.
 */
interface ReplacementStrategy
{
    /**
     * Returns true if the strategy can perform a replacement on the subject.
     *
     * @param string $subject
     * @return bool
     */
    public function canReplace($subject);

    /**
     * Replaces all instances of the search term with the replacement term.
     *
     * @param string $searchTerm
     * @param string $replaceTerm
     * @param string $subject
     * @return string
     */
    public function replace($searchTerm, $replaceTerm, $subject);
}
