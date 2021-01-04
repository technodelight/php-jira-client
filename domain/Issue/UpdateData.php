<?php

declare(strict_types=1);

namespace Technodelight\Jira\Domain\Issue;

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

    public static function createEmpty(): self
    {
        return new self;
    }

    public function asArray(): array
    {
        return array_filter([
            'transition' => $this->transition ? ['id' => $this->transition->id()]: null,
            'fields' => !empty($this->fields) ? $this->fields : null,
            'update' => !empty($this->updates) ? $this->prepareUpdates() : null
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
        $this->fields[$fieldName] = $fieldValue;

        return $this;
    }

    public function add(string $fieldName, $fieldValue): self
    {
        $this->adds[$fieldName] = $fieldValue;
        $this->updates[] = $fieldName;

        return $this;
    }

    public function set(string $fieldName, $fieldValue): self
    {
        $this->sets[$fieldName] = $fieldValue;
        $this->updates[] = $fieldName;

        return $this;
    }

    public function edit(string $fieldName, $fieldValue): self
    {
        $this->edits[$fieldName] = $fieldValue;
        $this->updates[] = $fieldName;

        return $this;
    }

    public function remove(string $fieldName, $fieldValue): self
    {
        $this->removes[$fieldName] = $fieldValue;
        $this->updates[] = $fieldName;

        return $this;
    }
}