from typing import Dict, Literal, Optional
from pydantic import BaseModel, Field

ModelName = Literal["svm", "naive_bayes", "comparar"]


class InferRequest(BaseModel):
    texto: str = Field(..., min_length=1)
    modelo: ModelName = "svm"


class ModelResult(BaseModel):
    modelo: str
    clasificacion: str
    confianza: float
    probabilidades: Dict[str, float]


class CompareResult(BaseModel):
    svm: ModelResult
    naive_bayes: ModelResult
    modelo_recomendado: str


class InferResponse(BaseModel):
    success: bool
    message: str
    modelo: str
    texto_limpio: str
    prediccion: Optional[ModelResult] = None
    comparacion: Optional[CompareResult] = None