<?php
namespace Gyro\Cloudflare;

/**
 * Enum representing DNS record types supported by Cloudflare
 */
enum RecordType: string
{
    case A = 'A';
    case AAAA = 'AAAA';
    case CNAME = 'CNAME';
    case MX = 'MX';
    case TXT = 'TXT';
    case SRV = 'SRV';
    case CAA = 'CAA';
    case NS = 'NS';
    case PTR = 'PTR';
    case LOC = 'LOC';
    case SSHFP = 'SSHFP';
    case TLSA = 'TLSA';
    case URI = 'URI';
    case HTTPS = 'HTTPS';
    case SVCB = 'SVCB';
    
    /**
     * Get all available record types
     * 
     * @return array<string>
     */
    public static function getAllTypes(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    
    /**
     * Check if a string is a valid record type
     * 
     * @param string $type
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::getAllTypes());
    }
    
    /**
     * Create a RecordType from a string
     * 
     * @param string $type
     * @return self
     * @throws \InvalidArgumentException if the type is not valid
     */
    public static function fromString(string $type): self
    {
        if (!self::isValid($type)) {
            throw new \InvalidArgumentException("Invalid record type: {$type}");
        }
        
        return self::from($type);
    }
} 