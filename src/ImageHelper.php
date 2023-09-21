<?php

namespace halilBelkir\WebConvert;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use halilBelkir\WebConvert\Browser;

class ImageHelper
{
    private static $disk        = Config::get('img-webp-convert.disk');
    private static $browserList = ['Chrome' => 8, 'Mozilla' => 64, 'Safari' => '13.2', 'Opera' => '10.2', 'Edge' => 17, 'Android' => 3];

    public static function getImage($image, $width = false, $height = false,$name=null,$status=null,$resize = false,$extensionType = false):string
    {
        if ($status == 1)
        {
            $ch = curl_init($image); //pass your pdf here

            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
            curl_exec($ch);
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if( $retcode !=200 ) return false;
        }
        else
        {
            if (!file_exists($image)) return false;
        }

        $imageInfo = pathinfo($image);

        //Uygun uzantıyı bul
        $extension = self::suitableExtension($imageInfo,$extensionType);

        //Yeni isim veriliyor
        $fileName  = self::newFileName($name, $width, $height, $extension, $resize);

        //Img bilgisi kontrol ediliyor
        if (!self::checkImage($fileName))
        {
            //Img düzenle
            self::resizeImg($image, $width, $height, $extension, $fileName, $resize);
        }

        return asset('upload/cache/'.$fileName);
    }

    public static function createTag($image,$param=[],$attr=[],$type='',$name = null,$status=null)
    {

        try {
            //sting data için img tag dan değer bulunuyor
            if(isset($param['string']) && $param['string'] == true)
            {
                $image = self::getFirstImage($image);
            }
            else
            {
                if ($status == 1)
                {
                    $ch = curl_init($image); //pass your pdf here

                    curl_setopt($ch, CURLOPT_NOBODY, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
                    curl_exec($ch);
                    $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if( $retcode !=200 ) return false;
                }
                else
                {
                    if (!file_exists($image)) $image = config('img-webp-convert.no-image');
                }
            }

            $imageInfo = pathinfo($image);

            //Uygun uzantıyı bul
            $extension = self::suitableExtension($imageInfo);

            //attribute etiketleri ayarla
            $attribute      = self::createAttribute($attr);

            $source    = '';
            $resize = isset($param['resize']) ? $param['resize'] : false;
            for ($p = 0; $p < count($param['width']); $p++)
            {
                $width  = $param['width'][$p];
                $height = $param['height'][$p];

                //Yeni isim veriliyor
                $fileName  = self::newFileName($name, $width, $height, $extension, $resize);


                //Img bilgisi kontrol ediliyor
                if (!self::checkImage($fileName))
                {
                    //Img düzenle
                    self::resizeImg($image, $width, $height, $extension, $fileName, $resize);
                }

                $img            = asset('upload/cache/'.$fileName);
                $srcSet[]       = $img.' '.$width.'w';
                $newFileName[]  = $fileName;

                $source .= '<source media="(max-width: '.$width.'px)" srcset="'.$img.'" />';
            }


            //etiketler atanıyor
            $tagSrc         = 'src="'. asset('upload/cache/'.$newFileName[0]).'"';
            $tagDataSrc     = 'data-src="'.asset('upload/cache/'.$newFileName[0]).'"';
            $tagSrcSet      = count($srcSet) > 1 ? 'srcset="'.implode(", ", $srcSet).'"' : '';
            $tagWidth       = 'width="'.max($param['width']).'"';
            $tagHeight      = 'height="'.$param['height'][array_search( max($param['width']), $param['width'])].'"';

            //tag tipi isteğine göre tag oluşturuluyor
            switch ($type)
            {
                case 'picture' :
                    $imgTag         = '<picture>';
                    $imgTag        .= $source;
                    $imgTag        .= '<img '.$tagSrc.' '.$tagWidth.' '.$tagHeight.' '.$tagDataSrc.' '.$attribute.'>';
                    $imgTag        .= '</picture>';
                    break;
                case 'lazy' :
                    //$tagSrc    = 'src="'. asset(config('app.loading_image')) .'"';
                    $tagSrc    = '';
                    $imgTag    = '<img '.$tagSrc.' '.$tagWidth.' '.$tagHeight.' '.$tagDataSrc.' '.$attribute.' '.$tagSrcSet.'>';
                    break;
                case 'slider' :
                    $imgTag    = '<img '.$tagWidth.' '.$tagHeight.' '.$tagDataSrc.' '.$attribute.'>';
                    break;
                default :
                    $imgTag    = '<img '.$tagSrc.' '.$tagWidth.' '.$tagHeight.' '.$attribute.' '.$tagSrcSet.'>';
            }

            return $imgTag;

        }
        catch (\Exception $exception)
        {
            //dd($exception, $image, $imageInfo);
        }

    }

    public static function createAttribute($param = []):string
    {
        foreach ($param as $key => $value)
        {
            $attribute[] = $key.'="'.$value.'"';
        }

        return count($param) > 0 ?  implode(" ", $attribute) : '';
    }

    public static function suitableExtension($image,$extensionType = false):string
    {
        $browser = new Browser();

        $browserList    = self::$browserList;
        $browserName    = $browser->getBrowser();
        $browserVersion = current(explode('.', $browser->getVersion()));
        $extension      = $image['extension'];

        if ($extensionType == false)
        {
            if (array_key_exists($browserName, $browserList) && $browserVersion > $browserList[$browserName] && $image['extension'] != 'gif')
            {
                $extension = 'webp';
            }
        }

        return $extension;
    }

    public static function checkImage($image):bool
    {
        return Storage::disk(self::$disk)->exists($image);
    }

    public static function resizeImg($image, $width, $height, $extension, $fileName, $resize)
    {
        $img      = Image::make($image);
        if($resize){
            $resize   = $img->resize($width, $height, function ($constraint) {$constraint->aspectRatio();$constraint->upsize();});
        }else{
            $resize   = $img->fit($width, $height, function ($constraint) {$constraint->aspectRatio();$constraint->upsize();});
        }

        $storage  = Storage::disk(self::$disk);

        $img->encode($extension,100);
        $storage->put($fileName, $resize->__toString());

    }

    public static function newFileName($filename, $width = false, $height = false, $extension = 'jpg', $resize = false):string
    {

        $newFileName = '';

        if($resize)
        {
            $newFileName = '-resize';
        }

        //height yoksa
        if ($width && !$height)
        {
            $newFileName = '-'.$width;
        }

        //width yoksa
        if (!$width && $height)
        {
            $newFileName = '-'.$height;
        }

        //width,height varsa
        if($width && $height)
        {
            $newFileName = '-'.$width.'x'.$height;
        }

        if (empty($filename))
        {
            $filename = Str::slug(config('app.custom_title'),'-');
        }
        else
        {
            $filename = Str::slug($filename,'-');
        }

        return $filename.'.'.$extension;
    }

    public static function getStringImgList($string):array
    {

        preg_match_all('/(<img .*?>)/', $string, $retVal);

        return $retVal[1];
    }

    public static function getTagAttr($tag, $attr = 'src')
    {

        $pattern = '/'.$attr.'="([^"]*)"/';
        preg_match($pattern, $tag, $retVal);

        return isset($retVal[1]) ? $retVal[1] : false;
    }

    public static function getFirstImage($image)
    {
        //default jpg
        $path = asset('assets/images/default.jpg');

        $image = collect(self::getStringImgList($image))->first();
        if($image){
            $path = self::getTagAttr($image);
        }

        return $path;
    }

}


