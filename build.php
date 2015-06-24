<?php

$shortopts  = "";

$longopts  = array(
    "help",
    "src:",
    "dir:",
    "package::",
    "dev",
);

$options = getopt($shortopts, $longopts);

if (isset($options['help'])) {
    $information = array(
        "--src     => Source directory to use for the build (default is 'src'); Usage: --src my_source_dir",
        "--dir     => Use a specific output directory name (default is 'spamexperts') Usage: --dir my_output_dir",
        "--package => Build an APS package; you can optionally specify a name (default is 'SpamExperts-2.0-X.app.zip') Usage: --package; --package='my_package.app.zip'",
        "--dev     => Development build (keeps some files; ignores --package) Usage: --dev",
    );
    exit(implode("\n", $information) . "\n");
}

$app = !empty($options['dir']) ? $options['dir'] : "spamexperts";

if (file_exists($app)) {
    exec("rm -rf $app");
}

exec("cp -r src/ $app/");

if (!file_exists("composer.phar")) {
    exec("wget getcomposer.org/composer.phar");
}

exec("php composer.phar install -d $app/scripts");

if (!isset($options['dev'])) {
    exec("rm -rf $app/scripts/composer.json; rm -rf $app/scripts/composer.lock;");

    if (isset($options['package'])) {
        $outputName = $options['package'] ? "-o {$options['package']}" : '';

        exec("aps build $outputName $app", $result);

        echo (implode("\n", $result) . "\n");
    }
};
