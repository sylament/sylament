<?php

declare(strict_types=1);

namespace Sylament\Actions\Contracts;

use Closure;
use Sylament\Actions\Action;
use Sylament\Schemas\Schema;
use Sylament\Schemas\State\Relationship;

interface InteractsWithActions
{
    /**
     * @param array<mixed> $arguments
     * @param array<mixed> $context
     */
    public function mountAction(string $name, array $arguments = [], array $context = []): mixed;

    /**
     * @param array<mixed> $arguments
     */
    public function callMountedAction(array $arguments = []): mixed;

    public function unmountAction(bool|string|null $cancelParentActions = null): void;

    public function getMountedAction(?int $actionNestingIndex = null): ?Action;

    /**
     * @return array<Action>
     */
    public function getMountedActions(): array;

    /**
     * One frame of the per-mount form-state bag.
     *
     * @return array<string, mixed>
     */
    public function getMountedActionData(?int $actionNestingIndex = null): array;

    public function getMountedActionSchema(?int $actionNestingIndex = null, ?Action $mountedAction = null): ?Schema;

    public function mountedActionHasSchema(?Action $mountedAction = null): bool;

    public function mountedActionShouldOpenModal(?Action $mountedAction = null): bool;

    public function getMountedModalId(?int $actionNestingIndex = null): string;

    public function isMountedActionModalOpen(int $actionNestingIndex): bool;

    public function getAction(string $name): ?Action;

    /**
     * The URL an action with no `url()` of its own falls through to — null when
     * the host has no page to send it to.
     */
    public function getDefaultActionUrl(Action $action): ?string;

    /**
     * The URL a successful action redirects to when it carries no
     * `successRedirectUrl()` of its own — null when the host has none.
     */
    public function getDefaultActionSuccessRedirectUrl(Action $action): ?string;

    /**
     * The URL a failed action redirects to when it carries no
     * `failureRedirectUrl()` of its own — null when the host has none.
     */
    public function getDefaultActionFailureRedirectUrl(Action $action): ?string;

    /**
     * The closure that builds a mounted action's schema when the action declares
     * none of its own — a resource page answers with its resource's form; null
     * when the host has nothing to offer.
     */
    public function getDefaultActionSchemaResolver(Action $action): ?Closure;

    /**
     * The record an action falls through to when nothing bound one — a record
     * page answers with the record it shows; null when the host has none. An
     * object or an array record: a default is never a bare key.
     *
     * @return object|array<string, mixed>|null
     */
    public function getDefaultActionRecord(Action $action): object|array|null;

    /**
     * The title a record-bound action interpolates into its modal heading when
     * it carries no recordTitle() of its own — null when the host has none.
     */
    public function getDefaultActionRecordTitle(Action $action): ?string;

    /**
     * The class an action creates into when nothing names one — a resource page
     * answers with its resource's record class; null when the host has none.
     *
     * @return class-string|null
     */
    public function getDefaultActionRecordClass(Action $action): ?string;

    /**
     * The label an action interpolates when it carries no recordLabel() and its
     * class is the host's default class — null when the host has none.
     */
    public function getDefaultActionRecordLabel(Action $action): ?string;

    /**
     * The relationship a creating action attaches its record to when neither the
     * action nor its table names one — a relation manager answers here; null when
     * the host has none.
     */
    public function getDefaultActionRelationship(Action $action): ?Relationship;
}
