<?php

namespace Crealoz\EasyAudit\Service\PDFWriter;

class SizeCalculation
{

    private int $lineHeight = 15;

    public function __construct(
        private readonly \Psr\Log\LoggerInterface $logger
    )
    {

    }

    /**
     * Try to get the first section of the subresults (errors, warnings or suggestions) and calculate its size plus the
     * size of the title.
     *
     * @param $subResults
     * @param bool $getFirstSection
     * @return int
     */
    public function calculateTitlePlusFirstSubsectionSize($subResults, $getFirstSection = false): int
    {
        if ($getFirstSection) {
            $subResults = $this->getFirstSection($subResults);
        }
        $size = 0;
        $size += 44;
        $size += $this->calculateSectionIntroSize(reset($subResults));
        return $size;
    }

    /**
     * Calculate the size of the section intro (title, explanation and caution)
     *
     * @param $subsection
     * @return int
     */
    public function calculateSectionIntroSize($subsection): int
    {
        $size = 0;
        if (isset($subsection['title'])) {
            $size += 25;
        }
        if (isset($subsection['explanation'])) {
            $size += $this->lineHeight;
            $size += $this->calculateNumberOfLines(preg_replace('/\s+/', ' ', $subsection['explanation'])) * $this->lineHeight;
        }
        if (isset($subsection['caution'])) {
            $size += $this->lineHeight;
            $size += $this->calculateNumberOfLines(preg_replace('/\s+/', ' ', $subsection['caution'])) * $this->lineHeight;
        }
        return $size;
    }

    /**
     * Calculate the size of a multidimensional array maximum depth is 2
     *
     * @param $entries
     * @param $columnCount
     * @param $depth
     * @return int
     */
    public function calculateMultidimensionalArraySize($entries, $columnCount = null, $depth = 0): int
    {
        $size = 0;
        if ($depth >= 3) {
            throw new \InvalidArgumentException('Depth must be less than 3');
        }
        if ($columnCount === null) {
            $pages = $this->getNumberOfPagesForFiles($entries);
            $columnCount = $pages > 1 ? 2 : 1;
        }
        foreach ($entries as $entry) {
            if (is_array($entry)) {
                $size += $this->calculateMultidimensionalArraySize($entry, $columnCount, $depth + 1);
            } else {
                $size += $this->getSizeForText($entry);
            }
        }
        return $size;
    }

    /**
     * Calculate the number of lines for a given text
     *
     * @param string $text
     * @param int $columnCount
     * @return int
     */
    private function calculateNumberOfLines(string $text, int $columnCount = 1): int
    {
        return ceil(strlen($text) / (130 / $columnCount));
    }

    /**
     * Calculate the size of a given text considering its column count. It adds the line height for each line.
     *
     * @param $text
     * @param int $columnCount
     * @return int
     */
    public function getSizeForText($text, int $columnCount = 1): int
    {
        if (!is_string($text)) {
            $text = (string)$text;
        }
        return $this->calculateNumberOfLines($text, $columnCount) * $this->lineHeight;
    }

    /**
     * Get the first section of the subresults (errors, warnings or suggestions)
     *
     * @param $subResults
     * @return array
     */
    private function getFirstSection($subResults): array
    {
        if (isset($subResults['errors'])) {
            return $subResults['errors'];
        } elseif (isset($subResults['warnings'])) {
            return $subResults['warnings'];
        } elseif (isset($subResults['suggestions'])) {
            return $subResults['suggestions'];
        }
    }

    /**
     * Calculate the size of the intro section of the PDF. It is not made to be used with the intro of the sections.
     *
     * @param $intro
     * @return int
     */
    public function calculateIntroSize($intro): int
    {
        $size = 0;
        $size += $this->calculateNumberOfLines(preg_replace('/\s+/', ' ', $intro['summary'])) * $this->lineHeight;

        return $size;
    }

    /**
     * @param $files
     * @return int if $sizeForTwoColumns is bigger than $size, return $sizeForTwoColumns
     */
    public function getNumberOfPagesForFiles($files): int
    {
        $size = 0;
        $this->logger->info('Files count: ' . count($files));
        $sizeForTwoColumns = 0;
        if (is_string($files)) {
            $size = $this->calculateNumberOfLines($files) * $this->lineHeight;
            $sizeForTwoColumns = $this->calculateNumberOfLines($files, 2) * $this->lineHeight;
        } else {
            foreach ($files ?? [] as $key => $file) {
                if (is_array($file)) {
                    $size += $this->calculateNumberOfLines($key) * $this->lineHeight;
                    $sizeForTwoColumns += $this->calculateNumberOfLines($key, 2) * $this->lineHeight;
                    foreach ($file as $subFile) {
                        $size += $this->calculateNumberOfLines($subFile) * $this->lineHeight;
                        $sizeForTwoColumns += $this->calculateNumberOfLines($subFile, 2) * $this->lineHeight;
                    }
                } else {
                    $size += $this->calculateNumberOfLines($file) * $this->lineHeight;
                    $sizeForTwoColumns += $this->calculateNumberOfLines($file, 2) * $this->lineHeight;
                }
            }
        }
        return $sizeForTwoColumns > $size ? ceil($sizeForTwoColumns / 800) : ceil($size / 800);
    }
}