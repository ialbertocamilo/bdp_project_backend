/**
 * Prueba de rendimiento del endpoint de login con peticiones paralelas
 * Simula cómo se comporta la API cuando múltiples usuarios inician sesión
 */

const http = require('http');

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

const LOGIN_DATA = {
    email: process.env.TEST_EMAIL || 'admin@admin.com',
    password: process.env.TEST_PASSWORD || 'wrongpass'
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
                const duration = Number(endTime - startTime) / 1_000_000;

                resolve({
                    statusCode: res.statusCode,
                    duration: duration,
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
 * Calcula estadísticas
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
 * Ejecuta pruebas con peticiones secuenciales
 */
async function runSequentialTest(iterations = 10) {
    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║          PRUEBA SECUENCIAL - Login Endpoint                 ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    const results = {
        successful: [],
        failed: [],
        durations: []
    };

    console.log(`Ejecutando ${iterations} peticiones SECUENCIALES...\n`);

    for (let i = 1; i <= iterations; i++) {
        try {
            const result = await makeLoginRequest();
            results.successful.push(result);
            results.durations.push(result.duration);

            const status = result.statusCode === 202 || result.statusCode === 200 ? '✓' : '⚠';
            console.log(`[${i}/${iterations}] ${status} ${result.statusCode} - ${result.duration.toFixed(2)}ms`);
        } catch (error) {
            results.failed.push(error.message);
            console.log(`[${i}/${iterations}] ✗ ERROR - ${error.message}`);
        }
    }

    const stats = calculateStats(results.durations);
    if (stats) {
        console.log('\nEstadísticas (SECUENCIAL):');
        console.log(`  - Promedio: ${stats.average.toFixed(2)}ms`);
        console.log(`  - Mediana: ${stats.median.toFixed(2)}ms`);
        console.log(`  - Mín: ${stats.min.toFixed(2)}ms | Máx: ${stats.max.toFixed(2)}ms`);
        console.log(`  - P95: ${stats.p95.toFixed(2)}ms | P99: ${stats.p99.toFixed(2)}ms`);
    }

    return results;
}

/**
 * Ejecuta pruebas con peticiones paralelas
 */
async function runParallelTest(concurrent = 5, total = 20) {
    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║          PRUEBA PARALELA - Login Endpoint                   ║');
    console.log(`║          ${concurrent} peticiones concurrentes, ${total} total                      ║`);
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    const results = {
        successful: [],
        failed: [],
        durations: []
    };

    console.log(`Ejecutando ${total} peticiones con ${concurrent} concurrentes...\n`);

    for (let batch = 0; batch < Math.ceil(total / concurrent); batch++) {
        const batchSize = Math.min(concurrent, total - batch * concurrent);
        const promises = [];

        for (let i = 0; i < batchSize; i++) {
            const requestNum = batch * concurrent + i + 1;
            promises.push(
                makeLoginRequest()
                    .then(result => {
                        results.successful.push(result);
                        results.durations.push(result.duration);
                        const status = result.statusCode === 202 || result.statusCode === 200 ? '✓' : '⚠';
                        console.log(`[${requestNum}/${total}] ${status} ${result.statusCode} - ${result.duration.toFixed(2)}ms`);
                    })
                    .catch(error => {
                        results.failed.push(error.message);
                        console.log(`[${requestNum}/${total}] ✗ ERROR - ${error.message}`);
                    })
            );
        }

        await Promise.all(promises);
    }

    const stats = calculateStats(results.durations);
    if (stats) {
        console.log('\nEstadísticas (PARALELA):');
        console.log(`  - Promedio: ${stats.average.toFixed(2)}ms`);
        console.log(`  - Mediana: ${stats.median.toFixed(2)}ms`);
        console.log(`  - Mín: ${stats.min.toFixed(2)}ms | Máx: ${stats.max.toFixed(2)}ms`);
        console.log(`  - P95: ${stats.p95.toFixed(2)}ms | P99: ${stats.p99.toFixed(2)}ms`);
    }

    return results;
}

/**
 * Función principal
 */
async function main() {
    console.log('╔════════════════════════════════════════════════════════════╗');
    console.log('║      ANÁLISIS DE RENDIMIENTO SECUENCIAL VS PARALELO        ║');
    console.log('╚════════════════════════════════════════════════════════════╝');

    console.log(`\nURL: http://${CONFIG.host}:${CONFIG.port}${CONFIG.path}`);
    console.log(`Email: ${LOGIN_DATA.email}\n`);

    // Test secuencial
    const sequentialResults = await runSequentialTest(10);

    // Test paralelo
    const parallelResults = await runParallelTest(5, 20);

    // Comparación
    console.log('\n╔════════════════════════════════════════════════════════════╗');
    console.log('║                    COMPARACIÓN                              ║');
    console.log('╚════════════════════════════════════════════════════════════╝\n');

    const seqStats = calculateStats(sequentialResults.durations);
    const parStats = calculateStats(parallelResults.durations);

    console.log('Secuencial (10 requests):');
    console.log(`  Promedio: ${seqStats.average.toFixed(2)}ms`);
    console.log(`  Tiempo total estimado: ${(seqStats.average * 10 / 1000).toFixed(2)}s`);

    console.log('\nParalelo (5 concurrentes, 20 total):');
    console.log(`  Promedio: ${parStats.average.toFixed(2)}ms`);
    console.log(`  Tiempo total estimado: ${Math.max(...parallelResults.durations) / 1000}s`);

    console.log('\n⚠️  IMPORTANTE:');
    console.log('- Los tiempos paralelos SÍ serán más altos (carga en servidor)');
    console.log('- Bcrypt toma ~600-700ms por defecto');
    console.log('- Si la respuesta es más lenta, es por presión en recursos');
    console.log('- Esto es NORMAL en desarrollo sin cache/optimizaciones');
}

main().catch(console.error);
