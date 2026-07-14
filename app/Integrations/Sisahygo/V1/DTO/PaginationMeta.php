<?php

namespace App\Integrations\Sisahygo\V1\DTO;

final readonly class PaginationMeta
{
    public function __construct(public int $currentPage, public int $perPage, public ?int $total = null) {}
}