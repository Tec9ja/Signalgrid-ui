# SignalGrid

SignalGrid is the campus Wi-Fi research workspace for this project. Students sign in, keep one campus record, collect real measurements, and review coverage, access-point load, user feedback, and reports.

The application does not invent floors, access points, signal readings, or survey results. Those stay empty until someone records them.

## Start here

The student guide is [docs/student-setup.md](docs/student-setup.md). It covers:

- what each role can do
- how to run the site on a Windows computer
- the difference between account onboarding and campus setup
- how to publish the site on Vercel
- which files survive a restart, and which ones do not

## Local commands

From PowerShell, in this folder, after PHP 8.4, Composer, and Node.js are installed:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite | Out-Null
php artisan migrate --force
php artisan signalgrid:ensure-workspace
npm install
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`. Use `signalgrid:ensure-workspace` rather than `php artisan db:seed` after the first setup. The plain database seeder reapplies the AAUA public record every time it runs.

`npm run dev` is only for local design work. Do not deploy a `public/hot` file.
