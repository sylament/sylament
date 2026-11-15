<?php

declare(strict_types=1);

namespace Sylament\Tests\Fixtures\Infrastructure;

use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Sylament\Tests\Fixtures\Entity\Tally;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
final class TallyController
{
    public function __invoke(int $id, EntityManagerInterface $entityManager): Response
    {
        $tally = $entityManager->find(Tally::class, $id) ?? throw new LogicException(sprintf('No tally has the id %d.', $id));
        $tally->count++;
        $entityManager->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
