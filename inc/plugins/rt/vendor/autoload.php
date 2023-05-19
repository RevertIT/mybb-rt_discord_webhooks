<?php
/**
 * RT Discord Webhooks
 *
 * A simple integration of discord webhooks with multiple insertions
 *
 * @package rt_discord_webhooks
 * @author  RevertIT <https://github.com/revertit>
 * @license http://opensource.org/licenses/mit-license.php MIT license
 */

declare(strict_types=1);

namespace rt\Autoload;

function psr4_autoloader(string $pluginDirectory, string $filesDirectory, string $namespace, array $functions = []): void
{
    spl_autoload_register(function ($class) use ($pluginDirectory, $filesDirectory, $namespace)
    {
        // Define the base directory for your classes
        $directory = "inc/plugins/{$pluginDirectory}/{$filesDirectory}";
        $baseDir = MYBB_ROOT . $directory;

        // Check if the class belongs to the base namespace
        if (strpos($class, $namespace) === 0)
        {
            // Convert namespace and class to file path
            $relativeClass = substr($class, strlen($pluginDirectory));
            $filePath = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            // Load the class file if it exists
            if (file_exists($filePath))
            {
                require $filePath;
            }
        }
    });

    if (!empty($functions))
    {
        foreach ($functions as $f)
        {
            $directory = "inc/plugins/{$pluginDirectory}/{$filesDirectory}";
            $baseDir = MYBB_ROOT . $directory;

            // Convert namespace and function to file path
            $relativeFunction = substr($f, strlen($pluginDirectory));
            $filePath = $baseDir . str_replace('\\', '/', $relativeFunction);

            // Load the function file if it exists
            if (file_exists($filePath))
            {
                require $filePath;
            }
        }
    }
}
