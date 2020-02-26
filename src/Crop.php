<?php

namespace Encore\Cropper;

use Encore\Admin\Form\Field\ImageField;
use Encore\Admin\Form\Field\File;
use Encore\Admin\Admin;

class Crop extends File
{
    //use Field\UploadField;
    use ImageField;

    private $ratioW = 100;

    private $ratioH = 100;

    protected $view = 'laravel-admin-cropper::cropper';

    protected static $css = [
        '/vendor/laravel-admin-ext/cropper/cropper.min.css',
    ];

    protected static $js = [
        '/vendor/laravel-admin-ext/cropper/cropper.min.js',
        '/vendor/laravel-admin-ext/cropper/layer/layer.js'
    ];

    protected function preview()
    {
        return $this->objectUrl($this->value);
    }

    private function base64_image_content($base64_image_content, $path)
    {
        //Match the format of the picture
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $new_file = $path . "/" . date('Ymd', time()) . "/";
            if (!file_exists($new_file)) {
                //Check if the folder exists, create it if not, and give the highest permissions
                mkdir($new_file, 0755, true);
            }
            $new_file = $new_file . md5(microtime()) . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                return $new_file;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function prepare($base64)
    {
        //Check if it is base64 encoded
        if (preg_match('/data:image\/.*?;base64/is',$base64)) {
            //Base64 to picture returns absolute path
            $imagePath = $this->base64_image_content($base64,public_path('uploads/base64img'));
            if ($imagePath !== false) {
                //Delete old picture
                @unlink(public_path('uploads/').$this->original);
                //Handling picture addresses
                preg_match('/base64img\/.*/is',$imagePath,$matches);

                $this->callInterventionMethods($imagePath);

                return $matches[0];
            } else {
                return 'lost';
            }
        } else {
            preg_match('/base64img\/.*/is',$base64,$matches);
            return isset($matches[0]) ? $matches[0] : $base64;
        }
    }


    public function cRatio($width,$height)
    {
        if (!empty($width) and is_numeric($width)) {
            $this->attributes['data-w'] = $width;
        } else {
            $this->attributes['data-w'] = $this->ratioW;
        }
        if (!empty($height) and is_numeric($height)) {
            $this->attributes['data-h'] = $height;
        } else {
            $this->attributes['data-h'] = $this->ratioH;
        }
        return $this;
    }

    public function render()
    {
        $this->name = $this->formatName($this->column);

        if (!empty($this->value)) {
            $this->value = filter_var($this->preview());
        }

        $this->script = <<<EOT

//Picture type pre-stored
var cropperMIME = '';

function getMIME(base64)
{
    var preg = new RegExp('data:(.*);base64','i');
    var result = preg.exec(base64);
    return result[1];
}

function cropper(imgSrc,id,w,h)
{
    
    var cropperImg = '<div id="cropping-div"><img id="cropping-img" src="'+imgSrc+'"><\/div>';
    
    layer.open({
        type: 1,
        skin: 'layui-layer-demo',
        area: ['800px', '600px'],
        closeBtn: 2, 
        anim: 2,
        resize: false,
        shadeClose: false, 
        title: 'Picture cropper',
        content: cropperImg,
        btn: ['Tailoring','Original image','Clear'],
        btn1: function(){
            var cas = cropper.getCroppedCanvas({
                width: w,
                height: h
            });
            //Clipping data conversion base64
            var base64url = cas.toDataURL(cropperMIME);
            //Replace preview
            $('#'+id+'-img').attr('src',base64url);
            //Replace submission data
            $('#'+id+'-input').val(base64url);
            //Destroy Clipper Example
            cropper.destroy();
            layer.closeAll('page');
        },
        btn2:function(){
            // Close the box by default
            // Destroy the clipper instance
            cropper.destroy();
        },
        btn3:function(){
            // Empty forms and options
            // Destroy the clipper instance
            cropper.destroy();
            layer.closeAll('page');
            //Clear preview
            $('#'+id+'-img').removeAttr('src');
            $('#'+id+'-input').val('');
            $('#'+id+'-file').val('');
        }
    });

    var image = document.getElementById('cropping-img');
    var cropper = new Cropper(image, {
        aspectRatio: w / h,
        viewMode: 2,
    });
}

$('.cropper-btn').click(function(){
    var id = $(this).attr('data-id');
    $('#'+id+'-file').click();
});

$('.cropper-file').change(function(){
    var id = $(this).attr('data-id');
    var w = $(this).attr('data-w');
    var h = $(this).attr('data-h');
    
    // Get the files file array of input file;
    // Only one can be selected here, but the storage form is still an array, so the first element is [0];
    var file = $(this)[0].files[0];
    //Create an object to read this file
    var reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = function(e){
        $('#'+id+'-img').attr('src',e.target.result);
        cropperMIME = getMIME(e.target.result);
        cropper(e.target.result,id,w,h);
        $('#'+id+'-input').val(e.target.result);
    };
});

$('.cropper-img').click(function(){
    var id = $(this).attr('data-id');
    var w = $(this).attr('data-w');
    var h = $(this).attr('data-h');
    cropper($(this).attr('src'),id,w,h);
});

EOT;

        if (!$this->display) {
            return '';
        }

        Admin::script($this->script);

        return view($this->getView(), $this->variables());
    }

}
