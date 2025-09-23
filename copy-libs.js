/**
 * copy-libs.js
 * ----------------
 * Copia automáticamente librerías desde node_modules a public/assets
 * - Archivos principales minificados (.min.css, .min.js)
 * - Archivos .map
 * - Carpetas de fuentes: fonts, font, webfonts
 *
 * Ignora automáticamente:
 * - Archivos con sufijos/prefijos no deseados (-rtl, -esm, -bs4, -bs5, -lite)
 * - Archivos de idioma (summernote-ar-AR.min.js, etc.)
 * - Archivos dentro de /src
 *
 * Las fuentes que estén en "font" dentro de Summernote se copian dentro de assets/css/font/
 * y el CSS se ajusta automáticamente para que cargue correctamente.
 */

const fs = require('fs');
const path = require('path');
const pkg = require('./package.json');

const nodeModulesPath = path.join(__dirname, 'node_modules');
const assetsPath = path.join(__dirname, 'public', 'assets');

// Prefijos/sufijos a excluir
const EXCLUDE_PREFIXES = [
    'rtl', 'esm', 'slim', 'bs4', 'bs5', 'lite',
    'grid', 'utilities', 'reboot'
];

function copyFileSafe(src, dest) {
    const destDir = path.dirname(dest);
    if (!fs.existsSync(destDir)) fs.mkdirSync(destDir, {recursive: true});
    if (fs.existsSync(src)) fs.copyFileSync(src, dest);
}

function copyFolderSync(src, dest) {
    if (!fs.existsSync(src)) return;
    if (!fs.existsSync(dest)) fs.mkdirSync(dest, {recursive: true});

    fs.readdirSync(src).forEach(file => {
        const srcFile = path.join(src, file);
        const destFile = path.join(dest, file);

        if (fs.statSync(srcFile).isDirectory()) {
            copyFolderSync(srcFile, destFile);
        } else {
            fs.copyFileSync(srcFile, destFile);
        }
    });
}

function isMainFile(file) {
    const minMatch = /^([^.]+(\.[^.]+)*)\.min\.(css|js)(\.map)?$/i.test(file);
    if (!minMatch) return false;

    const lower = file.toLowerCase();
    if (EXCLUDE_PREFIXES.some(p => lower.includes(`-${p}.`) || lower.includes(`.${p}.`))) return false;

    const namePart = file.split('.min')[0];
    if (namePart.includes('-') && !namePart.includes('bundle') && !namePart.includes('stepper')) return false;

    return true;
}

function copyDistRecursively(srcFolder, relativeBase = '') {
    if (!fs.existsSync(srcFolder)) return;

    fs.readdirSync(srcFolder).forEach(file => {
        const srcPath = path.join(srcFolder, file);
        const stats = fs.statSync(srcPath);

        if (stats.isDirectory()) {
            copyDistRecursively(srcPath, path.join(relativeBase, file));
        } else if (stats.isFile() && isMainFile(file)) {
            let destFolder;
            if (!relativeBase) {
                if (file.endsWith('.css')) destFolder = 'css';
                else if (file.endsWith('.js')) destFolder = 'js';
                else destFolder = '';
            } else {
                destFolder = relativeBase;
            }

            const destPath = path.join(assetsPath, destFolder, file);
            copyFileSafe(srcPath, destPath);
        }
    });
}

function adjustCssFontPaths(cssFile) {
    if (!fs.existsSync(cssFile)) return;
    let content = fs.readFileSync(cssFile, 'utf8');
    content = content.replace(/url\(\s*font\//g, 'url(./font/');
    fs.writeFileSync(cssFile, content, 'utf8');
}

function copyLib(lib) {
    const libPath = path.join(nodeModulesPath, lib);

    // 1️⃣ Copiar archivos minificados principales
    copyDistRecursively(path.join(libPath, 'dist'));

    // 2️⃣ Reglas de fuentes
    ['fonts', 'font', 'webfonts'].forEach(fd => {
        [path.join(libPath, 'dist', fd), path.join(libPath, fd)].forEach(src => {
            if (fs.existsSync(src) && fs.statSync(src).isDirectory()) {
                let dest;

                // 🌟 Regla especial: Summernote → font dentro de CSS
                if (lib === 'summernote' && fd === 'font') {
                    dest = path.join(assetsPath, 'css', 'font');
                    copyFolderSync(src, dest);

                    // Ajustar CSS para Summernote si CSS existe
                    const cssDistFolder = path.join(libPath, 'dist', 'css');
                    if (fs.existsSync(cssDistFolder) && fs.statSync(cssDistFolder).isDirectory()) {
                        const cssFiles = fs.readdirSync(cssDistFolder).filter(f => f.endsWith('.css'));
                        cssFiles.forEach(cssFile => {
                            const targetCss = path.join(assetsPath, 'css', cssFile);
                            if (fs.existsSync(targetCss)) {
                                adjustCssFontPaths(targetCss);
                            }
                        });
                    }
                    return; // ya copiado, no hacer más
                }

                // Carpeta webfonts → assets/webfonts
                if (fd === 'webfonts') {
                    dest = path.join(assetsPath, 'webfonts');
                    copyFolderSync(src, dest);
                    return;
                }

                // Carpeta fonts → assets/fonts
                if (fd === 'fonts') {
                    dest = path.join(assetsPath, 'fonts');
                    copyFolderSync(src, dest);
                    return;
                }
            }
        });
    });

    // 3️⃣ Archivos especiales declarados en package.json → assets
    if (pkg.assets && pkg.assets[lib]) {
        pkg.assets[lib].forEach(p => {
            const src = path.join(libPath, p);
            const dest = path.join(assetsPath, p.replace(/^dist\//, ''));
            if (fs.existsSync(src) && fs.statSync(src).isDirectory()) {
                copyFolderSync(src, dest);
            } else {
                copyFileSafe(src, dest);
            }
        });
    }
}

// Ejecutar para todas las dependencias
Object.keys(pkg.dependencies).forEach(lib => copyLib(lib));

console.log('📦 Copia de librerías completada.');
