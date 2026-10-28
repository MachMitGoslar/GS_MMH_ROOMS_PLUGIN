<?php

use PHPUnit\Framework\TestCase;

final class PluginRegistrationTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    public function testEveryFileRegisteredByTheIndexExists(): void
    {
        $index = (string) file_get_contents(self::root() . '/index.php');
        preg_match_all("/__DIR__ \\. '(\\/[^']+)'/", $index, $matches);

        $this->assertGreaterThan(15, count($matches[1]));

        foreach ($matches[1] as $path) {
            $this->assertFileExists(self::root() . $path, $path);
        }
    }

    public function testNoRoomFileIsLeftOutOfTheIndex(): void
    {
        $index = (string) file_get_contents(self::root() . '/index.php');

        foreach (['blueprints/pages', 'templates', 'snippets', 'snippets/emails'] as $dir) {
            foreach (glob(self::root() . '/' . $dir . '/*.{php,yml}', GLOB_BRACE) as $file) {
                $this->assertStringContainsString(
                    "'/" . $dir . '/' . basename($file) . "'",
                    $index,
                    basename($file) . ' is not registered',
                );
            }
        }
    }

    public function testBlueprintsAreValidYamlWithATitle(): void
    {
        foreach (glob(self::root() . '/blueprints/pages/*.yml') as $file) {
            $data = Kirby\Data\Yaml::decode((string) file_get_contents($file));

            $this->assertIsArray($data, basename($file));
            $this->assertArrayHasKey('title', $data, basename($file));
        }
    }

    public function testApiRoutesAreThePublicBookingAndAvailabilityEndpoints(): void
    {
        $routes = require self::root() . '/routes/api.php';

        $this->assertSame(
            [
                'POST booking/submit',
                'GET booking/submit',
                'GET rooms/availability.json',
                'GET rooms/(:any)/availability.json',
            ],
            array_map(fn (array $route) => $route['method'] . ' ' . $route['pattern'], $routes),
        );

        foreach ($routes as $route) {
            $this->assertFalse($route['auth'], $route['pattern']);
            $this->assertIsCallable($route['action']);
        }
    }

    public function testTheBookingEndpointAnswersGetWithAHint(): void
    {
        $get = require self::root() . '/routes/api.php';

        $this->assertSame('ok', $get[1]['action']()['status']);
    }

    public function testTheStatusHookIgnoresPagesThatAreNotBookingRequests(): void
    {
        $hook = require self::root() . '/hooks/booking-status.php';
        $page = new Kirby\Cms\Page(['slug' => 'irgendwas']);

        $this->assertNull($hook($page, $page));
    }
}
