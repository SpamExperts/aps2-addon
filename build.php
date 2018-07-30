<?php

$shortopts  = "";

$longopts  = array(
    "help",
    "src:",
    "dir:",
    "package::",
    "dev",
    "test",
);

$options = getopt($shortopts, $longopts);

if (isset($options['help'])) {
    $information = array(
        "--src     => Source directory to use for the build (default is 'src'); Usage: --src my_source_dir",
        "--dir     => Use a specific output directory name (default is 'spamexperts') Usage: --dir my_output_dir",
        "--package => Build an APS package; you can optionally specify a name (default is 'SpamExperts-2.0-X.app.zip') Usage: --package; --package='my_package.app.zip'",
        "--dev     => Development build (keeps some files; ignores --package) Usage: --dev",
        "--test    => Executes standalone unit tests (not including POA integration tests). Usage: --test",
    );
    exit(implode("\n", $information) . "\n");
}

/** Use correct version id @see https://trac.spamexperts.com/ticket/28164 */
$appMeta = simplexml_load_file(__DIR__ . '/src/APP-META.xml');
$versionId = "{$appMeta->version}-{$appMeta->release}";
$appFile = __DIR__ . '/src/scripts/App.php';
$appFileContents = file_get_contents($appFile);
file_put_contents($appFile, strtr($appFileContents, [ '{{{ VERSION }}}' => $versionId ]));

$app = !empty($options['dir']) ? $options['dir'] : "spamexperts";

if (file_exists($app)) {
    exec("rm -rf $app");
}

exec("cp -r src/ $app/");

if (!file_exists("./composer.phar")) {
    exec("wget https://raw.githubusercontent.com/composer/getcomposer.org/f084c2e65e0bf3f3eac0f73107450afff5c2d666/web/installer -O - -q | php -- --quiet");
}

if (isset($options['test'])) {
    exec("php composer.phar install -d " . __DIR__ . "/src/scripts");

    $output = '';
    $return_var = 0;
    exec(  __DIR__ . "/src/scripts/vendor/bin/phpunit " . __DIR__ . "/tests/APIClientTest.php", $output , $return_var);

    echo join("\n", $output) . "\n";

    exit($return_var);
}

exec("php composer.phar install -d $app/scripts");

if (!isset($options['dev'])) {
    exec("rm -rf \"$app/scripts/composer.json\" \"$app/scripts/composer.lock\" ");

    if (isset($options['package'])) {
        $outputName = $options['package'] ? "-o {$options['package']}" : '';

        exec("aps build $outputName $app", $result);

        echo (implode("\n", $result) . "\n");
    }
};

echo `git checkout -- $appFile`;
