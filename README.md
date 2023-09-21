# webp-convert

**Laravel'de çalışan HTML etiketi olan 'img' etiketinin oluşturulması ve jpg,png vs. resim formatlarının webp formatına dönüştüren bir kütüphanedir.**

## Gerekli olan yüklemeler

*   Composer Yüklendi
*   [Laravel Yükle](https://laravel.com/docs/installation)
## Yükleme

```bash

composer require halilbelkir/img-webp-convert

php artisan vendor:publish --provider="http\\WebpConvertServiceProvider" --force

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

config/app içerisinde ki aliases dizinin altına aşağıdaki tanımlamayı ekleyiniz.

```bash

"WebpConvert" => \src\ImageHelper::class
```

## Kullanımı

```bash

  {!! WebpConvert::createTag(resim yolu,['width' =>[1440,768,500], 'height' => [500,400,400]],['alt' => 'alt','title' => 'title','class' => 'class adı'],'lazy load kullanılacak ise buraya sadece "lazy" yazmanız yeterlidir','resmin yeni adı',1 olursa başka domainden kendi dosyanıza indirir ) !!}
  
```

## 2. Kullanımı

```bash

  {!! WebpConvert::getImage(resim yolu,width,height,resmin yeni adı,1 olursa başka domainden kendi dosyanıza indirir) !!}
  
```