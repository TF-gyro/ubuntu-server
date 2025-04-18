<?php
namespace Gyro\Cloudflare;

/**
 * Represents a batch of DNS record operations
 */
class Batch
{
    /**
     * @var array List of operations to perform
     */
    private $operations = [];

    /**
     * Create a new batch
     */
    public function __construct()
    {
    }

    /**
     * Add a create operation to the batch
     *
     * @param Record $record Record to create
     * @return self
     */
    public function addCreate(Record $record): self
    {
        $this->operations[] = [
            'method' => 'POST',
            'path' => '/dns_records',
            'data' => $record->toApiArray()
        ];

        return $this;
    }

    /**
     * Add an update operation to the batch
     *
     * @param string $recordId ID of the record to update
     * @param Record $record Updated record data
     * @return self
     */
    public function addUpdate(string $recordId, Record $record): self
    {
        $this->operations[] = [
            'method' => 'PUT',
            'path' => '/dns_records/' . $recordId,
            'data' => $record->toApiArray()
        ];

        return $this;
    }

    /**
     * Add a delete operation to the batch
     *
     * @param string $recordId ID of the record to delete
     * @return self
     */
    public function addDelete(string $recordId): self
    {
        $this->operations[] = [
            'method' => 'DELETE',
            'path' => '/dns_records/' . $recordId
        ];

        return $this;
    }

    /**
     * Get operations array for API request
     *
     * @return array
     */
    public function getOperationsArray(): array
    {
        return $this->operations;
    }

    /**
     * Check if batch has any operations
     *
     * @return bool
     */
    public function hasOperations(): bool
    {
        return !empty($this->operations);
    }

    /**
     * Get number of operations in batch
     *
     * @return int
     */
    public function getOperationCount(): int
    {
        return count($this->operations);
    }

    /**
     * Clear all operations from batch
     *
     * @return self
     */
    public function clear(): self
    {
        $this->operations = [];
        return $this;
    }
}
