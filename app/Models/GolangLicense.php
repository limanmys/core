<?php

namespace App\Models;

use Carbon\Carbon;

class GolangLicense implements \JsonSerializable
{
    private string $owner;

    private bool $valid;

    private int $client_count;

    private int $timestamp;

    public function __construct($license)
    {
        if (! $license) {
            $this->owner = '';
            $this->valid = false;
            $this->client_count = 0;
            $this->timestamp = 0;

            return;
        }

        $this->owner = $license->owner;
        $this->valid = $license->Valid;
        $this->client_count = $license->clientCount;
        $this->timestamp = strtotime($license->expireDate);
    }

    public function getOwner(): string
    {
        return $this->owner;
    }

    public function getValid(): bool
    {
        return $this->valid;
    }

    public function getClientCount(): int
    {
        return $this->client_count;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getFormattedTimestamp(): string
    {
        return Carbon::createFromTimestamp($this->timestamp)
            ->isoFormat('LL');
    }

    public function jsonSerialize(): mixed
    {
        return [
            'owner' => $this->owner,
            'valid' => $this->valid,
            'client_count' => $this->client_count,
            'timestamp' => $this->timestamp * 1000,
        ];
    }
}
