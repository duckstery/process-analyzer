<?php

namespace Duckstery\Analyzer;

use Duckstery\Analyzer\Interfaces\IAPrinter;
use Duckstery\Analyzer\Interfaces\IAProfile;
use Duckstery\Analyzer\Interfaces\IARecord;
use Duckstery\Analyzer\Structures\AnalysisDataset;
use Duckstery\Analyzer\Structures\AnalysisProfile;
use Duckstery\Analyzer\Structures\AnalysisRecord;

class AnalysisPrinter extends IAPrinter
{
    // ***************************************
    // Properties
    // ***************************************

    /**
     * @var AnalysisDataset[] Pretty print datasets
     */
    protected array $datasets;

    /**
     * @var int Count the number of Record
     */
    protected int $count;

    /**
     * @var string Final content
     */
    protected string $content;

    // ***************************************
    // Public API
    // ***************************************

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->content = "";
        $this->datasets = [];
        $this->count = 0;
    }

    /**
     * Print Profile
     *
     * @param AnalysisProfile $profile
     * @return void
     */
    public function printProfile(IAProfile $profile): void
    {
        // Hook before convert Profile
        $this->preprocessProfile($profile);

        // Get Records
        $records = $profile->getRecords();
        // Count
        $size = count($records);

        // Iterate through each $record
        for ($i = 0; $i < $size; $i++) {
            // Increase count
            $this->count += 1;
            // Preprocess Record's data
            $data = $this->preprocessRecord($records[$i]);
            // Convert data
            $this->convertData($data);

            // Check if ...
            if (Analyzer::config()->showStructure()) {
                $i += $this->processRecordByRelations($records[$i], $data);
            }
        }

        // Check if pretty print
        if (Analyzer::config()->prettyPrint()) {
            // Convert to table
            $this->convertToTableAndAppendToString($this->datasets);
        }

        $this->wrapContentInProfile($profile);
        $this->printContent($this->content);
    }

    // ***************************************
    // Private API
    // ***************************************

    /**
     * Preprocess Profile
     *
     * @param IAProfile $profile
     * @return void
     */
    protected function preprocessProfile(IAProfile $profile): void
    {
        // Call hook
        Utils::callHook($this, "onPreprocessProfile", $profile);
        // Apply prefix and suffix for Profile's name
        $profile->setName(
            Analyzer::config()->profilePrefix() . $profile->getName() . Analyzer::config()->profileSuffix()
        );
    }

    /**
     * Preprocess Record
     *
     * @param IARecord $record
     * @return array
     */
    protected function preprocessRecord(IARecord $record): array
    {
        // Hook: beforePrint for AnalysisProfile
        Utils::callHook($this, "onPreprocessRecord", $record);

        // $record new Name
        $name = Analyzer::config()->recordPrefix() . $record->getName() . Analyzer::config()->recordSuffix();
        // Apply prefix and suffix for Profile's name
        $record->setName($name);

        // Preprocess other data
        $output = [
            'uid' => $record->getUID(),
            'name' => $record->getName(),
            'time' => $record->actualTime(),
            'memory' => $record->actualMem(),
            ...$record->getExtras()
        ];

        return $output;
    }

    /**
     * Format data
     *
     * @param array $data
     * @return array
     */
    protected function formatData(array $data): array
    {
        // Format time and memory
        $data['time'] = Analyzer::config()->timeFormatter($data['time']);
        $data['memory'] = Analyzer::config()->memFormatter($data['memory']);

        // Skip UID column
        if (!Analyzer::config()->showUID()) unset($data['uid']);

        // Hook: onEachPreprocessedRecord
        Utils::callHook($this, "onEachPreprocessedRecord", $data);

        return $data;
    }

    /**
     * Process another Record by following $record's relations
     * This method is only applicable to AnalysisRecord
     *
     * @param IARecord $record
     * @param array $recordData
     * @return int
     */
    protected function processRecordByRelations(IARecord $record, array $recordData): int
    {
        // Number of processed Record
        $output = 0;

        // If not an instance of AnalysisRecord, skip this step
        if (!$record instanceof AnalysisRecord::class) return $output;

        // Iterate through each relation
        /** @var AnalysisRecord $record */
        foreach ($record->getRelations() as $relation) {
            // Check if
        }
    }

    /**
     * Convert data to printable
     *
     * @param array $data
     * @return array
     */
    protected function convertData(array $data): array
    {
        // Format data
        $data = $this->formatData($data);

        // Check if Printer should prepare for pretty print
        if (Analyzer::config()->prettyPrint()) {
            // Convert Record to datasets
            $this->convertAndPushToDatasets($data);
        } else {
            // Convert to printable cols
            $this->convertAndAppendToString($data);
        }
    }

    /**
     * Convert Record to datasets
     *
     * @param array $data
     */
    protected function convertAndPushToDatasets(array $data): void
    {
        // Iterate through each $data key
        foreach ($data as $key => $value) {
            // Check if dataset is set
            if (!isset($this->datasets[$key])) $this->datasets[$key] = new AnalysisDataset(strlen($key));
            // Add data to dataset
            $this->datasets[$key]->add($value);
        }
    }

    /**
     * Convert Record to string
     *
     * @param array $data
     * @return void
     */
    protected function convertAndAppendToString(array $data): void
    {
        $line = [""];
        // UID
        if (Analyzer::config()->showUID()) $line[0] = sprintf("[%s] ", $data['uid']);
        // Name
        $line[0] .= sprintf("%s:", $data['name']);
        // Iterate through each field in $data
        foreach ($data as $field => $value) {
            // Skip 'uid' and 'name'
            if ($field === 'uid' || $field === 'name') continue;
            // Data as string
            $line[] = sprintf("%s ⇒ [%s];", ucfirst($field), $data[$field] ?? "");
        }

        // Create content
        $content = implode(Analyzer::config()->oneLine() ? " " : (PHP_EOL . "\t"), $line);
        // Call hook
        Utils::callHook($this, "onEachRecordString", $content);

        // Add to content
        $this->content .= $content . PHP_EOL;
    }

    /**
     * Convert datasets to table string
     *
     * @param AnalysisDataset[] $datasets
     * @return void
     */
    protected function convertToTableAndAppendToString(array $datasets): void
    {
        // Create column width structure (2 for padding)
        $widthOfColumns = array_map(fn($dataset) => $dataset->getMaxLength() + 2, $datasets);

        $this->content = $this->createHeader($widthOfColumns);

        // Iterate through each
        for ($i = 0; $i < $this->count; $i++) {
            // Iterate through each column
            foreach ($widthOfColumns as $header => $width) {
                $this->content .= Analyzer::config()->verticalLineChar()
                    . str_pad(" " . $datasets[$header]->get($i) . " ", $width, " ", (int)($header === "name"));
            }

            // Add last border and linebreak
            $this->content .= Analyzer::config()->verticalLineChar() . PHP_EOL;
        }

        // Create last border
        $this->content .= $this->createBorderRow(
                $widthOfColumns,
                Analyzer::config()->bottomLeftChar(),
                Analyzer::config()->bottomForkChar(),
                Analyzer::config()->bottomRightChar()
            ) . PHP_EOL;
    }

    /**
     * Wrap report string in Profile string
     *
     * @param IAProfile $profile
     * @return void
     */
    protected function wrapContentInProfile(IAProfile $profile): void
    {
        $this->content =
            $profile->getName() . " " . str_repeat("-", 20) . PHP_EOL .
            $this->content .
            str_repeat("-", 20 + strlen($profile->getName() . " ")) . PHP_EOL;
    }

    /**
     * Print content
     *
     * @return void
     */
    protected function printContent(string $content)
    {
        // Hook: printRecord
        Utils::callHook($this, "onPrintProfileString", $this->content);

        // Check if Printer should print to file
        $useFile = Analyzer::config()->useFile();
        if ($useFile) {
            // Try to create directory
            is_dir($useFile) || mkdir($useFile);
            // Get file name
            file_put_contents($useFile . DIRECTORY_SEPARATOR . date('Y-m-d') . ".log", $content, FILE_APPEND);
        }

        // Check if Printer should print to console
        if (Analyzer::config()->useConsole()) {
            // Print to console
            printf("%s", $content);
        }
    }

    /**
     * Create border row (row with no content)
     *
     * @param array $widthOfColumns
     * @param string $start
     * @param string $separator
     * @param string $end
     * @return string
     */
    protected function createBorderRow(array $widthOfColumns, string $start, string $separator, string $end): string
    {
        $content = array_map(fn($width) => str_repeat(Analyzer::config()->horizontalLineChar(), $width), $widthOfColumns);
        return $start . implode($separator, $content) . $end;
    }

    /**
     * Create header
     *
     * @param array $widthOfColumns
     * @return string
     */
    protected function createHeader(array $widthOfColumns): string
    {
        // Create first row (top border)
        $output = $this->createBorderRow(
                $widthOfColumns,
                Analyzer::config()->topLeftChar(),
                Analyzer::config()->topForkChar(),
                Analyzer::config()->topRightChar()
            ) . PHP_EOL;

        // Iterate through each dataset's keys to create header
        foreach (array_keys($this->datasets) as $key) {
            // Add headers
            $output .= Analyzer::config()->verticalLineChar()
                . str_pad(" " . ucfirst($key) . " ", $widthOfColumns[$key]);
        }

        // Create border to separate header and content
        $output .= Analyzer::config()->verticalLineChar() . PHP_EOL . $this->createBorderRow(
                $widthOfColumns,
                Analyzer::config()->leftForkChar(),
                Analyzer::config()->crossChar(),
                Analyzer::config()->rightForkChar()
            );

        return $output . PHP_EOL;
    }
}
