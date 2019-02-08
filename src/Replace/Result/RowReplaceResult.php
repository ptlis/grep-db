<?php declare(strict_types=1);

/**
 * @copyright   (c) 2017-present brian ridley
 * @author      brian ridley <ptlis@ptlis.net>
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace ptlis\GrepDb\Replace\Result;

use ptlis\GrepDb\Search\Result\RowSearchResult;

/**
 * Result of row replacements.
 */
final class RowReplaceResult
{
    /** @var FieldReplaceResult[] */
    private $fieldResultList;

    /** @var string[] */
    private $errorList;

    /** @var RowSearchResult */
    private $rowSearchResult;


    /**
     * @param FieldReplaceResult[] $fieldResultList
     * @param string[] $errorList
     */
    public function __construct(
        RowSearchResult $rowSearchResult,
        array $fieldResultList,
        array $errorList
    ) {
        $this->fieldResultList = $fieldResultList;
        $this->errorList = $errorList;
        $this->rowSearchResult = $rowSearchResult;
    }

    /**
     * Get the number of replacements in all fields in this row.
     */
    public function getReplacedCount(): int
    {
        return intval(array_reduce(
            $this->fieldResultList,
            function (int $count, FieldReplaceResult $fieldResult) {
                return $count + $fieldResult->getReplacedCount();
            },
            0
        ));
    }

    /**
     * Get any replacement errors.
     *
     * @return string[]
     */
    public function getErrorList(): array
    {
        $errorList = $this->errorList;
        foreach ($this->fieldResultList as $fieldResult) {
            foreach ($fieldResult->getErrorList() as $error) {
                $errorList[] = $error;
            }
        }

        return $errorList;
    }

    /**
     * @return FieldReplaceResult[]
     */
    public function getFieldResultList(): array
    {
        return $this->fieldResultList;
    }

    public function getRowSearchResult(): RowSearchResult
    {
        return $this->rowSearchResult;
    }
}