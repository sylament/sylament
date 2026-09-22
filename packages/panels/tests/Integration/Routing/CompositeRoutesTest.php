<?php

declare(strict_types=1);

use Symfony\Component\Routing\RouterInterface;

/**
 * The composite keys `record-key.md` works through, written as literals. Its
 * ninth worked key is a single string identifier, which the codec passes through
 * verbatim, `/` included — the one worked key no URL path segment can carry.
 *
 * @return list<string>
 */
function compositeRouteWorkedKeys(): array
{
    return [
        'plain~about',
        'www=2Eexample=2Ecom~=2Fblog=2F2026=2Fbundles=20all=20the=20way=20down',
        'example=2Ecom~=2Fclanky=2F=C5=BDofia=7EN=C3=A1vratov=C3=A1',
        'a=3Db~c=7Cd',
        'x=20y~p=252Fq',
        'main-hall~autumn=2F2026',
        'footer~2',
        '1~2',
    ];
}

it('generates and matches back every worked key, through the compiled router', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);

    foreach (compositeRouteWorkedKeys() as $key) {
        $url = $router->generate('sylament.key.resources.imprints.edit', ['record' => $key]);

        expect($url)->toBe('/key/imprints/' . $key . '/edit')
            ->and($url)->not->toContain('%')
            ->and($router->match($url)['record'])->toBe($key);
    }
});

it('carries a composite parent key beside a composite record key on one path', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);

    $nested = $router->generate('sylament.key.resources.catalogues.stamps.edit', [
        'catalogue' => '1~2',
        'record' => 'main-hall~autumn=2F2026',
    ]);
    $deep = $router->generate('sylament.key.resources.catalogues.stamps.marks.edit', [
        'catalogue' => 'plain~about',
        'stamp' => 'a=3Db~c=7Cd',
        'record' => 'footer~2',
    ]);

    expect($nested)->toBe('/key/catalogues/1~2/stamps/main-hall~autumn=2F2026/edit')
        ->and($nested)->not->toContain('%')
        ->and($router->match($nested))->toMatchArray(['catalogue' => '1~2', 'record' => 'main-hall~autumn=2F2026'])
        ->and($deep)->toBe('/key/catalogues/plain~about/stamps/a=3Db~c=7Cd/stamp-marks/footer~2/edit')
        ->and($deep)->not->toContain('%')
        ->and($router->match($deep))->toMatchArray(['catalogue' => 'plain~about', 'stamp' => 'a=3Db~c=7Cd', 'record' => 'footer~2']);
});

it('round-trips a key through every path shape a separator closes', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);

    $shapes = [
        'sylament.key.resources.separator-probes.dash' => '/key/separator-probes/%s-preview',
        'sylament.key.resources.separator-probes.tilde' => '/key/separator-probes/%s~x',
        'sylament.key.resources.separator-probes.underscore' => '/key/separator-probes/%s_x',
        'sylament.key.resources.separator-probes.equals' => '/key/separator-probes/%s=x',
        'sylament.key.resources.separator-probes.dot' => '/key/separator-probes/%s.pdf',
    ];

    foreach ($shapes as $name => $shape) {
        foreach (['main-hall~autumn=2F2026', '1~EN-GB', 'under_score~x', '0f1e2d3c-4b5a-6978-8796-a5b4c3d2e1f0'] as $key) {
            $url = $router->generate($name, ['record' => $key]);

            expect($url)->toBe(sprintf($shape, $key))
                ->and($url)->not->toContain('%')
                ->and($router->match($url)['record'])->toBe($key);
        }
    }
});

it('leaves every parent placeholder at the compiler default, because a slug always follows it', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);
    $parented = [];

    foreach ($router->getRouteCollection() as $name => $route) {
        if (! str_starts_with($name, 'sylament.') || preg_match('/\{(?!record\})[^}]+\}/', $route->getPath()) !== 1) {
            continue;
        }

        $parented[$name] = $route->getRequirements();
    }

    // The loader composes a parent placeholder as `/{param}` and then appends
    // `/` plus the child slug, so the character after it is always a slash and
    // the compiler's own `[^/]++` covers every character the key grammar uses.
    // M7 · 3's greedy requirement is stamped on `{record}` alone, and this is
    // what keeps that asymmetry harmless.
    expect($parented)->not->toBeEmpty();

    foreach ($parented as $name => $requirements) {
        expect($requirements)->toBe([], $name)
            ->and($router->getRouteCollection()->get($name)?->getPath())->toContain('}/');
    }

    $url = $router->generate('sylament.key.resources.binderies.bindings.edit', [
        'imprint' => 'a=7Eb~c=3Dd=20=C5=BE',
        'record' => '1',
    ]);

    expect($url)->toBe('/key/binderies/a=7Eb~c=3Dd=20=C5=BE/imprint-bindings/1/edit')
        ->and($url)->not->toContain('%')
        ->and($router->match($url))->toMatchArray(['imprint' => 'a=7Eb~c=3Dd=20=C5=BE', 'record' => '1']);
});

it('narrows the mounted paths a separator follows and no others', function (): void {
    $router = $this::getContainer()->get(RouterInterface::class);
    $narrowed = [];

    foreach ($router->getRouteCollection() as $name => $route) {
        if (str_starts_with($name, 'sylament.') && $route->getRequirements() !== []) {
            $narrowed[$name] = $route->getRequirements();
        }
    }

    expect($narrowed)->toBe([
        'sylament.key.resources.separator-probes.dash' => ['record' => '[^/]+'],
        'sylament.key.resources.separator-probes.tilde' => ['record' => '[^/]+'],
        'sylament.key.resources.separator-probes.underscore' => ['record' => '[^/]+'],
        'sylament.key.resources.separator-probes.equals' => ['record' => '[^/]+'],
        'sylament.key.resources.separator-probes.dot' => ['record' => '[^/]+'],
    ])
        ->and($router->getRouteCollection()->get('sylament.key.resources.imprints.edit')?->compile()->getRegex())
        // The `u` the framework's own `utf8` option adds is what tells this
        // baseline apart from the same path compiled bare.
        ->toBe('{^/key/imprints/(?P<record>[^/]++)/edit$}sDu');
});
