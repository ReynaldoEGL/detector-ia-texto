# Detector de Texto IA — Documentación de la API

Base URL: `http://localhost:8000/api`

---

## Endpoints

### `POST /api/clasificar-texto`
Analiza un texto y devuelve si fue generado por IA o por un humano.

**Headers**
```
Content-Type: application/json
Accept: application/json
```

**Body**
| Campo   | Tipo   | Requerido | Descripción |
|---------|--------|-----------|-------------|
| `texto` | string | ✅        | Texto a analizar (10–12 000 chars) |
| `modelo`| string | ❌        | `svm` (default) · `naive_bayes` · `comparar` |

---

#### Respuesta exitosa — modelo único (`svm` o `naive_bayes`)
```json
HTTP 200
{
  "success": true,
  "message": "Texto analizado correctamente.",
  "data": {
    "texto_original": "El texto tal como fue enviado...",
    "texto_limpio":   "el texto despues de normalizar",
    "modelo": "svm",
    "prediccion": {
      "modelo": "svm",
      "clasificacion": "IA",
      "confianza": 0.9231,
      "probabilidades": {
        "Humano": 0.0769,
        "IA": 0.9231
      },
      "raw": { }
    }
  }
}
```

#### Respuesta exitosa — modo `comparar`
```json
HTTP 200
{
  "success": true,
  "message": "Texto analizado correctamente.",
  "data": {
    "texto_original": "El texto...",
    "texto_limpio":   "el texto",
    "modelo": "comparar",
    "comparacion": {
      "svm": {
        "modelo": "svm",
        "clasificacion": "IA",
        "confianza": 0.9231,
        "probabilidades": { "Humano": 0.0769, "IA": 0.9231 },
        "raw": { }
      },
      "naive_bayes": {
        "modelo": "naive_bayes",
        "clasificacion": "IA",
        "confianza": 0.8854,
        "probabilidades": { "Humano": 0.1146, "IA": 0.8854 },
        "raw": { }
      }
    },
    "modelo_recomendado": "svm"
  }
}
```

#### Error de validación
```json
HTTP 422
{
  "success": false,
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "texto": ["El texto debe tener al menos 10 caracteres."],
    "modelo": ["El modelo \"xyz\" no es válido. Opciones: svm, naive_bayes, comparar."]
  }
}
```

#### Servicio de inferencia no disponible
```json
HTTP 502
{
  "success": false,
  "message": "El servicio de inferencia respondió con HTTP 503: ...",
  "data": null
}
```

#### Error interno
```json
HTTP 500
{
  "success": false,
  "message": "Error interno al procesar la solicitud.",
  "data": null
}
```

---

### `GET /api/health`
Verifica el estado del servicio Laravel y del microservicio Python.

```json
HTTP 200
{
  "success": true,
  "status": "ok",
  "services": {
    "laravel": {
      "status": "ok",
      "version": "11.x"
    },
    "python_inference": {
      "status": "ok",
      "url": "http://127.0.0.1:8001/api/infer",
      "details": {
        "success": true,
        "status": "ok",
        "vectorizer_loaded": true,
        "nb_loaded": true,
        "svm_loaded": true
      },
      "error": null
    }
  }
}
```

Si el microservicio Python no está disponible devuelve `HTTP 503` con `"status": "degraded"`.

---

### `GET /api/modelos`
Devuelve los modelos disponibles y la configuración activa.

```json
HTTP 200
{
  "success": true,
  "data": {
    "modelos_disponibles": ["svm", "naive_bayes", "comparar"],
    "modelo_default": "svm",
    "max_caracteres": 12000,
    "min_caracteres": 10
  }
}
```

---

## Ejemplos con cURL

```bash
# Análisis con SVM (default)
curl -s -X POST http://localhost:8000/api/clasificar-texto \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"texto": "La inteligencia artificial ha transformado la industria tecnológica de maneras sin precedentes."}'

# Análisis con Naive Bayes
curl -s -X POST http://localhost:8000/api/clasificar-texto \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"texto": "Hoy fui al mercado y me encontré con mi vecina.", "modelo": "naive_bayes"}'

# Comparar ambos modelos
curl -s -X POST http://localhost:8000/api/clasificar-texto \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"texto": "Lorem ipsum dolor sit amet consectetur adipiscing elit.", "modelo": "comparar"}'

# Health check
curl -s http://localhost:8000/api/health

# Listar modelos
curl -s http://localhost:8000/api/modelos
```

---

## Rate Limiting
El endpoint `/api/clasificar-texto` permite **60 peticiones por minuto** por IP.
Al superarlo se devuelve `HTTP 429 Too Many Requests`.

---

## Variables de entorno relevantes

| Variable | Default | Descripción |
|----------|---------|-------------|
| `TEXT_DETECTOR_INFERENCE_URL` | `http://127.0.0.1:8001/api/infer` | URL del microservicio Python |
| `TEXT_DETECTOR_DEFAULT_MODEL` | `svm` | Modelo usado si no se especifica |
| `TEXT_DETECTOR_MAX_LENGTH`    | `12000` | Máximo de caracteres por texto |
| `TEXT_DETECTOR_REQUEST_TIMEOUT` | `30` | Timeout en segundos hacia Python |
