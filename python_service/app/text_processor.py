import re
import unicodedata


def normalize_text(text: str) -> str:
    """
    Normaliza el texto para inferencia.
    Mantiene acentos y ñ, elimina URLs, menciones, hashtags, puntuación
    y compacta espacios.
    """
    text = str(text or "")
    text = text.replace("\r\n", "\n").replace("\r", "\n")
    text = unicodedata.normalize("NFKC", text)
    text = text.lower().strip()

    # Quitar URLs
    text = re.sub(r"https?://\S+|www\.\S+", " ", text, flags=re.IGNORECASE)

    # Quitar menciones y hashtags
    text = re.sub(r"@\w+", " ", text)
    text = re.sub(r"#\w+", " ", text)

    # Conservar letras, números, espacios, acentos y ñ
    text = re.sub(r"[^\w\sáéíóúüñ¿¡]", " ", text, flags=re.IGNORECASE)

    # Compactar espacios
    text = re.sub(r"\s+", " ", text).strip()

    return text