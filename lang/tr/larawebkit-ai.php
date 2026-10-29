<?php

return [
    'default_instruction' => 'Netliği ve akışı iyileştir.',
    'errors' => [
        'disabled' => 'Yapay zekâ asistanı yapılandırılmamış. .env dosyasına bir sağlayıcı anahtarı (ör. ANTHROPIC_API_KEY) ekleyin.',
        'budget' => 'Bu ayın yapay zekâ bütçesine ulaşıldı.',
        'failed' => 'Yapay zekâ isteği başarısız oldu. Lütfen daha sonra tekrar deneyin.',
    ],
    'actions' => [
        'draft' => 'Taslak',
        'rewrite' => 'Yeniden yazma',
        'tags' => 'Etiketler',
        'meta_description' => 'Meta açıklama',
        'topics' => 'Konu önerileri',
        'image_keywords' => 'Görsel anahtar kelimeleri',
    ],
    'usage' => [
        'singular' => 'Yapay zekâ kullanımı',
        'plural' => 'Yapay zekâ kullanımı',
        'action' => 'İşlem',
        'provider' => 'Sağlayıcı',
        'model' => 'Model',
        'input' => 'Girdi token',
        'output' => 'Çıktı token',
        'cost' => 'Tahmini maliyet',
        'ok' => 'Başarılı',
    ],
    'assistant' => [
        'title' => 'Yapay zekâ asistanı',
        'brief' => 'Taslak için notlar (isteğe bağlı)',
        'generate_draft' => 'Başlıktan taslak oluştur',
        'rewrite_selection' => 'Seçili metni yeniden yaz',
        'rewrite_hint' => 'Önce editörde bir metin seçin.',
        'suggest_tags' => 'Etiket öner',
        'suggest_meta' => 'Meta açıklama öner',
        'suggest_topics' => 'Konu öner',
        'working' => 'Çalışıyor…',
        'queued' => 'Sırada, işleyici bekleniyor…',
        'replace_confirm' => 'Mevcut içerik yapay zekâ taslağıyla değiştirilsin mi?',
        'applied' => 'Uygulandı',
        'budget_left' => 'Bu ay :budget USD\'nin :spent USD\'si kullanıldı',
    ],
];
