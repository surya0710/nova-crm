<?php

namespace App\Services\Import;

/**
 * Immutable field definition supplied by an ImportableEntity adapter.
 *
 * The Import Platform uses these for column detection, validation, and preview.
 * No entity persistence logic lives here.
 */
final class ImportFieldDefinition
{
    public const TYPE_STRING = 'string';

    public const TYPE_EMAIL = 'email';

    public const TYPE_PHONE = 'phone';

    public const TYPE_DATE = 'date';

    public const TYPE_NUMBER = 'number';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_BOOLEAN = 'boolean';

    /** @var list<string> */
    public const DATA_TYPES = [
        self::TYPE_STRING,
        self::TYPE_EMAIL,
        self::TYPE_PHONE,
        self::TYPE_DATE,
        self::TYPE_NUMBER,
        self::TYPE_INTEGER,
        self::TYPE_BOOLEAN,
    ];

    /**
     * @param  list<string>  $aliases  Additional header labels that map to this field
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly bool $required = false,
        public readonly string $dataType = self::TYPE_STRING,
        public readonly bool $supportsMetadata = false,
        public readonly array $aliases = [],
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('Import field key cannot be empty.');
        }

        if (! in_array($dataType, self::DATA_TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported import field data type [{$dataType}].");
        }
    }

    /**
     * Normalized labels used for automatic column detection.
     *
     * @return list<string>
     */
    public function detectionLabels(): array
    {
        $labels = array_merge([$this->key, $this->label], $this->aliases);

        return array_values(array_unique(array_map(
            static fn (string $label): string => ColumnDetector::normalizeHeader($label),
            $labels
        )));
    }
}
