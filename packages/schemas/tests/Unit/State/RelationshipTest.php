<?php

declare(strict_types=1);

use Sylament\Schemas\State\Relationship;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;

it('holds the owner record and the property the association is mapped under', function (): void {
    $post = new Post();

    $relationship = new Relationship($post, 'tags');

    expect($relationship->owner)->toBe($post)
        ->and($relationship->name)->toBe('tags');
});
