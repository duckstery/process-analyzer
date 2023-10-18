<?php

namespace Duckster\Analyzer;

use Duckster\Analyzer\Structures\AnalysisProfile;

class Analyzer
{
    // ***************************************
    // Configurable
    // ***************************************

    /**
     * @var string Default Profile name
     */
    protected static string $defaultProfile = "Default";

    /**
     * @var string[] Default record name getter
     */
    protected static array $defaultRecordGetter = [self::class, "getCaller"];

    /**
     * @var bool Print Profile as ASCII table
     */
    protected static bool $prettyPrint = true;

    /**
     * @var string Printer class
     */
    protected static string $printer = AnalysisPrinter::class;

    // ***************************************
    // Properties
    // ***************************************

    /**
     * @var AnalysisProfile[] Analyzer profiles
     */
    private static array $profiles = [];

    // ***************************************
    // Public API
    // ***************************************

    /**
     * Get Profiles
     *
     * @return AnalysisProfile[]
     */
    public static function getProfiles(): array
    {
        return self::$profiles;
    }

    /**
     * Get Profile by name
     *
     * @param string $name
     * @param bool $createIfNotExist
     * @return AnalysisProfile|null
     */
    public static function profile(string $name, bool $createIfNotExist = false): ?AnalysisProfile
    {
        // Create if not exist
        if ($createIfNotExist) {
            self::createProfile($name);
        }

        return self::$profiles[$name] ?? null;
    }

    /**
     * Create a Profile
     *
     * @param string $name
     * @return void
     */
    public static function createProfile(string $name): void
    {
        // Check if Profile exists
        if (!self::hasProfile($name)) {
            // Create Profile
            self::$profiles[$name] = AnalysisProfile::create($name);
        }
    }

    /**
     * Add a Profile. Return true if added successfully, else return false
     *
     * @param AnalysisProfile $profile
     * @return bool
     */
    public static function addProfile(AnalysisProfile $profile): bool
    {
        // Check if Profile exists
        if (self::hasProfile($profile->getName())) {
            return false;
        }

        // Create Profile
        self::$profiles[$profile->getName()] = $profile;

        return true;
    }

    /**
     * Delete Profile. Return Profile if delete successfully, else return null
     *
     * @param string $name
     * @return AnalysisProfile|null
     */
    public static function popProfile(string $name): ?AnalysisProfile
    {
        $output = null;

        if (self::hasProfile($name)) {
            // Get reference
            $output = self::$profiles[$name];
            // Delete
            unset(self::$profiles[$name]);
        }

        return $output;
    }

    /**
     * Clear all Profile
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$profiles = [];
    }

    /**
     * Start recording using Default Profile and return execution UID
     *
     * @param string|null $title
     * @return string
     */
    public static function start(string|null $title = null): string
    {
        return self::startProfile(static::$defaultProfile, $title);
    }

    /**
     * Start recoding using $profile Profile and return execution UID
     *
     * @param string $profileName
     * @param string|null $title
     * @return string
     */
    public static function startProfile(string $profileName, string|null $title = null): string
    {
        // Start recording
        return self::profile($profileName, true)
            ->write($title ?? call_user_func(static::$defaultRecordGetter));
    }

    /**
     * Stop the Record with $executionUID of Default Profile
     *
     * @param string $executionUID
     * @return void
     */
    public static function stop(string $executionUID): void
    {
        self::stopProfile(static::$defaultProfile, $executionUID);
    }

    /**
     * Stop the Record with $executionUID of $profile Profile
     *
     * @param string $profileName
     * @param string $executionUID
     * @return void
     */
    public static function stopProfile(string $profileName, string $executionUID): void
    {
        // Stop recording
        self::profile($profileName)?->close($executionUID);
    }

    /**
     * Check if Analyzer has $profile Profile
     *
     * @param string $profile
     * @return bool
     */
    public static function hasProfile(string $profile): bool
    {
        return array_key_exists($profile, self::$profiles);
    }

    /**
     * Flush Profile
     *
     * @param string|null $name
     * @return void
     */
    public static function flush(string $profileName = null): void
    {
        // Create a Printer instance
        $printerInstance = new self::$printer;

        if (is_null($profileName)) {
            // Iterate and flush all Profile
            foreach (array_keys(self::$profiles) as $profileName) {
                $printerInstance->printProfile(self::popProfile($profileName));
            }
        } elseif (self::hasProfile($profileName)) {
            // Pop and print
            $printerInstance->printProfile(self::popProfile($profileName));
        }
    }

    // ***************************************
    // Private API
    // ***************************************

    /**
     * Get level 1 caller method or level 0 caller script
     *
     * @return string
     */
    private static function getCaller(): string
    {
        // Get the backtrace
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        return count($backtrace) === 2
            ? "Function: " . $backtrace[1]['function']
            : $backtrace[0]['file'] . ":" . ($backtrace[0]['line'] ?? 0);
    }
}