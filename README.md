# webp-convert

**Laravel'de çalışan HTML etiketi olan 'img' etiketinin oluşturulması ve jpg,png vs. resim formatlarının webp formatına dönüştüren bir kütüphanedir.**
** https://packagist.org/packages/halilbelkir/img-webp-convert

## Gerekli olan yüklemeler

*   Composer Yüklendi
*   [Laravel Yükle](https://laravel.com/docs/installation)
## Yükleme

```bash
composer require halilbelkir/img-webp-convert
```

```bash
php artisan vendor:publish --provider="halilBelkir\WebConvert\WebpConvertServiceProvider" --force
```

## Filesystems Düzenleme 

config/filesystem içerisinde ki disks dizinin altına aşağıdaki array dizinini ekleyiniz.

```bash

'cache' => [
                'driver' => 'local',
                'root'   => public_path() . '/upload/cache',
                'url'    => '/upload/cache',
            ],
```

## Config App Düzenleme

app/Providers/AppServiceProvider içerisinde ki register fonksiyonun altına aşağıdaki tanımlamayı ekleyiniz.

```bash

$loader = AliasLoader::getInstance();
$loader->alias('WebpConvert', halilBelkir\WebConvert\ImageHelper::class);
$loader->alias('Image', Image::class);
```

## Kullanımı

```bash

  {!! WebpConvert::createTag(resim yolu,['width' =>[1440,768,500], 'height' => [500,400,400]],['alt' => 'alt','title' => 'title','class' => 'class adı'],'lazy load kullanılacak ise buraya sadece "lazy" yazmanız yeterlidir','resmin yeni adı',1 olursa başka domainden kendi dosyanıza indirir ) !!}
  
```

## 2. Kullanımı

```bash

  {!! WebpConvert::getImage(resim yolu,width,height,resmin yeni adı,1 olursa başka domainden kendi dosyanıza indirir) !!}
  
```