Step 1: Setting up the bundle
=============================
### A) Download RobocopBundle

**Note:**

> This bundle uses [php-mime-mail-parser](https://github.com/message/php-mime-mail-parser) which is 
> a wrapper for the [PHP PECL extension mailparse](http://pecl.php.net/package/mailparse).
>
> Email attachments are compared for how different they are from each other by [ImageMagick](http://www.imagemagick.org).
>
> Images that are different enough get made into a video with [avconv](http://libav.org)


Ultimately, the RobocopBundle files should be downloaded to the
`vendor/theapi/robocopbundle/Theapi/RobocopBundle` directory.

**Using composer**

Simply run assuming you have installed composer.phar or composer binary:

``` bash
$ composer require theapi/robocopbundle
```

### B) Enable the bundle

Enable the bundle in the kernel:

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Theapi\RobocopBundle\TheapiRobocopBundle(),
    );
}
```
