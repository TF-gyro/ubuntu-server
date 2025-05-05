<?php

namespace Gyro\Service;

use PDO;

/**
 * Service class for managing port allocation and availability checks.
 * 
 * This service handles the allocation of ports for tribe and junction services,
 * ensuring that allocated ports are available and not in use by other services.
 */
class PortService
{
    /** @var PDO Database connection */
    private PDO $db;

    /** @var int Default starting port for tribe service */
    private const DEFAULT_TRIBE_PORT = 8080;

    /** @var int Default starting port for junction service */
    private const DEFAULT_JUNCTION_PORT = 8081;

    /** @var int Minimum allowed port number */
    private const MIN_PORT = 1024;

    /** @var int Maximum allowed port number */
    private const MAX_PORT = 49152;

    /**
     * PortService constructor.
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get available ports for tribe and junction services.
     *
     * This method:
     * 1. Checks the last used ports from the database
     * 2. Finds the next available ports
     * 3. Ensures ports are not in use by other services
     * 4. Validates port ranges
     *
     * @return array{ tribe_port: int, junction_port: int } Array containing available ports
     * @throws \RuntimeException If no available ports are found
     */
    public function getAvailablePorts(): array
    {
        $row = $this->db
            ->query("SELECT * FROM dockers ORDER BY id DESC LIMIT 1")
            ->fetch();

        // Initialize with default ports
        $tribePort = self::DEFAULT_TRIBE_PORT;
        $junctionPort = self::DEFAULT_JUNCTION_PORT;

        if ($row) {
            // Increase by 1 based on junction's port since it should be higher than tribe's
            $tribePort = $junctionPort = $row['junction_port'] + 1;
        }

        // Validate initial ports
        if ($tribePort < self::MIN_PORT || $tribePort > self::MAX_PORT) {
            $tribePort = self::DEFAULT_TRIBE_PORT;
        }

        // Find free system port for tribe
        $tribePort = $this->findNextAvailablePort($tribePort);
        
        // Junction port should be tribe port + 1
        $junctionPort = $tribePort + 1;
        $junctionPort = $this->findNextAvailablePort($junctionPort);

        // Final validation of allocated ports
        if ($tribePort > self::MAX_PORT || $junctionPort > self::MAX_PORT) {
            throw new \RuntimeException('No available ports found in the valid range');
        }

        return [
            'tribe_port' => $tribePort,
            'junction_port' => $junctionPort
        ];
    }

    /**
     * Find the next available port starting from the given port.
     *
     * @param int $startPort The port to start checking from
     * @return int The first available port found
     * @throws \RuntimeException If no available ports are found
     */
    private function findNextAvailablePort(int $startPort): int
    {
        $currentPort = $startPort;
        $maxAttempts = 100; // Prevent infinite loops

        while (!$this->isPortAvailable($currentPort)) {
            $currentPort++;
            $maxAttempts--;

            if ($currentPort > self::MAX_PORT || $maxAttempts <= 0) {
                throw new \RuntimeException('No available ports found after maximum attempts');
            }
        }

        return $currentPort;
    }

    /**
     * Check if a port is available on the system.
     *
     * @param int $port The port to check
     * @return bool True if the port is available, false otherwise
     * @throws \InvalidArgumentException If the port is outside the valid range
     */
    private function isPortAvailable(int $port): bool
    {
        if ($port < self::MIN_PORT || $port > self::MAX_PORT) {
            throw new \InvalidArgumentException("Port $port is outside the valid range (" . self::MIN_PORT . "-" . self::MAX_PORT . ")");
        }

        exec("netstat -tnlp | grep $port", $output, $status);
        return $status !== 0;
    }
}
