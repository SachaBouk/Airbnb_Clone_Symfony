<?php

namespace App\Model;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Symfony\Component\Serializer\Attribute\Groups;

class Paginator
{
    #[Groups('paginate')]
    public int $total = 0;

    #[Groups('paginate')]
    public int $lastPage = 1;

    #[Groups('paginate')]
    public array $items = [];

    public function paginate(QueryBuilder $query, PaginationDTO $paginationDTO): self
    {
        $page = (new OffsetPaginator(fetchJoinCollection: true))
            ->paginate($query, Window::fromPageNumberAndSize($paginationDTO->page, $paginationDTO->limit));

        $this->total = $page->getTotalCount();
        $this->lastPage = $page->getPageCount();
        $this->items = array_values($page->getItems());

        return $this;
    }
}
