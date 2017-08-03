<?php

namespace ptlis\GrepDb\Replace\ReplacementStrategy;

/**
 * Perform replacement on a PHP-serialized string.
 */
final class SerializedReplace implements ReplacementStrategy
{
    /**
     * {@inheritdoc}
     */
    public function canReplace($subject)
    {
        return false !== @unserialize($subject);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($searchTerm, $replaceTerm, $subject)
    {
        $deserialized = @unserialize($subject);

        if (false === $deserialized) {
            throw new \RuntimeException('Error deserializing data');
        }

        $deserialized = $this->recursivelyReplace($searchTerm, $replaceTerm, $deserialized);

        return serialize($deserialized);
    }

    /**
     * Perform a recursive search & replace.
     *
     * @param string $searchTerm
     * @param string $replaceTerm
     * @param object|array|string $subject
     * @return object|array|string
     */
    private function recursivelyReplace($searchTerm, $replaceTerm, &$subject)
    {
        if (is_object($subject)) {
            foreach ($subject as $key => $value) {
                $subject->{$key} = $this->recursivelyReplace($searchTerm, $replaceTerm, $value);
            }

        } elseif (is_array($subject)) {
            foreach ($subject as $key => $value) {
                $subject[$key] = $this->recursivelyReplace($searchTerm, $replaceTerm, $value);
            }

        } elseif (is_string($subject)) {
            $subject = str_replace($searchTerm, $replaceTerm, $subject);
        }

        return $subject;
    }
}
