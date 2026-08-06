<?php

namespace App\Services\Export;

/**
 * Immutable column definition supplied by an ExportableEntity adapter.
 */
final class ExportColumnDefinition
{
    public const TYPE_STRING = 'string';

    public const TYPE_NUMBER = 'number';

    public const TYPE_DATE = 'date';

    public const TYPE_DATETIME = 'datetime';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_COMPUTED = 'computed';

    public const TYPE_RELATIONSHIP = 'relationship';

    /** @var list<string> */
    public const DATA_TYPES = [
        self::TYPE_STRING,
        self::TYPE_NUMBER,
        self::TYPE_DATE,
        self::TYPE_DATETIME,
        self::TYPE_BOOLEAN,
        self::TYPE_COMPUTED,
        self::TYPE_RELATIONSHIP,
    ];

    /**
     * @param  string|null  $attribute  Model attribute / dotted path when not computed
     * @param  list<string>  $eager  Relations required when this column is selected
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $dataType = self::TYPE_STRING,
        public readonly bool $default = true,
        public readonly bool $hidden = false,
        public readonly bool $sensitive = false,
        public readonly ?string $attribute = null,
        public readonly array $eager = [],
    ) {
        if ($key === '') {
            throw new \InvalidArgumentException('Export column key cannot be empty.');
        }

        if (! in_array($dataType, self::DATA_TYPES, true)) {
            throw new \InvalidArgumentException("Unsupported export column data type [{$dataType}].");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'data_type' => $this->dataType,
            'default' => $this->default,
            'hidden' => $this->hidden,
            'sensitive' => $this->sensitive,
        ];
    }
}
