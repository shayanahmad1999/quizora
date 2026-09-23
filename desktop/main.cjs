'use strict';
const { app, BrowserWindow, Menu, dialog, session } = require('electron');
const path = require('node:path');
const { validateServerUrl, isTrustedNavigation } = require('./url-policy.cjs');
const configuration = require('./config.json');
let mainWindow;
let server;
try { server = validateServerUrl(configuration.serverUrl); }
catch (error) { app.whenReady().then(() => { dialog.showErrorBox('Quizora configuration', error.message); app.quit(); }); }
const gotLock = app.requestSingleInstanceLock();
if (!gotLock) app.quit();
app.on('second-instance', () => { if (mainWindow) { if (mainWindow.isMinimized()) mainWindow.restore(); mainWindow.focus(); } });
async function connect() {
    if (!mainWindow || !server) return;
    try { await mainWindow.loadURL(server.href); }
    catch { if (!mainWindow.isDestroyed()) await mainWindow.loadFile(path.join(__dirname, 'offline.html')); }
}
function createWindow() {
    const workspaceSession = session.fromPartition('persist:quizora');
    workspaceSession.setPermissionRequestHandler((_webContents, _permission, callback) => callback(false));
    workspaceSession.setPermissionCheckHandler(() => false);
    mainWindow = new BrowserWindow({
        width: 1380, height: 920, minWidth: 800, minHeight: 620, title: 'Quizora',
        icon: path.join(__dirname, 'assets/icon.png'), show: false,
        webPreferences: { partition: 'persist:quizora', contextIsolation: true, nodeIntegration: false, sandbox: true,
            webSecurity: true, allowRunningInsecureContent: false, devTools: !app.isPackaged },
    });
    mainWindow.webContents.setWindowOpenHandler(() => ({ action: 'deny' }));
    const guard = (event, url) => { if (!isTrustedNavigation(url, server)) event.preventDefault(); };
    mainWindow.webContents.on('will-navigate', guard);
    mainWindow.webContents.on('will-redirect', guard);
    mainWindow.webContents.on('will-attach-webview', event => event.preventDefault());
    mainWindow.once('ready-to-show', () => mainWindow.show());
    mainWindow.on('closed', () => { mainWindow = null; });
    Menu.setApplicationMenu(Menu.buildFromTemplate([
        ...(process.platform === 'darwin' ? [{ role: 'appMenu' }] : []),
        { label: 'Workspace', submenu: [ { label: 'Reconnect to server', click: connect }, { type: 'separator' }, { role: 'quit' } ] },
        { role: 'editMenu' },
        { label: 'View', submenu: [{ role: 'resetZoom' }, { role: 'zoomIn' }, { role: 'zoomOut' }, { type: 'separator' }, { role: 'togglefullscreen' }] },
    ]));
    connect();
}
if (server && gotLock) {
    app.whenReady().then(createWindow);
    app.on('activate', () => { if (BrowserWindow.getAllWindows().length === 0) createWindow(); });
}
app.on('window-all-closed', () => { if (process.platform !== 'darwin') app.quit(); });
