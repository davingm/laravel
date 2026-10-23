# @davingm/laravel-cli

Global `artisan` launcher for projects using the `davingm/laravel` framework.

Install once on your development machine:

```bash
npm install --global @davingm/laravel-cli
```

Then run framework commands from the project directory or any directory beneath it:

```bash
artisan dev
artisan route:list
artisan make:dashboard
```

The launcher finds the nearest project root and forwards the command to that project's `.laravel/cli.js`. The framework runtime remains installed by Composer; this npm package only provides the global command entry point.

If the shell reports `artisan` as not found after installation, ensure npm's global executable directory is on `PATH`, then open a new terminal. Check the directory with:

```bash
npm prefix --global
```
