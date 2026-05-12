<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detector de texto IA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <main class="app-container">
            <section class="hero-card">
                <div class="hero-badge">Proyecto IA</div>
                <h1>Detector de texto humano vs IA</h1>
                <p>
                    Ingresa un texto, selecciona el modelo y revisa la predicción con su nivel de confianza.
                </p>
            </section>

            <section class="content-grid">
                <article class="card panel">
                    <div class="panel-header">
                        <h2>Análisis de texto</h2>
                        <span class="panel-subtitle">Entrada, validación y envío a la API</span>
                    </div>

                    <form id="analysis-form" class="analysis-form" autocomplete="off">
                        <div class="field">
                            <label for="texto">Texto a analizar</label>
                            <textarea
                                id="texto"
                                name="texto"
                                rows="10"
                                placeholder="Pega aquí el texto que deseas clasificar..."
                            ></textarea>
                        </div>

                        <div class="controls-row">
                            <div class="field compact">
                                <label for="modelo">Modelo</label>
                                <select id="modelo" name="modelo">
                                    <option value="svm">SVM (principal)</option>
                                    <option value="naive_bayes">Naive Bayes (baseline)</option>
                                    <option value="comparar">Comparar ambos</option>
                                </select>
                            </div>

                            <div class="field compact upload-field">
                                <label for="archivo">Cargar archivo .txt</label>
                                <input id="archivo" name="archivo" type="file" accept=".txt,text/plain">
                            </div>
                        </div>

                        <div class="meta-row">
                            <span id="contador">0 caracteres</span>
                            <button type="submit" id="btnAnalizar" class="primary-btn">
                                Analizar texto
                            </button>
                        </div>
                    </form>
                </article>

                <aside class="card result-card">
                    <div class="panel-header">
                        <h2>Resultado</h2>
                        <span class="panel-subtitle">La predicción aparecerá aquí</span>
                    </div>

                    <div id="loadingState" class="loading-state hidden">
                        <div class="spinner"></div>
                        <p>Procesando texto...</p>
                    </div>

                    <div id="errorBox" class="alert alert-error hidden"></div>

                    <div id="resultEmpty" class="result-empty">
                        <p>El resultado se mostrará después del análisis.</p>
                    </div>

                    <div id="resultBox" class="result-box hidden">
                        <div class="result-top">
                            <span id="resultLabel" class="result-label">—</span>
                            <span id="resultModel" class="result-chip">—</span>
                        </div>

                        <div class="confidence-block">
                            <div class="confidence-row">
                                <span>Confianza</span>
                                <strong id="confidenceText">0%</strong>
                            </div>
                            <div class="progress">
                                <div id="confidenceBar" class="progress-bar"></div>
                            </div>
                        </div>

                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-title">Texto limpio</span>
                                <p id="cleanText">—</p>
                            </div>
                            <div class="detail-item">
                                <span class="detail-title">Observación</span>
                                <p id="noteText">—</p>
                            </div>
                        </div>

                        <div id="compareBox" class="compare-box hidden"></div>
                    </div>
                </aside>
            </section>
        </main>
    </div>
</body>
</html>