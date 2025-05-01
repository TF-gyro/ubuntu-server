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
     *
     * @return array{ tribe_port: int, junction_port: int } Array containing available ports
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

        // Find free system port for tribe
        $tribePort = $this->findNextAvailablePort($tribePort);
        
        // Junction port should be tribe port + 1
        $junctionPort = $tribePort + 1;
        $junctionPort = $this->findNextAvailablePort($junctionPort);

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
     */
    private function findNextAvailablePort(int $startPort): int
    {
        $currentPort = $startPort;
        while (!$this->isPortAvailable($currentPort)) {
            $currentPort++;
        }
        return $currentPort;
    }

    /**
     * Check if a port is available on the system.
     *
     * @param int $port The port to check
     * @return bool True if the port is available, false otherwise
     */
    private function isPortAvailable(int $port): bool
    {
        exec("netstat -tnlp | grep $port", $output, $status);
        return $status !== 0;
    }
}
