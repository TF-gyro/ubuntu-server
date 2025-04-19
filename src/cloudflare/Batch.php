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
    private $operations = [
        'deletes' => [],
        'patches' => [],
        'puts' => [],
        'posts' => []
    ];

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
        $this->operations['posts'][] = $record->toApiArray();
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
        $data = $record->toApiArray();
        $data['id'] = $recordId;
        $this->operations['patches'][] = $data;
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
        $this->operations['deletes'][] = ['id' => $recordId];
        return $this;
    }

    /**
     * Get operations array for API request
     *
     * @return array
     */
    public function getOperationsArray(): array
    {
        $operations = [];
        foreach ($this->operations as $method => $items) {
            if (!empty($items)) {
                $operations[$method] = $items;
            }
        }
        return $operations;
    }

    /**
     * Check if batch has any operations
     *
     * @return bool
     */
    public function hasOperations(): bool
    {
        foreach ($this->operations as $items) {
            if (!empty($items)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get number of operations in batch
     *
     * @return int
     */
    public function getOperationCount(): int
    {
        $count = 0;
        foreach ($this->operations as $items) {
            $count += count($items);
        }
        return $count;
    }

    /**
     * Clear all operations from batch
     *
     * @return self
     */
    public function clear(): self
    {
        $this->operations = [
            'deletes' => [],
            'patches' => [],
            'puts' => [],
            'posts' => []
        ];
        return $this;
    }
}
