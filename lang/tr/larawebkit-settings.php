<?php

return [
    'title' => 'Ayarlar',
    'saved' => 'Ayarlar kaydedildi.',
    'groups' => [
        'general' => 'Genel',
        'social' => 'Sosyal',
        'organization' => 'Kurum',
        'analytics' => 'Analitik',
        'seo' => 'SEO',
        'geo' => 'Yapay zekâ araması (GEO)',
        'privacy' => 'Gizlilik',
        'features' => 'Özellikler',
        'ai' => 'Yapay zekâ',
    ],
    'keys' => [
        'general' => [
            'site_name' => 'Site adı',
            'tagline' => 'Slogan',
            'logo' => 'Logo',
            'contact_email' => 'İletişim e-postası',
            'default_locale' => 'Varsayılan dil',
            'active_locales' => 'Etkin diller',
            'theme' => 'Tema',
            'posts_per_page' => 'Sayfa başına yazı',
        ],
        'social' => [
            'links' => 'Sosyal profiller',
        ],
        'organization' => [
            'type' => 'Site neyi temsil ediyor',
            'name' => 'Kurum / kişi adı',
            'logo' => 'Kurum logosu',
            'phone' => 'Telefon',
            'address' => 'Adres',
        ],
        'analytics' => [
            'google_analytics_id' => 'Google Analytics ölçüm kimliği',
            'plausible_domain' => 'Plausible alan adı',
            'google_site_verification' => 'Google Search Console doğrulama kodu',
            'bing_site_verification' => 'Bing Webmaster doğrulama kodu',
        ],
        'seo' => [
            'title_separator' => 'Başlık ayracı',
            'default_meta_description' => 'Varsayılan meta açıklama',
            'default_og_image' => 'Varsayılan paylaşım görseli',
            'canonical_host' => 'Kanonik alan adı',
            'www_policy' => 'www kuralı',
            'force_https' => 'https\'e yönlendir',
            'trailing_slash' => 'Sondaki eğik çizgi kuralı',
            'robots_extra' => 'Ek robots.txt kuralları',
            'indexnow_enabled' => 'Yayında IndexNow bildirimi gönder',
        ],
        'geo' => [
            'llms_intro' => 'llms.txt giriş metni',
            'ai_preset' => 'Yapay zekâ tarayıcı politikası',
            'ai_bots' => 'İzin verilen yapay zekâ tarayıcıları',
        ],
        'privacy' => [
            'consent_enabled' => 'Çerez onay bandını göster',
            'access_log_retention_days' => 'Erişim kaydı saklama süresi (gün)',
        ],
        'features' => [
            'access_log' => 'Erişim kaydı ve dahili sayfa sayacı',
            'page_cache' => 'Ziyaretçiler için tam sayfa önbellek',
            'contact_form' => 'İletişim formu',
            'newsletter' => 'Bülten kaydı (çift onaylı)',
            'ai_assistant' => 'Yapay zekâ yazım asistanı (.env içinde sağlayıcı anahtarı gerekir)',
            'pexels' => 'Pexels stok fotoğrafları (.env içinde PEXELS_API_KEY gerekir)',
            'publish_gate' => 'Yayın kapısı (yayından önce asgari puanlar)',
        ],
        'ai' => [
            'monthly_budget_usd' => 'Aylık yapay zekâ bütçesi (USD)',
        ],
    ],
    'help' => [
        'seo' => [
            'canonical_host' => 'örn. ornek.com. Diğer alan adlarındaki istekler 301 ile yönlendirilir. Kapatmak için boş bırakın.',
            'robots_extra' => 'Oluşturulan robots.txt dosyasının sonuna eklenir.',
            'indexnow_enabled' => '.env içinde INDEXNOW_KEY gerekir.',
        ],
        'geo' => [
            'llms_intro' => '/llms.txt dosyasının başında gösterilir. Boşsa slogan kullanılır.',
            'ai_preset' => '"Yalnızca arama", yapay zekâ asistanlarının sizi kaynak göstermesine izin verir ama model eğitimi yapan tarayıcıları engeller.',
            'ai_bots' => 'İşaretli tarayıcılara robots.txt içinde izin verilir, diğerleri engellenir.',
        ],
        'analytics' => [
            'google_analytics_id' => 'Yalnızca ziyaretçi analitik çerezlerini kabul ettikten sonra yüklenir.',
        ],
        'ai' => [
            'monthly_budget_usd' => 'Tahmini maliyet bu tutara ulaştığında yapay zekâ işlemleri ay sonuna kadar kapanır. 0 = sınırsız.',
        ],
        'social' => [
            'links' => 'Ağ adı → profil adresi. Alt bilgide ve yapılandırılmış verilerde sameAs olarak kullanılır.',
        ],
    ],
    'options' => [
        'organization' => 'Bir kurum',
        'person' => 'Bir kişi',
        'ignore' => 'Olduğu gibi bırak',
        'www' => 'Her zaman www',
        'non_www' => 'Hiçbir zaman www',
        'remove' => 'Sondaki eğik çizgiyi kaldır',
        'add' => 'Sona eğik çizgi ekle',
        'allow_all' => 'Tüm yapay zekâ tarayıcılarına izin ver',
        'search_only' => 'Yapay zekâ aramasına izin ver, eğitimi engelle',
        'block_all' => 'Tüm yapay zekâ tarayıcılarını engelle',
        'custom' => 'Tarayıcı bazında seç',
    ],
    'map' => [
        'key' => 'Ad',
        'value' => 'Adres',
    ],
];
