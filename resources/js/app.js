document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('analysis-form');
    if (!form) return;

    const textarea = document.getElementById('texto');
    const fileInput = document.getElementById('archivo');
    const modelSelect = document.getElementById('modelo');
    const counter = document.getElementById('contador');
    const submitBtn = document.getElementById('btnAnalizar');

    const loadingState = document.getElementById('loadingState');
    const errorBox = document.getElementById('errorBox');
    const resultEmpty = document.getElementById('resultEmpty');
    const resultBox = document.getElementById('resultBox');

    const resultLabel = document.getElementById('resultLabel');
    const resultModel = document.getElementById('resultModel');
    const confidenceText = document.getElementById('confidenceText');
    const confidenceBar = document.getElementById('confidenceBar');
    const cleanText = document.getElementById('cleanText');
    const noteText = document.getElementById('noteText');
    const compareBox = document.getElementById('compareBox');

    const API_URL = '/api/clasificar-texto';

    const escapeHtml = (value) => {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const setLoading = (isLoading) => {
        loadingState.classList.toggle('hidden', !isLoading);
        submitBtn.disabled = isLoading;
        submitBtn.textContent = isLoading ? 'Analizando...' : 'Analizar texto';
    };

    const showError = (message = '') => {
        if (!message) {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            return;
        }

        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    };

    const resetResult = () => {
        resultEmpty.classList.remove('hidden');
        resultBox.classList.add('hidden');
        compareBox.classList.add('hidden');
        compareBox.innerHTML = '';
        resultLabel.textContent = '—';
        resultModel.textContent = '—';
        confidenceText.textContent = '0%';
        confidenceBar.style.width = '0%';
        cleanText.textContent = '—';
        noteText.textContent = '—';
    };

    const updateCounter = () => {
        const length = textarea.value.length;
        counter.textContent = `${length} caracteres`;
    };

    const renderSingleResult = (payload) => {
        const pred = payload?.prediccion || payload?.data?.prediccion || payload?.prediccion;
        if (!pred) return;

        const clasificacion = pred.clasificacion ?? '—';
        const confianza = typeof pred.confianza === 'number' ? pred.confianza : 0;
        const porcentaje = Math.max(0, Math.min(100, Math.round(confianza * 100)));

        resultLabel.textContent = clasificacion;
        resultModel.textContent = pred.modelo ? `Modelo: ${pred.modelo}` : 'Resultado';
        confidenceText.textContent = `${porcentaje}%`;
        confidenceBar.style.width = `${porcentaje}%`;
        cleanText.textContent = payload.texto_limpio || payload?.data?.texto_limpio || '—';
        noteText.textContent = porcentaje >= 90
            ? 'La predicción presenta una confianza alta.'
            : 'La predicción presenta una confianza moderada.';

        resultEmpty.classList.add('hidden');
        resultBox.classList.remove('hidden');
        compareBox.classList.add('hidden');
        compareBox.innerHTML = '';
    };

    const renderComparison = (payload) => {
        const comparison = payload?.comparacion;
        if (!comparison) return;

        const svm = comparison.svm;
        const nb = comparison.naive_bayes;
        const recommended = comparison.modelo_recomendado;

        const svmConf = Math.round((svm?.confianza || 0) * 100);
        const nbConf = Math.round((nb?.confianza || 0) * 100);

        resultLabel.textContent = 'Comparación';
        resultModel.textContent = 'SVM vs Naive Bayes';
        confidenceText.textContent = `${Math.max(svmConf, nbConf)}%`;
        confidenceBar.style.width = `${Math.max(svmConf, nbConf)}%`;
        cleanText.textContent = payload.texto_limpio || payload?.data?.texto_limpio || '—';
        noteText.textContent = `Modelo recomendado: ${recommended}`;

        compareBox.classList.remove('hidden');
        compareBox.innerHTML = `
            <div class="compare-card">
                <strong>SVM</strong>
                <span>Clasificación: ${escapeHtml(svm?.clasificacion ?? '—')}</span><br>
                <span>Confianza: ${svmConf}%</span>
            </div>
            <div class="compare-card">
                <strong>Naive Bayes</strong>
                <span>Clasificación: ${escapeHtml(nb?.clasificacion ?? '—')}</span><br>
                <span>Confianza: ${nbConf}%</span>
            </div>
            <div class="compare-recommended">
                Modelo recomendado: <strong>${escapeHtml(recommended || '—')}</strong>
            </div>
        `;

        resultEmpty.classList.add('hidden');
        resultBox.classList.remove('hidden');
    };

    const readFileAsText = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(new Error('No se pudo leer el archivo.'));

            reader.readAsText(file, 'UTF-8');
        });
    };

    textarea.addEventListener('input', () => {
        updateCounter();
        if (textarea.value.trim() !== '') {
            showError('');
        }
    });

    fileInput.addEventListener('change', async () => {
        const file = fileInput.files?.[0];
        if (!file) return;

        try {
            const text = await readFileAsText(file);
            textarea.value = text;
            updateCounter();
            showError('');
        } catch (error) {
            showError(error.message || 'No se pudo cargar el archivo.');
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        showError('');

        const texto = textarea.value.trim();
        const modelo = modelSelect.value;

        if (!texto) {
            showError('Debes escribir o cargar un texto antes de analizar.');
            return;
        }

        setLoading(true);
        resetResult();

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ texto, modelo }),
            });

            const data = await response.json().catch(() => null);

            if (!response.ok) {
                const message = data?.message || data?.detail || 'No se pudo procesar la solicitud.';
                throw new Error(message);
            }

            if (modelo === 'comparar') {
                renderComparison(data);
            } else {
                renderSingleResult(data);
            }
        } catch (error) {
            resetResult();
            showError(error.message || 'Ocurrió un error inesperado al consultar la API.');
        } finally {
            setLoading(false);
        }
    });

    updateCounter();
    resetResult();
});