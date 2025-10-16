<?php

namespace halilBelkir\WebConvert;

use halilBelkir\WebConvert\Browser;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Imagick\Driver;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;



class ImageHelper
{


    private static $browserList = ['Chrome' => 8, 'Mozilla' => 64, 'Safari' => '13.2', 'Opera' => '10.2', 'Edge' => 17, 'Android' => 3];

    public static function getDisk()
    {
        return Config::get('img-webp-convert.disk');
    }

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
            self::resizeImg($image, $width, $height, $extension, $fileName, $resize,$status);
        }

        return Storage::disk(self::getDisk())->url($fileName);
    }

    public static function createTag($image,$param=[],$attr=[],$type='',$name = null,$status=null)
    {
        try
        {
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

                    if( $retcode !=200 )
                    {
                        $image  = config('img-webp-convert.no-image');
                        $status = null;
                    }
                }
                else
                {
                    if (!file_exists(public_path($image))) $image = config('img-webp-convert.no-image');
                }
            }

            $imageInfo = pathinfo($image);

            //Uygun uzantıyı bul
            $extension = self::suitableExtension($imageInfo);

            //attribute etiketleri ayarla
            $attribute = self::createAttribute($attr);
            $source    = '';
            $resize    = isset($param['resize']) ? $param['resize'] : false;

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
                    self::resizeImg($image, $width, $height, $extension, $fileName, $resize,$status);
                }

                $img            = Storage::disk(self::getDisk())->url($fileName);
                $srcSet[]       = $img.' '.$width.'w';
                $newFileName[]  = $fileName;

                $source .= '<source media="(max-width: '.$width.'px)" srcset="'.$img.'" />';
            }

            //etiketler atanıyor
            $tagSrc     = 'src="'. Storage::disk(self::getDisk())->url($newFileName[0]).'"';
            $tagDataSrc = 'data-src="'.Storage::disk(self::getDisk())->url($newFileName[0]).'"';
            $tagSrcSet  = count($srcSet) > 1 ? 'srcset="'.implode(", ", $srcSet).'"' : '';
            $tagWidth   = 'width="'.max($param['width']).'"';
            $tagHeight  = 'height="'.$param['height'][array_search( max($param['width']), $param['width'])].'"';

            //tag tipi isteğine göre tag oluşturuluyor
            switch ($type)
            {
                case 'picture' :
                    $imgTag   = '<picture>';
                    $imgTag  .= $source;
                    $imgTag  .= '<img '.$tagSrc.' '.$tagWidth.' '.$tagHeight.' '.$tagDataSrc.' '.$attribute.'>';
                    $imgTag  .= '</picture>';
                    break;
                case 'lazy' :
                    $tagSrc    = 'src="'. config('img-webp-convert.loading-image') .'"';
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
            dd($exception);
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
        $browser        = new Browser();
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
        return Storage::disk(self::getDisk())->exists($image);
    }

    public static function resizeImg($image, $width, $height, $extension, $fileName, $resize,$status)
    {
        $manager = new ImageManager(new Driver());

        if ($status == 1)
        {
            $img = $manager->read(file_get_contents($image));
        }
        else
        {

            $img = $manager->read(file_get_contents(public_path($image)));
        }

        if($resize)
        {
            $resize   = $img->resize($width, $height);
        }
        else
        {
            $resize   = $img->cover($width, $height, 'center');
        }

        $storage = Storage::disk(self::getDisk());
        $encode  = $resize->encodeByExtension($extension);

        $storage->put($fileName, $encode->toString());

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
            $filename = Str::slug(config('img-webp-convert.custom-title'),'-');
        }
        else
        {
            $filename = Str::slug($filename,'-');
        }

        //return $filename.'-'.$newFileName.'.'.$extension;
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
        $path = config('img-webp-convert.no-image');

        $image = collect(self::getStringImgList($image))->first();

        if($image)
        {
            $path = self::getTagAttr($image);
        }

        return $path;
    }

}


