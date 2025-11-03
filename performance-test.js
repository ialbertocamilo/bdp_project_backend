/**
 * Script para analizar el tiempo de respuesta del endpoint de login
 * Realiza múltiples peticiones y proporciona estadísticas detalladas
 */

const http = require('http');
const https = require('https');

// Configuración
const CONFIG = {
    host: process.env.API_HOST || 'localhost',
    port: process.env.API_PORT || 8000,
    path: '/api/login',
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
};

// Datos de prueba para el login - ACTUALIZA ESTOS CON CREDENCIALES REALES
const LOGIN_DATA = {
    email: process.env.TEST_EMAIL || 'supervisor@example.com',
    password: process.env.TEST_PASSWORD || 'password'
};

/**
 * Realiza una petición HTTP y mide el tiempo
 */
function makeLoginRequest() {
    return new Promise((resolve, reject) => {
        const startTime = process.hrtime.bigint();
        const postData = JSON.stringify(LOGIN_DATA);

        const options = {
            ...CONFIG,
            headers: {
                ...CONFIG.headers,
                'Content-Length': Buffer.byteLength(postData)
            }
        };

        const req = http.request(options, (res) => {
            let data = '';

            res.on('data', (chunk) => {
                data += chunk;
            });

            res.on('end', () => {
                const endTime = process.hrtime.bigint();
                const duration = Number(endTime - startTime) / 1_000_000; // Convertir a milisegundos

                resolve({
                    statusCode: res.statusCode,
                    duration: duration,
                    response: data.length,
                    timestamp: new Date().toISOString()
                });
            });
        });

        req.on('error', (error) => {
            reject(error);
        });

        req.write(postData);
        req.end();
    });
}

/**
 * Calcula estadísticas de un arreglo de números
 */
function calculateStats(durations) {
    if (durations.length === 0) return null;

    const sorted = [...durations].sort((a, b) => a - b);
    const sum = durations.reduce((a, b) => a + b, 0);
    const avg = sum / durations.length;

    return {
        count: durations.length,
        min: sorted[0],
        max: sorted[durations.length - 1],
        average: avg,
        median: sorted[Math.floor(sorted.length / 2)],
        p95: sorted[Math.ceil(sorted.length * 0.95) - 1],
        p99: sorted[Math.ceil(sorted.length * 0.99) - 1],
        standardDeviation: Math.sqrt(
            durations.reduce((sq, n) => sq + Math.pow(n - avg, 2), 0) / durations.length
        )
    };
}

/**
 * Ejecuta las pruebas de rendimiento
 */
async function runPerformanceTest(iterations = 10) {
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║      ANÁLISIS DE RENDIMIENTO - Endpoint de Login           ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    console.log(`Configuración:`);
    console.log(`  - URL: http://${CONFIG.host}:${CONFIG.port}${CONFIG.path}`);
    console.log(`  - Método: ${CONFIG.method}`);
    console.log(`  - Iteraciones: ${iterations}\n`);

    const results = {
        successful: [],
        failed: [],
        durations: []
    };

    console.log('Ejecutando pruebas...\n');

    for (let i = 1; i <= iterations; i++) {
        try {
            const result = await makeLoginRequest();
            results.successful.push(result);
            results.durations.push(result.duration);

            const status = result.statusCode === 202 || result.statusCode === 200 ? '✓' : '✗';
            console.log(`[${i}/${iterations}] ${status} ${result.statusCode} - ${result.duration.toFixed(2)}ms`);
        } catch (error) {
            results.failed.push({
                error: error.message,
                iteration: i,
                timestamp: new Date().toISOString()
            });
            console.log(`[${i}/${iterations}] ✗ ERROR - ${error.message}`);
        }
    }

    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║                    RESULTADOS                              ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    // Estadísticas
    const stats = calculateStats(results.durations);

    if (stats) {
        console.log('Estadísticas de Tiempo de Respuesta (en milisegundos):');
        console.log(`  - Peticiones exitosas: ${results.successful.length}/${iterations}`);
        console.log(`  - Peticiones fallidas: ${results.failed.length}/${iterations}`);
        console.log(`  - Mínimo: ${stats.min.toFixed(2)}ms`);
        console.log(`  - Máximo: ${stats.max.toFixed(2)}ms`);
        console.log(`  - Promedio: ${stats.average.toFixed(2)}ms`);
        console.log(`  - Mediana: ${stats.median.toFixed(2)}ms`);
        console.log(`  - Percentil 95: ${stats.p95.toFixed(2)}ms`);
        console.log(`  - Percentil 99: ${stats.p99.toFixed(2)}ms`);
        console.log(`  - Desviación estándar: ${stats.standardDeviation.toFixed(2)}ms\n`);
    }

    // Distribución por rangos
    if (results.durations.length > 0) {
        console.log('Distribución de tiempos de respuesta:');
        const ranges = [
            { name: '< 50ms', min: 0, max: 50 },
            { name: '50-100ms', min: 50, max: 100 },
            { name: '100-200ms', min: 100, max: 200 },
            { name: '200-500ms', min: 200, max: 500 },
            { name: '> 500ms', min: 500, max: Infinity }
        ];

        ranges.forEach(range => {
            const count = results.durations.filter(d => d >= range.min && d < range.max).length;
            const percentage = ((count / results.durations.length) * 100).toFixed(1);
            const bar = '█'.repeat(Math.floor(count / 2));
            console.log(`  ${range.name.padEnd(15)} │ ${bar.padEnd(25)} ${count} (${percentage}%)`);
        });
    }

    // Detalles de errores si los hay
    if (results.failed.length > 0) {
        console.log('\nErrores encontrados:');
        results.failed.forEach((error, index) => {
            console.log(`  [${index + 1}] ${error.error} (Iteración ${error.iteration})`);
        });
    }

    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║                Prueba finalizada                            ║');
    console.log('╚════════════════════════════════════════════════════════════╝');

    return results;
}

// Ejecutar la prueba
const iterations = process.argv[2] ? parseInt(process.argv[2]) : 10;
runPerformanceTest(iterations).catch(console.error);
