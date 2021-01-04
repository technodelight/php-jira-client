<?php

declare(strict_types=1);

namespace Technodelight\Jira\Domain\Issue;

use InvalidArgumentException;
use Technodelight\Jira\Domain\Transition;

final class UpdateData
{
    private bool $notifyUsers = true;
    private ?Transition $transition = null;
    private array $fields = [];
    private array $adds = [];
    private array $sets = [];
    private array $edits = [];
    private array $removes = [];
    private array $updates = [];
    private array $properties = [];

    public static function createEmpty(): self
    {
        return new self;
    }

    public function asArray(): array
    {
        return array_filter([
            'transition' => $this->transition ? ['id' => $this->transition->id()]: null,
            'fields' => !empty($this->fields) ? $this->fields : null,
            'update' => !empty($this->updates) ? $this->prepareUpdates() : null,
            'properties' => !empty($this->properties) ? $this->properties : null,
        ], static function($value) { return $value !== null; });
    }

    private function __construct()
    {
    }

    private function prepareUpdates(): array
    {
        $data = [];
        foreach ($this->updates as $fieldName) {
            $data[$fieldName] = !empty($data[$fieldName]) ? $data[$fieldName] : [];
            foreach ($this->adds as $fieldName => $values) {
                $data[$fieldName][] = ['add' => $values];
            }
            foreach ($this->sets as $fieldName => $values) {
                $data[$fieldName][] = ['set' => $values];
            }
            foreach ($this->edits as $fieldName => $values) {
                $data[$fieldName][] = ['edit' => $values];
            }
            foreach ($this->removes as $fieldName => $values) {
                $data[$fieldName][] = ['remove' => $values];
            }
        }

        return $data;
    }

    public function notifyUsers(bool $flag = null)
    {
        if (null === $flag) {
            return $this->notifyUsers;
        }

        return $this->notifyUsers;
    }

    public function transition(Transition $transition): self
    {
        $this->transition = $transition;

        return $this;
    }

    public function addField(string $fieldName, $fieldValue): self
    {
        if (in_array($fieldName, $this->updates)) {
            throw new InvalidArgumentException(
                sprintf('Cannot add field "%s" as it\'s already added as update', $fieldName)
            );
        }
        $this->fields[$fieldName] = $fieldValue;

        return $this;
    }

    public function add(string $fieldName, $fieldValue): self
    {
        $this->addUniqueUpdate($fieldName);
        $this->adds[$fieldName] = $fieldValue;

        return $this;
    }

    public function set(string $fieldName, $fieldValue): self
    {
        $this->addUniqueUpdate($fieldName);
        $this->sets[$fieldName] = $fieldValue;

        return $this;
    }

    public function edit(string $fieldName, $fieldValue): self
    {
        $this->addUniqueUpdate($fieldName);
        $this->edits[$fieldName] = $fieldValue;

        return $this;
    }

    public function remove(string $fieldName, $fieldValue): self
    {
        $this->addUniqueUpdate($fieldName);
        $this->removes[$fieldName] = $fieldValue;

        return $this;
    }

    public function addProperty(string $property, $value): self
    {
        $this->properties[] = ['key' => $property, 'value' => $value];

        return $this;
    }

    private function addUniqueUpdate($fieldName): void
    {
        if (isset($this->fields[$fieldName])) {
            throw new InvalidArgumentException(
                sprintf('Cannot add update on field "%s" as it\'s already set in "fields" section', $fieldName)
            );
        }

        $this->updates[] = $fieldName;
    }
}