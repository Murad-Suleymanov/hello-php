# Hello PHP

Sadə PHP tətbiqi, Docker ilə hazır. Prometheus metrics dəstəyi var.

## API Endpointləri

| Endpoint        | Metod | Təsvir              |
|-----------------|-------|---------------------|
| `/`             | GET   | Əsas səhifə         |
| `/health`       | GET   | Sağlamlıq yoxlaması |
| `/metrics`      | GET   | Prometheus metrics  |
| `/api/hello`    | GET   | `?name=` ilə salam  |
| `/items/{id}`   | GET   | Item məlumatı       |

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

> Qeyd: sayğaclar proseslər arasında APCu shared memory vasitəsilə saxlanılır.
