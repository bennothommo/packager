<?php

namespace Winter\Packager\Parser;

use Composer\Semver\VersionParser;

class InstallOutputParser implements Parser
{
    /**
     * Parses the output of the "install" command.
     *
     * @param string[] $output
     * @return array<string, mixed>
     */
    public function parse(array $output): array
    {
        $parser = new VersionParser;
        $section = null;
        $conflicts = false;
        $lockFile = [
            'locked' => [],
            'upgraded' => [],
            'removed' => [],
        ];
        $packages = [
            'installed' => [],
            'upgraded' => [],
            'removed' => [],
        ];
        $problems = [];
        $readingProblem = false;
        $currentProblem = [];

        foreach ($output as $line) {
            if (strpos($line, 'Lock file operations:') === 0) {
                $section = 'lock';
            }
            if (strpos($line, 'Package operations:') === 0) {
                $section = 'packages';
            }
            if (strpos($line, 'Your requirements could not be resolved') === 0) {
                $conflicts = true;
            }

            // Parsed locked and/or modified packages, if no conflicts occurred
            if (in_array($section, ['lock', 'packages']) && !$conflicts) {
                $line = trim($line);

                // Parse action
                if (!preg_match(
                    '/^\- ([A-Z][a-z]+) ([a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]?|-{0,2})[a-z0-9]+)*) \(([^\=)]+)( => ([^\)]+))?\)/i',
                    $line,
                    $matches
                )) {
                    continue;
                }

                $action = strtolower($matches[1]);
                $package = $matches[2];
                $version = trim($matches[6]);
                $newVersion = isset($matches[8]) ? trim($matches[8]) : null;

                if ($section === 'lock') {
                    switch ($action) {
                        case 'locking':
                            $lockFile['locked'][$package] = $parser->normalize($version);
                            break;
                        case 'upgrading':
                            $lockFile['upgraded'][$package] = [
                                $parser->normalize($version),
                                $parser->normalize($newVersion)
                            ];
                            break;
                        case 'removing':
                            $lockFile['removed'][] = $package;
                            break;
                    }
                } else {
                    switch ($action) {
                        case 'installing':
                            $packages['installed'][$package] = $parser->normalize($version);
                            break;
                        case 'upgrading':
                            $packages['upgraded'][$package] = [
                                $parser->normalize($version),
                                $parser->normalize($newVersion)
                            ];
                            break;
                        case 'removing':
                            $packages['removed'][] = $package;
                            break;
                    }
                }
            }

            // Parse problems when a conflict occurs
            if ($conflicts) {
                if ($readingProblem) {
                    if (strpos($line, '    - ') === 0) {
                        $currentProblem[] = trim($line);
                    } else {
                        $readingProblem = false;
                        $problems[] = implode(PHP_EOL, $currentProblem);
                    }
                } elseif (strpos($line, '  Problem') === 0) {
                    $readingProblem = true;
                    $currentProblem = [];
                }
            }
        }

        // Cap off a problem if it's the last thing read
        if ($readingProblem) {
            $problems[] = implode(PHP_EOL, $currentProblem);
        }

        return [
            'conflicts' => $conflicts,
            'problems' => $problems,
            'lockFile' => $lockFile,
            'packages' => $packages,
        ];
    }
}
