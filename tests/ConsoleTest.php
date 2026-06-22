<?php

namespace AnourValar\EloquentRequest\Tests;

class ConsoleTest extends AbstractSuite
{
    /**
     * @return void
     */
    public function test_make_controller_buildby_command(): void
    {
        $path = app_path('Http/Controllers/FooBuildController.php');
        @unlink($path);

        $this->artisan('make:controller-buildby', ['name' => 'FooBuildController'])->assertExitCode(0);

        $this->assertFileExists($path);

        $content = (string) file_get_contents($path);
        // the custom stub (controller.plain.stub) was used
        $this->assertStringContainsString('class FooBuildController', $content);
        $this->assertStringContainsString('use \AnourValar\EloquentRequest\ControllerTrait;', $content);
        $this->assertStringContainsString("'filter' =>", $content);
        $this->assertStringContainsString('OPTION_PAGE_MAX', $content);

        @unlink($path);
    }
}
