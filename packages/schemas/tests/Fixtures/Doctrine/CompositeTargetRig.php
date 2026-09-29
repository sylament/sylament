<?php

declare(strict_types=1);

namespace Sylament\Schemas\Tests\Fixtures\Doctrine;

use Doctrine\ORM\EntityManager;
use LogicException;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Enrolment;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Marker;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Post;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostSeal;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\PostTranslation;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Redirect;
use Sylament\Schemas\Tests\Fixtures\Doctrine\Entity\Tag;
use Sylament\Support\Tests\Doctrine\RecordingMiddleware;

/**
 * The in-memory rig every composite-target case reads: three posts, two tags,
 * and one record of each identifier flavour as the target of a many-to-many the
 * first post holds — owning side and, for the two widest flavours, inverse side
 * too. Ids are deterministic (the posts are 1, 2, 3 and the tags 1, 2), so every
 * key below is a literal rather than something a test asks the codec for.
 */
final class CompositeTargetRig
{
    public const string REDIRECT_ONE = 'example=2Ecom~=2Fa';

    public const string REDIRECT_TWO = 'example=2Ecom~=2Fb';

    public const string REDIRECT_THREE = 'other=2Ecom~=2Fc';

    public const string TRANSLATION_EN = '1~en';

    public const string TRANSLATION_DE = '1~de';

    public const string TRANSLATION_FR = '2~fr';

    public const string SEAL_ONE = '1';

    public const string SEAL_TWO = '2';

    public const string ENROLMENT_ONE = '1~1';

    public const string ENROLMENT_TWO = '1~2';

    public const string ENROLMENT_THREE = '2~1';

    public static function create(?RecordingMiddleware $recorder = null): EntityManager
    {
        $entityManager = EntityManagerFactory::create($recorder);

        [$first, $second] = self::posts($entityManager);
        [$php, $symfony] = self::tags($entityManager);

        $one = self::redirect($entityManager, 'example.com', '/a');
        $two = self::redirect($entityManager, 'example.com', '/b');
        $three = self::redirect($entityManager, 'other.com', '/c');

        $english = self::translation($entityManager, $first, 'en');
        $german = self::translation($entityManager, $first, 'de');
        self::translation($entityManager, $second, 'fr');

        $firstSeal = self::seal($entityManager, $first);
        $secondSeal = self::seal($entityManager, $second);

        $firstEnrolment = self::enrolment($entityManager, $first, $php);
        self::enrolment($entityManager, $first, $symfony);
        self::enrolment($entityManager, $second, $php);

        // The owning side of each flavour hangs off the first post; the second
        // post holds one of each too, so a record related to another owner is
        // never mistaken for an unrelated one.
        $first->addRedirect($one);
        $first->addLinkedTranslation($english);
        $first->addSeal($firstSeal);
        $first->addEnrolment($firstEnrolment);

        $second->addRedirect($three);

        // The inverse sides: the target owns the mapping, so the write goes there.
        $two->addPost($first);
        $german->addPost($first);

        // The single derived identity is the flavour Doctrine binds, so it owns
        // collections of its own — two of them, scoped disjointly.
        $firstSeal->addTopic($php);
        $secondSeal->addTopic($symfony);

        self::marker($entityManager, 'first seal', $firstSeal);
        self::marker($entityManager, 'second seal', $secondSeal);
        self::marker($entityManager, 'loose', null);

        $entityManager->flush();
        $entityManager->clear();

        return $entityManager;
    }

    public static function post(EntityManager $entityManager, int $id): Post
    {
        $post = $entityManager->find(Post::class, $id);

        return $post instanceof Post ? $post : throw new LogicException(sprintf('The rig seeds no post [%d].', $id));
    }

    public static function tag(EntityManager $entityManager, int $id): Tag
    {
        $tag = $entityManager->find(Tag::class, $id);

        return $tag instanceof Tag ? $tag : throw new LogicException(sprintf('The rig seeds no tag [%d].', $id));
    }

    public static function findRedirect(EntityManager $entityManager, string $host, string $path): Redirect
    {
        $redirect = $entityManager->find(Redirect::class, ['host' => $host, 'path' => $path]);

        return $redirect instanceof Redirect ? $redirect : throw new LogicException(sprintf('The rig seeds no redirect [%s%s].', $host, $path));
    }

    public static function findTranslation(EntityManager $entityManager, int $postId, string $locale): PostTranslation
    {
        $translation = $entityManager->find(PostTranslation::class, ['post' => $postId, 'locale' => $locale]);

        return $translation instanceof PostTranslation ? $translation : throw new LogicException(sprintf('The rig seeds no translation [%d~%s].', $postId, $locale));
    }

    public static function findSeal(EntityManager $entityManager, int $postId): PostSeal
    {
        $seal = $entityManager->find(PostSeal::class, ['post' => $postId]);

        return $seal instanceof PostSeal ? $seal : throw new LogicException(sprintf('The rig seeds no seal [%d].', $postId));
    }

    public static function findEnrolment(EntityManager $entityManager, int $postId, int $tagId): Enrolment
    {
        $enrolment = $entityManager->find(Enrolment::class, ['post' => $postId, 'tag' => $tagId]);

        return $enrolment instanceof Enrolment ? $enrolment : throw new LogicException(sprintf('The rig seeds no enrolment [%d~%d].', $postId, $tagId));
    }

    /**
     * @return list<Post>
     */
    private static function posts(EntityManager $entityManager): array
    {
        $posts = [];

        foreach (['Alpha', 'Beta', 'Gamma'] as $title) {
            $post = new Post();
            $post->setTitle($title);

            $entityManager->persist($post);
            $posts[] = $post;
        }

        $entityManager->flush();

        return $posts;
    }

    /**
     * @return list<Tag>
     */
    private static function tags(EntityManager $entityManager): array
    {
        $tags = [];

        foreach (['php', 'symfony'] as $name) {
            $tag = new Tag();
            $tag->setName($name);

            $entityManager->persist($tag);
            $tags[] = $tag;
        }

        $entityManager->flush();

        return $tags;
    }

    private static function redirect(EntityManager $entityManager, string $host, string $path): Redirect
    {
        $redirect = new Redirect();
        $redirect->setHost($host);
        $redirect->setPath($path);
        $redirect->setLabel($host . $path);

        $entityManager->persist($redirect);

        return $redirect;
    }

    private static function translation(EntityManager $entityManager, Post $post, string $locale): PostTranslation
    {
        $translation = new PostTranslation();
        $translation->setPost($post);
        $translation->setLocale($locale);
        $translation->setTitle($locale);

        $entityManager->persist($translation);

        return $translation;
    }

    private static function seal(EntityManager $entityManager, Post $post): PostSeal
    {
        $seal = new PostSeal();
        $seal->setPost($post);
        $seal->setNote('sealed');

        $entityManager->persist($seal);

        return $seal;
    }

    private static function enrolment(EntityManager $entityManager, Post $post, Tag $tag): Enrolment
    {
        $enrolment = new Enrolment();
        $enrolment->setPost($post);
        $enrolment->setTag($tag);
        $enrolment->setNote($post->getTitle() . ' ' . $tag->getName());

        $entityManager->persist($enrolment);

        return $enrolment;
    }

    private static function marker(EntityManager $entityManager, string $label, ?PostSeal $seal): Marker
    {
        $marker = new Marker();
        $marker->setLabel($label);
        $marker->setSeal($seal);

        $entityManager->persist($marker);

        return $marker;
    }
}
