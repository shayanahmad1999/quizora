# Quizora desktop client

This folder is an Electron client for the Laravel workspace. **It connects to a running Laravel server. It is not a self-contained offline version of the application.**

## Local use on Windows

Start the web application first in its own terminal:

```powershell
cd D:\my-project\quizora
php artisan serve
```

In another terminal:

```powershell
cd D:\my-project\quizora\desktop
npm install --save-dev --save-exact electron@latest electron-builder@latest
npm test
npm start
```

The package requires Node 22.12+ as a baseline; use a maintained Node release supported by the resolved Electron/build tooling. Respect any newer engine requirement reported by npm. The installation command resolves current stable packages and records exact installed versions; inspect the resulting versions and commit `package.json` plus `package-lock.json` after testing. Subsequent installs should use `npm ci`.

No npm dependencies, lockfile, desktop executable, or signed installer are preinstalled in the source archive. Dependency installation requires internet access. On first launch, some Electron versions may complete an additional binary download.

## Server URL

Edit `config.json` before launching or packaging:

```json
{
  "serverUrl": "https://quiz.example.com"
}
```

Replace the example with the real deployed HTTPS origin. The default `http://127.0.0.1:8000` is only for a local Laravel server. Only exact loopback hostnames/IP forms accepted by `url-policy.cjs` may use plain HTTP. Remote insecure HTTP, credential-bearing URLs, non-HTTP protocols, and navigation outside the configured origin are rejected.

When the server is unavailable, the client shows a connection page. After restoring the connection, use **Workspace > Reconnect to server**. No offline answer synchronization is performed. A failed save must be retried while the attempt deadline still permits it.

## Build Windows installer

From the `desktop` directory:

```powershell
npm run dist:win
```

This invokes the NSIS x64 build. The configured build output directory is `desktop/dist`. Installer generation has not been executed in the build environment and requires the resolved Electron/toolchain dependencies on your machine. Unsigned installers may trigger OS warnings; sign distributed software with your own trusted certificate and test it on a clean machine.

Linux AppImage and macOS DMG scripts are also present. Build/test on the target operating system; macOS signing/notarization requires the appropriate Apple credentials and tooling. The presence of a build configuration is not a claim that those platform packages have been produced or verified.

## Security boundary

The renderer has `nodeIntegration: false`, `contextIsolation: true`, `sandbox: true`, and web security enabled. There is no preload bridge and no privileged IPC API. New windows, webviews, and permission requests are denied. Navigation and redirects must remain on the configured origin. The app does not suppress TLS certificate failures.

Persistent cookies are stored in the Electron session partition. The Laravel server remains the authentication, permissions, validation, timer, and scoring authority. The wrapper does not grant admin rights, enforce exam invigilation, or prevent a learner from using another browser or device.

Eleven dependency-free URL-policy checks are available with:

```powershell
node --test tests/*.test.cjs
```

These tests validate URL handling, not native Electron rendering, sandbox implementation, installer execution, or server integration. Consult [Electron security guidance](https://www.electronjs.org/docs/latest/tutorial/security) when extending the client. Do not add a generic filesystem/command bridge to make new features easier.
