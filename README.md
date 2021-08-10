[![Code Climate](https://codeclimate.com/github/SpamExperts/aps2-addon/badges/gpa.svg)](https://codeclimate.com/github/SpamExperts/aps2-addon) [![Issue Count](https://codeclimate.com/github/SpamExperts/aps2-addon/badges/issue_count.svg)](https://codeclimate.com/github/SpamExperts/aps2-addon)

# SpamExperts

SpamExperts application for CloudBlue Commerce (20.4 and 20.5), built using the APS framework.

## Build (OSX/Linux)

After cloning a local copy, simply run:

```
php build.php
```

And you'll get a build ready for packaging in 'spamexperts'. To get the ready-to-deploy package as well, simply add '--package':

```
php build.php --package
```

Note: If you want to use --package, you will need to install the [APS PHP Runtime Library](https://doc.apsstandard.org/2.1/tools/php-lib/) and the [APS Command Line Tools](https://doc.apsstandard.org/2.1/tools/cli-tools/).

## Build Options

Here is a more detailed explanation of the options you can use for the build:
```
--src     => Source directory to use for the build (default is 'src'); Usage: --src my_source_dir
--dir     => Use a specific output directory name (default is 'spamexperts') Usage: --dir my_output_dir
--package => Build an APS package; you can optionally specify a name (default is 'SpamExperts-2.0-X.app.zip') Usage: --package; --package='my_package.app.zip'
--dev     => Development build (keeps some files; ignores --package) Usage: --dev
```

## Stable Release

You can find the latest stable release in the [App Catalog](https://dev.apsstandard.org/apps/).
