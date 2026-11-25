<?php

namespace App\Model;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * One page of results, serialized as { "total": 25, "lastPage": 3, "items": [...] }.
 *
 * Built on Doctrine's OffsetPaginator (the old Doctrine Paginator is deprecated).
 * Serialize it with the "paginate" group plus the groups of the items.
 *
 * @template T
 */
class Paginator
{
    /** Number of results across all pages. */
    #[Groups('paginate')]
    public int $total = 0;

    /** Number of the last page (1 when there is no result). */
    #[Groups('paginate')]
    public int $lastPage = 1;

    /** @var list<T> */
    #[Groups('paginate')]
    public array $items = [];

    /**
     * @return self<T>
     */
    public function paginate(QueryBuilder $query, PaginationDTO $paginationDTO): self
    {
        // fetchJoinCollection: counts root entities correctly even when the query joins collections.
        $page = (new OffsetPaginator(fetchJoinCollection: true))
            ->paginate($query, Window::fromPageNumberAndSize($paginationDTO->page, $paginationDTO->limit));

        $this->total = $page->getTotalCount();
        $this->lastPage = $page->getPageCount();
        $this->items = array_values($page->getItems());

        return $this;
    }
}
