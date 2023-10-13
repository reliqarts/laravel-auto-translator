<p align="center">
<img src="./docs/images/logo.svg" alt="Laravel Auto-Translator" width="245"/>
<p>
<p align="center">
Laravel Automatic Translator, for Laravel 10+
</p>
<p align="center">
<a href="https://github.com/reliqarts/laravel-auto-translator/actions/workflows/test.yml"><img src="https://github.com/reliqarts/laravel-auto-translator/actions/workflows/test.yml/badge.svg" alt="test" /></a>
<a href="https://scrutinizer-ci.com/g/reliqarts/laravel-auto-translator/"><img src="https://img.shields.io/scrutinizer/g/reliqarts/laravel-auto-translator.svg" alt="Scrutinizer" /></a>
<a href="https://codecov.io/gh/reliqarts/laravel-auto-translator"><img src="https://img.shields.io/codecov/c/github/reliqarts/laravel-auto-translator.svg" alt="Codecov" /></a>
<a href="https://packagist.org/packages/reliqarts/laravel-auto-translator"><img src="https://poser.pugx.org/reliqarts/laravel-auto-translator/version" alt="Latest Stable Version" /></a>
<a href="https://packagist.org/packages/reliqarts/laravel-auto-translator"><img src="https://poser.pugx.org/reliqarts/laravel-auto-translator/license" alt="License" /></a>
</p>

This package will scan your application, locate all [translation string keys](https://laravel.com/docs/10.x/localization#using-translation-strings-as-keys) throughout and generate translations 
based on your configuration.

## Features

- Simple, easy to schedule artisan command to generate all translations
- Automatic language file generation ([json](https://laravel.com/docs/10.x/localization#using-translation-strings-as-keys))
- Easy to configure, with support for custom translator implementation

## Installation

Install via composer:

```bash
composer require reliqarts/laravel-auto-translator
```

## Configuration

You may publish the configuration file and customize as you wish. Each built-in translator implementation has its own
set of config options. All configuration options are explained in the
[configuration file](https://github.com/reliqarts/laravel-auto-translator/blob/main/config/config.php).

```bash
php artisan vendor:publish --provider="\ReliqArts\AutoTranslator\ServiceProvider"
```

### Translator

The `auto_translate_via` key allows you to specify which translator should be used for automatic translations.
By default, this is set to Google's simple translator. Any service implementing the
`\ReliqArts\AutoTranslator\Contract\Translator` interface may be used here.

#### Built-in Implementations

| Implementation                                                         | Paid | Documentation                          | Available languages |
|------------------------------------------------------------------------|------|----------------------------------------|---------------------|
| \ReliqArts\AutoTranslator\Service\Translator\SimpleGoogleApiTranslator | No   | N/A                                    | 100+                |
| \ReliqArts\AutoTranslator\Service\Translator\DeepLTranslator           | Yes  | [Docs](https://www.deepl.com/docs-api) | 30+                 |


> [!IMPORTANT]
> For production use cases please consider using a paid translator service.
> Do not depend on the free Google HTTP implementation shipped with this package as it may
> break at any time. See original disclaimer on [google-translate-php](https://github.com/Stichoza/google-translate-php).


## Usage

### Artisan Command

The package provides an easy-to-use command which you may run on-demand or on a schedule.

```bash
php artisan auto-translator:translate
```

You may pass one or more language codes (comma separated) in order to specify which languages should be translated to.
e.g.

```bash
php artisan auto-translator:translate es,de
```

An optional `replace-existing` flag (`r`) allows you to override existing translations.
e.g.

```bash
php artisan auto-translator:translate es,de -r
```

### Language Switcher Endpoint and Middleware

The package provides a route/middleware combination which allows users to switch the language of your application.

#### Usage:

1. Add the `\ReliqArts\AutoTranslator\Http\Middleware\LanguageDetector` middleware to the web route group in
   your `App\Http\Kernel` class.
2. Switch the language by sending a post request to the `switch-language` route. _(name and endpoint changeable in
   config file)_
   You may use a select box, set of flags, etc. to make calls to this endpoint. UI choice is totally yours :smiley:

## Credits & Inspiration

This package was inspired by [laravel-auto-translate](https://github.com/ben182/laravel-auto-translate) and is made
possible by the following:

- [laravel-translatable-string-exporter](https://github.com/kkomelin/laravel-translatable-string-exporter)
  by [kkomelin](https://github.com/kkomelin)
- [google-translate-php](https://github.com/Stichoza/google-translate-php) by [stichoza](https://github.com/Stichoza)
- [contributors](https://github.com/reliqarts/laravel-auto-translator/graphs/contributors)

---
All done! :beers:
