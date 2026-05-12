from pathlib import Path
import json
import math

import joblib
import numpy as np
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from app.schemas import InferRequest, InferResponse, ModelResult, CompareResult
from app.text_processor import normalize_text

BASE_DIR = Path(__file__).resolve().parent.parent
MODELS_DIR = BASE_DIR / "models"

VECTORIZER_PATH = MODELS_DIR / "tfidf_vectorizer.joblib"
NB_PATH = MODELS_DIR / "naive_bayes.joblib"
SVM_PATH = MODELS_DIR / "svm_lineal.joblib"
METADATA_PATH = MODELS_DIR / "metadata.json"

app = FastAPI(
    title="Detector de Texto IA - Servicio de Inferencia",
    version="1.0.0",
    description="API para clasificar texto como Humano o IA usando TF-IDF + Naive Bayes / SVM.",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # ajusta en producción
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

vectorizer = None
naive_bayes_model = None
svm_model = None
metadata = {}


def load_artifacts() -> None:
    global vectorizer, naive_bayes_model, svm_model, metadata

    if not VECTORIZER_PATH.exists():
        raise FileNotFoundError(f"No existe el vectorizador: {VECTORIZER_PATH}")
    if not NB_PATH.exists():
        raise FileNotFoundError(f"No existe el modelo Naive Bayes: {NB_PATH}")
    if not SVM_PATH.exists():
        raise FileNotFoundError(f"No existe el modelo SVM: {SVM_PATH}")

    vectorizer = joblib.load(VECTORIZER_PATH)
    naive_bayes_model = joblib.load(NB_PATH)
    svm_model = joblib.load(SVM_PATH)

    if METADATA_PATH.exists():
        with open(METADATA_PATH, "r", encoding="utf-8") as f:
            metadata = json.load(f)
    else:
        metadata = {
            "labels": {"0": "Humano", "1": "IA"}
        }


@app.on_event("startup")
def on_startup() -> None:
    load_artifacts()


@app.get("/health")
def health():
    return {
        "success": True,
        "status": "ok",
        "vectorizer_loaded": vectorizer is not None,
        "nb_loaded": naive_bayes_model is not None,
        "svm_loaded": svm_model is not None,
    }


def normalize_label(value) -> str:
    value_str = str(value).strip().lower()

    if value_str in {"0", "humano", "human"}:
        return "Humano"
    if value_str in {"1", "ia", "ai"}:
        return "IA"

    return str(value)


def probabilities_from_model(model, x_vector):
    """
    Devuelve un dict {"Humano": prob, "IA": prob} cuando sea posible.
    Si el modelo tiene predict_proba, lo usa.
    Si no, usa decision_function para aproximar una probabilidad.
    """
    if hasattr(model, "predict_proba"):
        probs = model.predict_proba(x_vector)[0]
        classes = list(model.classes_)
        prob_map = {normalize_label(c): float(p) for c, p in zip(classes, probs)}

        # Asegurar ambas llaves
        prob_map.setdefault("Humano", 0.0)
        prob_map.setdefault("IA", 0.0)
        return prob_map

    if hasattr(model, "decision_function"):
        score = model.decision_function(x_vector)
        if isinstance(score, (np.ndarray, list)):
            score = float(score[0])
        else:
            score = float(score)

        # Sigmoide
        p_ia = 1.0 / (1.0 + math.exp(-score))
        p_h = 1.0 - p_ia

        return {"Humano": float(p_h), "IA": float(p_ia)}

    # Último recurso
    pred = normalize_label(model.predict(x_vector)[0])
    return {pred: 1.0, ("IA" if pred == "Humano" else "Humano"): 0.0}


def predict_single(model_name: str, texto_limpio: str) -> ModelResult:
    x_vec = vectorizer.transform([texto_limpio])

    if model_name == "svm":
        model = svm_model
    elif model_name == "naive_bayes":
        model = naive_bayes_model
    else:
        raise HTTPException(status_code=400, detail="Modelo no válido.")

    pred = normalize_label(model.predict(x_vec)[0])
    probs = probabilities_from_model(model, x_vec)
    confianza = max(probs.values()) if probs else 0.0

    return ModelResult(
        modelo=model_name,
        clasificacion=pred,
        confianza=round(float(confianza), 4),
        probabilidades={k: round(float(v), 4) for k, v in probs.items()},
    )


@app.post("/api/infer", response_model=InferResponse)
def infer(payload: InferRequest):
    if vectorizer is None or naive_bayes_model is None or svm_model is None:
        raise HTTPException(status_code=500, detail="Los artefactos del modelo no se cargaron correctamente.")

    texto_limpio = normalize_text(payload.texto)

    if not texto_limpio:
        raise HTTPException(status_code=400, detail="El texto quedó vacío después de la normalización.")

    if len(texto_limpio) < 5:
        raise HTTPException(status_code=400, detail="El texto es demasiado corto para clasificarlo.")

    if payload.modelo == "comparar":
        svm_res = predict_single("svm", texto_limpio)
        nb_res = predict_single("naive_bayes", texto_limpio)

        modelo_recomendado = "svm" if svm_res.confianza >= nb_res.confianza else "naive_bayes"

        return InferResponse(
            success=True,
            message="Texto analizado correctamente.",
            modelo="comparar",
            texto_limpio=texto_limpio,
            comparacion=CompareResult(
                svm=svm_res,
                naive_bayes=nb_res,
                modelo_recomendado=modelo_recomendado,
            ),
        )

    prediccion = predict_single(payload.modelo, texto_limpio)

    return InferResponse(
        success=True,
        message="Texto analizado correctamente.",
        modelo=payload.modelo,
        texto_limpio=texto_limpio,
        prediccion=prediccion,
    )