<?php

namespace Gyro\Dto;

/**
 * Data Transfer Object (DTO) for Instance information.
 * 
 * This class represents the data structure for an instance with all its required properties.
 * It provides a way to transfer instance data between different layers of the application.
 */
class InstanceDTO
{
    /** @var string The name of the application */
    private string $appName;

    /** @var string The unique identifier of the application */
    private string $appUid;

    /** @var string The secret key for the instance */
    private string $secret;

    /** @var string The domain name for the instance */
    private string $domain;

    /** @var int The port number for the tribe service */
    private int $tribePort;

    /** @var int The port number for the junction service */
    private int $junctionPort;

    /**
     * InstanceDTO constructor.
     *
     * @param string $appName The name of the application
     * @param string $appUid The unique identifier of the application
     * @param string $secret The secret key for the instance
     * @param string $domain The domain name for the instance
     * @param int $tribePort The port number for the tribe service
     * @param int $junctionPort The port number for the junction service
     */
    public function __construct(
        string $appName,
        string $appUid,
        string $secret,
        string $domain,
        int $tribePort,
        int $junctionPort
    ) {
        $this->appName = $appName;
        $this->appUid = $appUid;
        $this->secret = $secret;
        $this->domain = $domain;
        $this->tribePort = $tribePort;
        $this->junctionPort = $junctionPort;
    }

    /**
     * Get the name of the application.
     *
     * @return string The application name
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Set the name of the application.
     *
     * @param string $appName The application name
     * @return self
     */
    public function setAppName(string $appName): self
    {
        $this->appName = $appName;
        return $this;
    }

    /**
     * Get the unique identifier of the application.
     *
     * @return string The application UID
     */
    public function getAppUid(): string
    {
        return $this->appUid;
    }

    /**
     * Set the unique identifier of the application.
     *
     * @param string $appUid The application UID
     * @return self
     */
    public function setAppUid(string $appUid): self
    {
        $this->appUid = $appUid;
        return $this;
    }

    /**
     * Get the secret key for the instance.
     *
     * @return string The instance secret
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Set the secret key for the instance.
     *
     * @param string $secret The instance secret
     * @return self
     */
    public function setSecret(string $secret): self
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Get the domain name for the instance.
     *
     * @return string The instance domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Set the domain name for the instance.
     *
     * @param string $domain The instance domain
     * @return self
     */
    public function setDomain(string $domain): self
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Get the port number for the tribe service.
     *
     * @return int The tribe service port
     */
    public function getTribePort(): int
    {
        return $this->tribePort;
    }

    /**
     * Set the port number for the tribe service.
     *
     * @param int $tribePort The tribe service port
     * @return self
     */
    public function setTribePort(int $tribePort): self
    {
        $this->tribePort = $tribePort;
        return $this;
    }

    /**
     * Get the port number for the junction service.
     *
     * @return int The junction service port
     */
    public function getJunctionPort(): int
    {
        return $this->junctionPort;
    }

    /**
     * Set the port number for the junction service.
     *
     * @param int $junctionPort The junction service port
     * @return self
     */
    public function setJunctionPort(int $junctionPort): self
    {
        $this->junctionPort = $junctionPort;
        return $this;
    }

    /**
     * Convert the DTO to an associative array.
     * 
     * @return array An associative array containing all DTO properties
     */
    public function toArray(): array
    {
        return [
            'app_name' => $this->appName,
            'app_uid' => $this->appUid,
            'secret' => $this->secret,
            'domain' => $this->domain,
            'tribe_port' => $this->tribePort,
            'junction_port' => $this->junctionPort
        ];
    }
}
