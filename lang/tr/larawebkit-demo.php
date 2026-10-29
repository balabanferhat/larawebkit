<?php

/*
| Her temanın demo içeriğinde (themes/{name}/demo.php) ortak kullanılan yer tutucu metinler.
| Temalar kendi metinlerini themes/{name}/lang/{locale}/demo.php dosyasına ekler.
*/

return [
    'notice' => 'Demo sitesi: buradaki tüm adlar, yazılar ve görseller uydurma örneklerdir.',
    'post_excerpt' => 'Yer tutucu metinli demo yazısı. Kendi hikâyenizle değiştirin ya da siteniz hazır olduğunda silin.',
    'blog' => [
        'name' => 'Saha Notları',
        'tagline' => 'Yazı, araçlar ve dikkat üzerine kısa notlar. Bir demo blog.',
        'footer' => 'Saha Notları, larawebkit Sade temasının demo blogudur.',
        'role' => 'Yazar',
        'bio' => 'Sam, larawebkit demosu için hayalî bir yazardır.',
        'category' => 'Notlar',
        'blog' => 'Blog',
        'about' => 'Hakkımda',
        'about_text' => 'Bu blog larawebkit demosunun parçasıdır. Kendinizden ve okurların burada ne bulacağından birkaç cümleyle bahsedin.',
        'privacy' => 'Gizlilik',
        'cookies' => 'Çerezler',
        'posts' => [
            ['title' => 'Bir ay boyunca her sabah yazmak', 'excerpt' => 'Neler değişti, neler değişmedi ve sürdürdüğüm tek alışkanlık.'],
            ['title' => 'Gerçekten kullandığım araçlar', 'excerpt' => 'Kısa bir liste ve neden giderek kısaldığı.'],
            ['title' => 'Dikkat üzerine notlar', 'excerpt' => 'Daha az bildirimle küçük deneyler.'],
            ['title' => 'Neden kâğıt defter tutuyorum', 'excerpt' => 'Yavaş girdi, daha iyi düşünme.'],
            ['title' => 'Bitirmek üzerine', 'excerpt' => 'Bitmiş olmak bir özelliktir.'],
        ],
    ],
    'article' => [
        'paragraphs' => [
            'Bu, larawebkit demosundaki yer tutucu metindir. Gerçek bir yazının bu temada nasıl okunduğunu gösterir: satır uzunluğu, paragraflar arası boşluk, başlıkların, listelerin ve alıntıların görünümü. Hazır olduğunuzda kendi cümlelerinizle değiştirin.',
            'İyi yazılar yazarın geçmişiyle değil, okurun sorusuyla başlar. İlk iki cümlede okurun ne öğreneceğini ve bunun onun için neden önemli olduğunu söyleyin. Gerisi ardından gelebilir.',
            'Kısa paragraflar telefonda daha kolay okunur. Her biri tek bir fikir taşıyan üç beş cümlelik paragraflar hedefleyin; yapıyı başlıklar taşısın ki okurlar karar vermeden önce göz gezdirebilsin.',
            'Somut örnekler genel tavsiyelerden iyidir. Bir şeyin zaman kazandırdığını söylüyorsanız ne kadar ve kime kazandırdığını belirtin. Bir araç öneriyorsanız işe yaradığı ve yaramadığı durumu anlatın.',
            'Görseller süslemek için değil, açıklamak için olmalı. Bir şema, bir ekran görüntüsü ya da gerçeğin fotoğrafı, sıradan bir stok görselden çok daha değerlidir. Göremeyen kişiler için görseli her zaman alternatif metinde tarif edin.',
            'Arama motorları ve yapay zekâ asistanları, okurların değer verdiği şeyleri ödüllendirir: en üstte net bir yanıt, dürüst kaynaklar, konusunu bilen bir yazar ve yavaş bağlantıda bile hızlı açılan bir sayfa.',
            'Yayınlamadan önce yazıyı bir kez sesli okuyun. Söylemesi zor cümlelerin okunması da zordur. Tekrarlanan fikirleri, dolgu kelimeleri ve bir arkadaşınıza söylemeyeceğiniz her şeyi çıkarın.',
            'Son olarak bir sonraki adımla bitirin: ilgili bir yazı, okura bir soru ya da iletişim yolu. Sayfanızdan fayda gören okurlar biraz daha kalmaktan memnun olur.',
        ],
        'headings' => [
            'Okurun sorusuyla başlayın',
            'Göz gezdirmeyi kolaylaştırın',
            'Anlatmakla kalmayın, gösterin',
            'Yayınlamadan önce kontrol edin',
        ],
        'list' => [
            'Her paragrafta tek fikir',
            'Özet işlevi gören başlıklar',
            'Gerçek rakamlarla örnekler',
            'Her görsel için alternatif metin',
        ],
        'quote' => 'Tek bir okur için yazın, pek çoğuna ulaşırsınız.',
    ],
];
