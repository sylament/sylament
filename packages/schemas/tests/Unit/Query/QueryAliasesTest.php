<?php

declare(strict_types=1);

use Sylament\Schemas\Query\QueryAliases;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Author;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Comment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\EntityManagerFactory;

it('names the entity a root, a join, a join off a join and an entity join stand for', function (): void {
    $query = EntityManagerFactory::create()->createQueryBuilder()
        ->select('post')
        ->from(Post::class, 'post')
        ->leftJoin('post.author', 'author')
        ->leftJoin('author.posts', 'authorPosts')
        ->leftJoin(Comment::class, 'loose', 'WITH', 'loose.post = post');

    expect(QueryAliases::classOf($query, 'post'))->toBe(Post::class)
        ->and(QueryAliases::classOf($query, 'author'))->toBe(Author::class)
        ->and(QueryAliases::classOf($query, 'authorPosts'))->toBe(Post::class)
        ->and(QueryAliases::classOf($query, 'loose'))->toBe(Comment::class)
        ->and(QueryAliases::classOf($query, 'sy_x0_0'))->toBeNull();
});

it('tells a fetch-joined collection from an unselected one and from a fetch-joined to-one', function (): void {
    $entityManager = EntityManagerFactory::create();

    $fetched = $entityManager->createQueryBuilder()
        ->select('post')
        ->from(Post::class, 'post')
        ->leftJoin('post.comments', 'comments')
        ->addSelect('comments')
        ->leftJoin('post.author', 'author')
        ->addSelect('author');
    $listed = $entityManager->createQueryBuilder()->select('post, comments')->from(Post::class, 'post')->leftJoin('post.comments', 'comments');
    $plain = $entityManager->createQueryBuilder()->select('post')->from(Post::class, 'post')->leftJoin('post.comments', 'comments');

    expect(QueryAliases::isFetchJoinedCollection($fetched, 'comments'))->toBeTrue()
        ->and(QueryAliases::isFetchJoinedCollection($listed, 'comments'))->toBeTrue()
        ->and(QueryAliases::isFetchJoinedCollection($fetched, 'author'))->toBeFalse()
        ->and(QueryAliases::isFetchJoinedCollection($fetched, 'post'))->toBeFalse()
        ->and(QueryAliases::isFetchJoinedCollection($plain, 'comments'))->toBeFalse()
        ->and(QueryAliases::isFetchJoinedCollection($fetched, 'nothing'))->toBeFalse();
});
