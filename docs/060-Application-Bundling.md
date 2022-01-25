# Application Bundling

1. [Requirements](#requirements)
2. [Create the executable](#create-the-executable)
3. [Exclude Paths](#exclude-paths)
    1. [Customize Excludes](#customize-excludes)
4. [Skip Names](#skip-names)
    1. [Default Skip Names](#default-skip-names)
    1. [Customize Skip Names](#customize-skip-names)

## Requirements

Please note that you have to enable the phar-extension of PHP and also need to set this in your `php.ini`:

```ini
phar.readonly = 0
```

If you are using the suhosin extension, please also whitelist the phar extension:

```ini
suhosin.executor.include.whitelist = "phar"
```

## Create the executable

```bash
php bin/console core:compile "my-application.phar" --executable
```

## Exclude Paths

By default, only the `vendor/bin` path is excluded if you do not specify the `--exclude` and `--exclude-file` option.

**NOTE:** As soon as you specify any of these options, only your excludes will be used and the defaults won't be
applied.

### Customize Excludes

If you want to exclude certain paths, just provide them as command options:

```bash
php bin/console core:compile --exclude="./some/rel/path" --exclude="/also/abs/paths/work"
```

If you have many paths or want to include excluded paths in your project, you can also use an exclude-file which
contains one path per line.

Content of an example exclude file (`./my-exclude-paths.txt`):

```text
./some/rel/path
/also/abs/paths/work
```

```bash
php bin/console core:compile --exclude-file="./my-exclude-paths.txt"
```

**HINT:** Please note that you can combine the `--exclude` and `--exclude-file` options.

## Skip Names

Beside excluding explicit paths, you can also specify file and folder names that should be skipped regardless of their
location. This comes in handy if you want to exclude certain setting files or `.git`-repository directories for example.
Especially Git-repositories in the `vendor`-folder can get very big very quickly.

### Default Skip Names

By default, only the following names will be skipped if you do not specify the `--skip` and `--skip-file` option:

* `.idea`
* `.git`

**NOTE:** As soon as you specify any of these options, only your skip names will be used and the defaults won't be
applied.

### Customize Skip Names

Just like with exclude paths, you just need to provide them as command options:

```bash
php bin/console core:compile --skip="some_file.php" --skip="my-folder"
```

If you have many skip names or want to include them in your project you can also use a skip-file which contains one name
per line.

Content of an example exclude file (`./my-skip-names.txt`):

```text
some_file.php
my-folder
```

```bash
php bin/console core:compile --skip-file="./my-skip-names.txt"
```

**HINT:** Please note that you can combine the `--skip` and `--skip-file` options.
