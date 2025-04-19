<?php
namespace Gyro\Cloudflare;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for interacting with Cloudflare API
 */
class CloudflareService
{
    /**
     * @var string Cloudflare API key
     */
    private $apiKey;

    /**
     * @var string Cloudflare API token
     */
    private $apiToken;

    /**
     * @var string Cloudflare account email
     */
    private $email;

    /**
     * @var string Cloudflare API base URL
     */
    private $baseUrl = 'https://api.cloudflare.com/client/v4';

    /**
     * @var Client HTTP client for API requests
     */
    private $httpClient;

    /**
     * Create a new Cloudflare service
     *
     * @param array $credentials Authentication credentials
     *                          - apiKey: Cloudflare API key
     *                          - apiToken: Cloudflare API token (preferred over apiKey)
     *                          - email: Cloudflare account email (required if using apiKey)
     */
    public function __construct(array $credentials)
    {
        if (isset($credentials['apiToken'])) {
            $this->apiToken = $credentials['apiToken'];
        } elseif (isset($credentials['apiKey']) && isset($credentials['email'])) {
            $this->apiKey = $credentials['apiKey'];
            $this->email = $credentials['email'];
        } else {
            throw new \InvalidArgumentException('Either apiToken or both apiKey and email must be provided');
        }

        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30.0,
        ]);
    }

    /**
     * Make a request to the Cloudflare API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $params Query parameters
     * @param array|null $body Request body
     * @return array Response data
     * @throws \Exception If the API request fails
     */
    private function makeRequest(string $method, string $endpoint, array $params = [], ?array $body = null): array
    {
        $options = [
            'headers' => $this->getHeaders(),
            'query' => $params,
        ];

        if ($body !== null) {
            $options['json'] = $body;
        }

        try {
            $response = $this->httpClient->request($method, $endpoint, $options);
            $data = json_decode($response->getBody()->getContents(), true);

            if (!$data['success']) {
                throw new \Exception('Cloudflare API error: ' . ($data['errors'][0]['message'] ?? 'Unknown error'));
            }

            return $data['result'];
        } catch (GuzzleException $e) {
            throw new \Exception('Cloudflare API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get headers for API requests
     *
     * @return array
     */
    private function getHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];

        if (isset($this->apiToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiToken;
        } else {
            $headers['X-Auth-Email'] = $this->email;
            $headers['X-Auth-Key'] = $this->apiKey;
        }

        return $headers;
    }

    /**
     * List DNS records for a zone
     *
     * @param string $zoneId Zone ID
     * @param RecordType|null $type Filter by record type
     * @param string|null $name Filter by record name
     * @param string|null $content Filter by record content
     * @return Record[] Array of Record objects
     * @throws \Exception If the API request fails
     */
    public function listRecords(string $zoneId, ?RecordType $type = null, ?string $name = null, ?string $content = null): array
    {
        $filters = [];
        
        if ($type !== null) {
            $filters['type'] = $type->value;
        }
        
        if ($name !== null) {
            $filters['name'] = $name;
        }
        
        if ($content !== null) {
            $filters['content'] = $content;
        }
        
        $endpoint = "/zones/{$zoneId}/dns_records";
        $data = $this->makeRequest('GET', $endpoint, $filters);

        $records = [];
        foreach ($data as $recordData) {
            $records[] = Record::fromApiArray($recordData);
        }

        return $records;
    }

    /**
     * Create a DNS record
     *
     * @param string $zoneId Zone ID
     * @param Record $record Record to create
     * @return Record Created record
     * @throws \Exception If the API request fails
     */
    public function createRecord(string $zoneId, Record $record): Record
    {
        $endpoint = "/zones/{$zoneId}/dns_records";
        $data = $this->makeRequest('POST', $endpoint, [], $record->toApiArray());

        return Record::fromApiArray($data);
    }

    /**
     * Update a DNS record
     *
     * @param string $zoneId Zone ID
     * @param string $recordId Record ID
     * @param Record $record Updated record data
     * @return Record Updated record
     * @throws \Exception If the API request fails
     */
    public function updateRecord(string $zoneId, string $recordId, Record $record): Record
    {
        $endpoint = "/zones/{$zoneId}/dns_records/{$recordId}";
        $data = $this->makeRequest('PUT', $endpoint, [], $record->toApiArray());

        return Record::fromApiArray($data);
    }

    /**
     * Delete a DNS record
     *
     * @param string $zoneId Zone ID
     * @param string $recordId Record ID
     * @return bool True if successful
     * @throws \Exception If the API request fails
     */
    public function deleteRecord(string $zoneId, string $recordId): bool
    {
        $endpoint = "/zones/{$zoneId}/dns_records/{$recordId}";
        $this->makeRequest('DELETE', $endpoint);

        return true;
    }

    /**
     * Execute a batch of operations
     *
     * @param string $zoneId Zone ID
     * @param Batch $batch Batch of operations
     * @return array Results of batch operations
     * @throws \Exception If the API request fails
     */
    public function executeBatch(string $zoneId, Batch $batch): array
    {
        if (!$batch->hasOperations()) {
            return [];
        }

        $results = [];
        foreach ($batch->getOperationsArray() as $operation) {
            $endpoint = "/zones/{$zoneId}" . $operation['path'];
            $method = $operation['method'];
            $data = $operation['data'] ?? null;

            try {
                $result = $this->makeRequest($method, $endpoint, [], $data);
                $results[] = [
                    'success' => true,
                    'operation' => $operation,
                    'result' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'operation' => $operation,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
