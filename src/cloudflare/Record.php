<?php
namespace Gyro\Cloudflare;

/**
 * Represents a DNS record in Cloudflare
 */
class Record
{
    /**
     * @var RecordType Record type (A, AAAA, CNAME, MX, TXT, etc.)
     */
    private RecordType $type;

    /**
     * @var string Record name
     */
    private string $name;

    /**
     * @var string Record content
     */
    private string $content;

    /**
     * @var int Time to live in seconds
     */
    private int $ttl;

    /**
     * @var bool Whether the record is proxied through Cloudflare
     */
    private bool $proxied;

    /**
     * @var string|null Optional comment
     */
    private ?string $comment;

    /**
     * @var string|null Record ID (set after creation)
     */
    private ?string $id;

    /**
     * @var array Additional metadata
     */
    private array $metadata = [];

    /**
     * Create a new DNS record
     *
     * @param RecordType $type Record type (A, AAAA, CNAME, MX, TXT, etc.)
     * @param string $name Record name
     * @param string $content Record content
     * @param int $ttl Time to live in seconds
     * @param bool $proxied Whether the record is proxied through Cloudflare
     * @param string|null $comment Optional comment
     */
    public function __construct(
        RecordType $type, 
        string $name, 
        string $content, 
        int $ttl = 1, 
        bool $proxied = true, 
        ?string $comment = null
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->content = $content;
        $this->ttl = $ttl;
        $this->proxied = $proxied;
        $this->comment = $comment;
    }

    /**
     * Convert record to API array format
     *
     * @return array
     */
    public function toApiArray(): array
    {
        $data = [
            'type' => $this->type->value,
            'name' => $this->name,
            'content' => $this->content,
            'ttl' => $this->ttl,
            'proxied' => $this->proxied,
        ];

        if ($this->comment !== null) {
            $data['comment'] = $this->comment;
        }

        return $data;
    }

    /**
     * Create a Record object from API response data
     *
     * @param array $data API response data
     * @return self
     * @throws \InvalidArgumentException if the record type is invalid
     */
    public static function fromApiArray(array $data): self
    {
        $record = new self(
            RecordType::fromString($data['type']),
            $data['name'],
            $data['content'],
            $data['ttl'] ?? 1,
            $data['proxied'] ?? true,
            $data['comment'] ?? null
        );

        if (isset($data['id'])) {
            $record->setId($data['id']);
        }

        // Store additional metadata
        foreach ($data as $key => $value) {
            if (!in_array($key, ['type', 'name', 'content', 'ttl', 'proxied', 'comment', 'id'])) {
                $record->setMetadata($key, $value);
            }
        }

        return $record;
    }

    /**
     * Get record type
     *
     * @return RecordType
     */
    public function getType(): RecordType
    {
        return $this->type;
    }

    /**
     * Set record type
     *
     * @param RecordType $type
     * @return self
     */
    public function setType(RecordType $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get record name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set record name
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get record content
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set record content
     *
     * @param string $content
     * @return self
     */
    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Get TTL
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set TTL
     *
     * @param int $ttl
     * @return self
     */
    public function setTtl(int $ttl): self
    {
        $this->ttl = $ttl;
        return $this;
    }

    /**
     * Is record proxied
     *
     * @return bool
     */
    public function isProxied(): bool
    {
        return $this->proxied;
    }

    /**
     * Set proxied status
     *
     * @param bool $proxied
     * @return self
     */
    public function setProxied(bool $proxied): self
    {
        $this->proxied = $proxied;
        return $this;
    }

    /**
     * Get comment
     *
     * @return string|null
     */
    public function getComment(): ?string
    {
        return $this->comment;
    }

    /**
     * Set comment
     *
     * @param string|null $comment
     * @return self
     */
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Get record ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * Set record ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get metadata value
     *
     * @param string $key
     * @return mixed
     */
    public function getMetadata(string $key)
    {
        return $this->metadata[$key] ?? null;
    }

    /**
     * Set metadata value
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Get all metadata
     *
     * @return array
     */
    public function getAllMetadata(): array
    {
        return $this->metadata;
    }
}
