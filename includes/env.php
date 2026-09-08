<?php
/**
 * Minimal .env loader. No external dependency needed for this part.
 * Reads KEY=VALUE lines and exposes them via getenv()/$_ENV.
 */
function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        die(
            "Missing .env file at: $path\n" .
            "Copy .env.example to .env and fill in your mailer credentials."
        );
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || strpos($line, '#') === 0) {
            continue; // skip blank lines and comments
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name  = trim($name);
        $value = trim($value);

        // Strip surrounding quotes if present, e.g. NAME="Some Value"
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (!array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}
