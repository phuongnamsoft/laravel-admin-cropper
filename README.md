cropper extension for laravel-admin
======

This is a `laravel-admin` extension to integrate` cropper` into the form of `laravel-admin`

## Installation

```bash
composer require laravel-admin-ext/cropper
```

Then...

```bash
php artisan vendor:publish --tag=laravel-admin-cropper
```

## Configuration

In the `extensions` of the` config/admin.php` file, add some configuration belonging to this extension:

```php

    'extensions' => [

        'cropper' => [
        
			// If you want to turn off this extension, set to false
            'enable' => true,
        ]
    ]

```


## Usage:

Use it in form：
```php
$form->cropper('content','label');
```

The default mode is free crop mode, if you need to force crop size, please use (note that this size is the final picture size not "scale")
```php
$form->cropper('content','label')->cRatio($width,$height);
```
## PS (feature read ahead)
1. The picture is not pre-uploaded, but the input is input after the front end is converted to base64, and the server returns the image to save it.

2. The picture format is saved by default. That is, if the original image is a png image with a transparent background, it will still be a png image with a transparent background after saving, and it will not be lost (front-end logo artifact)

3. The extension can be called multiple times. Can be transferred multiple times in the same form without interfering with each other.

4. Extend the ImageField and File classes of laravel-admin.
So you don't have to worry about modifying and deleting pictures. They are all automatic.
Of course, since the ImageField class is inherited, you can also use the various (crop, fit, insert) methods of "intervention/image"
(Provided that you have ```composer require intervention/image```)

License
------------
Licensed under [The MIT License (MIT)](LICENSE).