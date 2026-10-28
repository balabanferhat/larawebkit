{{-- Cookie consent: nothing but necessary cookies until the visitor chooses. Analytics load in resources/js/site.js / inline loader below. --}}
@if (\App\Modules\Core\ModuleRegistry::enabled('privacy') && setting('privacy.consent_enabled', true))
    @php($ga = setting('analytics.google_analytics_id'))
    @php($plausible = setting('analytics.plausible_domain'))
    <div id="lwk-consent" class="lwk-consent" role="dialog" aria-live="polite" aria-labelledby="lwk-consent-title" hidden>
        <p id="lwk-consent-title" class="lwk-consent-title">{{ __('larawebkit-privacy.banner.title') }}</p>
        <p>{{ __('larawebkit-privacy.banner.text') }} <a href="{{ localized_path('/cookie-policy') }}">{{ __('larawebkit-privacy.banner.policy') }}</a></p>
        <form class="lwk-consent-options" hidden>
            <label><input type="checkbox" checked disabled> {{ __('larawebkit-privacy.banner.necessary') }}</label>
            <label><input type="checkbox" name="analytics"> {{ __('larawebkit-privacy.banner.analytics') }}</label>
            <label><input type="checkbox" name="marketing"> {{ __('larawebkit-privacy.banner.marketing') }}</label>
        </form>
        <div class="lwk-consent-actions">
            <button type="button" data-consent="reject">{{ __('larawebkit-privacy.banner.reject') }}</button>
            <button type="button" data-consent="customize">{{ __('larawebkit-privacy.banner.customize') }}</button>
            <button type="button" data-consent="save" hidden>{{ __('larawebkit-privacy.banner.save') }}</button>
            <button type="button" data-consent="all" class="lwk-btn-primary">{{ __('larawebkit-privacy.banner.accept_all') }}</button>
        </div>
    </div>
    <script>
        (function () {
            var cfg = @js(['ga' => $ga ?: null, 'plausible' => $plausible ?: null]);
            var box = document.getElementById('lwk-consent');
            function read() { var m = document.cookie.match(/(?:^|; )lwk_consent=([^;]*)/); try { return m ? JSON.parse(decodeURIComponent(m[1])) : null; } catch (e) { return null; } }
            function write(c) { document.cookie = 'lwk_consent=' + encodeURIComponent(JSON.stringify(c)) + ';path=/;max-age=31536000;SameSite=Lax' + (location.protocol === 'https:' ? ';Secure' : ''); }
            function load(c) {
                window.lwkConsent = c;
                if (c.analytics && cfg.ga && !window.gtag) {
                    var s = document.createElement('script'); s.async = true; s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga); document.head.appendChild(s);
                    window.dataLayer = window.dataLayer || []; window.gtag = function () { dataLayer.push(arguments); }; gtag('js', new Date()); gtag('config', cfg.ga, { anonymize_ip: true });
                }
                if (c.analytics && cfg.plausible && !window.plausible) {
                    var p = document.createElement('script'); p.defer = true; p.dataset.domain = cfg.plausible; p.src = 'https://plausible.io/js/script.js'; document.head.appendChild(p);
                }
                document.dispatchEvent(new CustomEvent('lwk:consent', { detail: c }));
            }
            function decide(c) { write(c); box.hidden = true; load(c); }
            var current = read();
            if (current) { load(current); } else { box.hidden = false; }
            box.addEventListener('click', function (e) {
                var a = e.target.getAttribute && e.target.getAttribute('data-consent'); if (!a) return;
                var form = box.querySelector('form');
                if (a === 'all') decide({ necessary: true, analytics: true, marketing: true });
                if (a === 'reject') decide({ necessary: true, analytics: false, marketing: false });
                if (a === 'customize') { form.hidden = false; box.querySelector('[data-consent=save]').hidden = false; e.target.hidden = true; }
                if (a === 'save') decide({ necessary: true, analytics: form.analytics.checked, marketing: form.marketing.checked });
            });
            document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('[data-open-consent]')) { e.preventDefault(); box.hidden = false; } });
        })();
    </script>
@endif
