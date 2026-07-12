# Hello PHP

Sadə PHP tətbiqi, Docker ilə hazır. Prometheus metrics dəstəyi var.

## API Endpointləri

| Endpoint        | Metod | Təsvir              |
|-----------------|-------|---------------------|
| `/`             | GET   | Əsas səhifə         |
| `/health`       | GET   | Sağlamlıq yoxlaması |
| `/metrics`      | GET   | Prometheus metrics  |
| `/api/hello`    | GET   | `?name=` ilə salam  |
| `/api/csharp`   | GET   | `?name=` ilə hello-csharp servisinə müraciət |
| `/api/csharp-error` | GET | hello-csharp `/error` çağırır — həmişə 500 qaytarır |
| `/items/{id}`   | GET   | Item məlumatı       |
| `/csharp`       | GET   | hello-csharp servisinin cavabı |
| `/aggregate`    | GET   | `?name=` — php + hello-csharp birləşmiş cavabı |

## Servislərarası çağırış

`/api/csharp` endpoint-i hello-csharp servisinə HTTP GET (`/aggregate?name=`) göndərir.
Hədəf URL `HELLO_CSHARP_URL` mühit dəyişəni ilə dəyişdirilə bilər, default:

```
http://hello-csharp-main-svc.hello-csharp.svc.cluster.local:8080
```

Servis əlçatan olmasa cavabda `"response": "unreachable"` qayıdır (tətbiq çökmür).

### `/api/csharp-error` — qəsdən 500

`/api/csharp-error` endpoint-i hello-csharp servisinin `/error` endpoint-ini çağırır. O endpoint
test məqsədi ilə həmişə `500` qaytarır və hello-php həmin status kodunu olduğu kimi ötürür —
beləcə xəta hər iki servisin metrikalarında (error rate, Kiali/Grafana) görünür.
hello-csharp ümumiyyətlə əlçatmaz olsa `502` qayıdır.

## Docker ilə işə salma

```bash
# Image qurma
docker build -t hello-php .

# Konteyneri işə salma
docker run -p 8080:8080 hello-php
```

Sonra brauzerdə açın: http://localhost:8080/

## Lokal işə salma (Docker olmadan)

```bash
php -S 0.0.0.0:8080 index.php
```

## Metrics

`/metrics` endpoint-i Prometheus formatında aşağıdakıları təqdim edir:

- `http_requests_total` — request sayğacı (`method`, `endpoint` label-ları ilə)
- `http_request_duration_seconds` — request müddəti histoqramı
- `phpfpm_active_processes` — PHP-FPM aktiv proses sayı
- `phpfpm_total_processes` — PHP-FPM ümumi proses sayı

> Qeyd: sayğaclar proseslər arasında APCu shared memory vasitəsilə saxlanılır.
>
> PHP-FPM proses sayılarını `/metrics` endpoint-i vasitəsilə almaq üçün `PHP_FPM_STATUS_URL` env dəyişəni istifadə edilə bilər. Məsələn:
>
> ```bash
> PHP_FPM_STATUS_URL=http://127.0.0.1/status docker run -p 8080:8080 hello-php
> ```
