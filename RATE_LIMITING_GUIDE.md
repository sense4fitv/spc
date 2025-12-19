# Rate Limiting Guide - S.P.O.R.

## Ce este Rate Limiting?

Rate limiting previne atacurile brute force prin limitarea numărului de încercări de login dintr-un anumit interval de timp.

## Cum Funcționează

Filter-ul `RateLimitFilter`:
- **Max încercări**: 5 încercări per IP
- **Fereastră de timp**: 15 minute (900 secunde)
- **Durata blocaj**: 30 minute (1800 secunde) după ce s-a atins limita
- **Stocare**: Folosește Cache (File cache by default, poate fi schimbat la Redis)

## Configurare

Filter-ul este deja înregistrat în `app/Config/Filters.php` ca `ratelimit`.

## Utilizare

### 1. Pe Rute Specifice (Recomandat)

În `app/Config/Routes.php`:

```php
$routes->post('auth/login', 'AuthController::doLogin', ['filter' => 'ratelimit']);
```

### 2. Pe toate rutele de autentificare

În `app/Config/Filters.php`, adăugăm în `$filters`:

```php
public array $filters = [
    'ratelimit' => ['before' => ['auth/login', 'auth/set-password']],
];
```

### 3. Pe toate POST-urile (mai agresiv)

În `app/Config/Filters.php`:

```php
public array $methods = [
    'POST' => ['ratelimit'],
];
```

## Comportament

### Cazul 1: Încercări normale
- User încearcă să se logheze
- Fiecare încercare e contorizată
- După 5 încercări în 15 minute → IP-ul e blocat

### Cazul 2: IP Blocat
- Răspuns HTTP 429 (Too Many Requests)
- Mesaj: "Prea multe încercări. IP-ul tău a fost blocat pentru X minute."
- Header `Retry-After` cu secundele rămase

### Cazul 3: Login reușit
- Contorul rămâne (se resetează natural după 15 minute)
- Pot adăuga logica să se reseteze automat după login reușit (în after hook)

## Configurare Avansată

Poți modifica limitele în `app/Filters/RateLimitFilter.php`:

```php
protected int $maxAttempts = 5;      // Numărul maxim de încercări
protected int $timeWindow = 900;     // Fereastra de timp (15 min)
protected int $blockDuration = 1800; // Durata blocajului (30 min)
```

## Dezblocare Manuală (pentru Admini)

Pentru a dezbloca un IP manual (în controller sau CLI):

```php
use App\Filters\RateLimitFilter;

// Dezblocare pentru un IP specific
RateLimitFilter::clearRateLimit('192.168.1.100');

// Dezblocare pentru o rută specifică
RateLimitFilter::clearRateLimit('192.168.1.100', '/auth/login');
```

## Testare

Pentru a testa rate limiting-ul:

1. Încearcă să te loghezi cu credențiale greșite de 5 ori
2. La a 6-a încercare, ar trebui să primești eroarea 429
3. Așteaptă 30 de minute sau resetează manual cache-ul

### Resetare rapidă pentru testare:

```php
// În controller sau tinker
$cache = \Config\Services::cache();
$cache->clean(); // Șterge tot cache-ul (atenție în producție!)
```

## Alternative

Dacă vrei rate limiting mai avansat (per user, nu per IP), poți:
- Extinde filter-ul pentru a verifica și email-ul
- Folosi Redis pentru distribuție multi-server
- Integra cu servicii externe (Cloudflare, etc.)

## Securitate

✅ **Bune:**
- Rate limiting per IP (previne brute force din același IP)
- Block duration rezonabil (30 min)
- Folosește cache (performant)

⚠️ **Considerații:**
- Rate limiting per IP poate afecta utilizatori legitimi din aceeași rețea
- Poți adăuga și rate limiting per email pentru protecție suplimentară
- În producție, consideră Redis pentru cache (distribuit, mai rapid)

## Exemplu de Integrare în AuthController

```php
public function doLogin()
{
    // Rate limiting se aplică automat înainte de request
    // Dacă ajunge aici, înseamnă că rate limit-ul nu e atins
    
    // ... logica de login ...
    
    if ($loginSuccessful) {
        // Opțional: resetează rate limit-ul pentru acest IP
        RateLimitFilter::clearRateLimit($this->request->getIPAddress(), '/auth/login');
    }
}
```

