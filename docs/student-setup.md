# SignalGrid student setup

This guide is for the students using SignalGrid. It explains what the web application is, how to run it on a computer, how the campus record works, and how to publish it on Vercel.

SignalGrid is a campus Wi-Fi research workspace. It stores one institution, its campus and buildings, then accepts surveys, measurements, access-point load samples, user feedback, insights, and reports. It is built with Laravel 13, PHP 8.4, and a Vite front end.

The application will not invent the data a survey team has to collect. Floors, access points, RSSI readings, speed tests, heatmaps, and feedback stay empty until a person enters them.

The published class site is [https://signalgrid-ui.vercel.app](https://signalgrid-ui.vercel.app). Sign in with the Super Admin email and password stored in the Vercel project. The first visit after the site has been idle can take longer, because the container starts again and checks the database.

## Two different setup screens

These are not the same thing.

**Account onboarding** is at `/onboarding`. A person who registers gets the Viewer role and is asked for a department, phone number, and intended use. That screen describes the person. It does not change the university.

**Campus setup** is at `/campus-setup`. It describes the institution, the campus, and the buildings. Super Admin, Network Admin, and Surveyor can open it. A Viewer cannot. Nothing on that form is stored until someone presses the save button.

`Campus setup` can be opened again later. Use `?pack=aaua` to prefill the published AAUA record, or `?blank=1` to start from an empty form. Prefill is not a save.

## What is already known about AAUA

The first run of `php artisan signalgrid:ensure-workspace` stores only published reference facts:

| Item | Value |
| --- | --- |
| Institution | Adekunle Ajasin University, Akungba |
| Slug | `aaua-akungba` |
| Website | https://www.aaua.edu.ng |
| Motto | For Learning and Service |
| Campus | Akungba Campus, code `AAUA` |
| Address | P.M.B. 001, Akungba-Akoko, Ondo State, Nigeria |
| Coordinate | 7.479234 N, 5.748411 E |
| Buildings | Senate Building (`SENATE`), University Library (`LIBRARY`), Postgraduate School (`PG`) |

The same postal box is also listed as P.M.B. 01. The coordinate is the published campus point, not a photo taken at the gate. The Main Gate is a landmark, not a survey building. Faculties, centres, and library divisions are academic units, not buildings and not floors. No floor count is published, so the buildings start with no floors.

Running the workspace command again does not put the AAUA name back if a student has changed the institution, and it does not create a second campus record. `php artisan db:seed` is different: it reapplies the AAUA record on every run. After the first setup, use `signalgrid:ensure-workspace`.

## Roles

A new registration becomes a Viewer. A Super Admin changes roles under **Administration**.

| Role | Can do |
| --- | --- |
| Super Admin | Everything, including user roles and the audit log |
| Network Admin | Everything except user management. Can open Validation |
| Surveyor | Locations, campus setup, surveys, measurements, CSV import, and heatmaps |
| Analyst | Review insights and generate reports |
| Viewer | Open the dashboard and the read-only pages |

Most sidebar links are visible to every signed-in person. The server still refuses an action the role is not allowed to perform. A Viewer can open Reports and see a refusal when trying to generate one. Campus setup, Validation, and Administration are hidden when the role cannot use them.

Public feedback does not need an account. The form is at `/feedback`.

## Pages

| Page | Address | What you do there |
| --- | --- | --- |
| Overview | `/dashboard` | Read scores. Empty numbers mean no measurements are stored yet |
| Locations | `/locations` | Review the campus tree and add a floor, area, or floor plan when you have one |
| Campus setup | `/campus-setup` | Confirm or replace the institution, campus, and buildings |
| RF survey studio | `/surveys` | Create a survey on a floor. Leave the floor plan blank until you want a heat map |
| Measurements | `/measurements` | Review stored readings |
| AP load analysis | `/access-points` | Record an access point and its load samples |
| Explainable insights | `/insights` | Generate and review rule-based findings |
| User feedback | `/admin/feedback` | Read feedback left at `/feedback` |
| Comparisons | `/comparisons` | Compare two surveys that contain real measurements |
| Reports | `/reports` | Build a report from stored surveys |
| Project alignment | `/project-alignment` | Read the project aim, objectives, and scope |
| Settings | `/settings` | Workspace links, including a return to campus setup |
| Validation | `/validation` | Holdout checks and the acceptance audit |
| Administration | `/admin` | Change a user's role and status |

There is no separate time-series feed for the overview chart. The chart stays empty until measurements exist. Do not type sample RSSI or speed values to make it look finished.

Insight review accepts only these statuses: `reviewed`, `investigating`, `resolved`, `dismissed`.

## Run it on your computer

Install PHP 8.4 or newer, Composer 2, and Node.js 22. PHP needs the `pdo_sqlite` extension for the default local database. Add `pdo_pgsql` only if you choose PostgreSQL. `intl`, `zip`, `gd`, and `bcmath` are required for the published container and are useful locally too.

Open PowerShell in the project folder. PowerShell 5.1 does not accept `&&`. Run one command at a time, or separate commands with `;`.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Force database\database.sqlite | Out-Null
php artisan migrate --force
```

Open `.env` and set the first administrator before the next command. Leave the password out of chat, screenshots, and git.

```text
SIGNALGRID_ADMIN_NAME="Your Name"
SIGNALGRID_ADMIN_EMAIL="you@example.com"
SIGNALGRID_ADMIN_PASSWORD="a long password you keep private"
```

```powershell
php artisan signalgrid:ensure-workspace
npm install
npm run build
php artisan serve
```

Open http://127.0.0.1:8000 and sign in with that email and password. The site redirects `/` to `/dashboard`.

`composer install` can also prepare a missing `.env`, generate the key, migrate, and build the front end through Composer's `setup` script. You still run `signalgrid:ensure-workspace` yourself so the campus record, roles, and research rules exist.

While you are changing the interface, use `npm run dev` in a second terminal. That file `public/hot` points the pages at the Vite dev server. Stop the dev server and run `npm run build` before you share the folder. Never commit or deploy `public/hot`.

The default local database is the SQLite file `database/database.sqlite`. To use PostgreSQL locally instead, set `DB_CONNECTION=pgsql` and `DB_URL` in `.env`, create the database, then run `php artisan migrate --force` and `php artisan signalgrid:ensure-workspace` again.

Login attempts are limited to five per minute. If a test account gets locked during practice, wait one minute, or run `php artisan cache:clear` on your own computer.

## First session for the class

1. Sign in as the Super Admin created above.
2. Open **Campus setup** and read the institution name, address, coordinate, and three buildings. Save only when the form is the campus you mean to keep.
3. Open **Locations**. Add a floor, and a survey area if you need one. A floor plan is optional.
4. Open **AP load analysis**. Add an access point on that floor and record a sample. A floor plan is not required.
5. In **RF survey studio**, create a survey and leave Floor plan on “No floor plan”. In the survey workspace, enter the reading and save it. You can also import a CSV.
6. Download the CSV template at `/measurement-import-template.csv`. Leave `x_normalized` and `y_normalized` blank when the survey has no floor plan. Fill those columns only for a survey that has a floor plan. The import reads the file during the same request.
7. Upload a floor-plan image only when you have one and want a heat map. PNG, JPG, WEBP, GIF, and PDF are accepted, up to 20 MB. On the published site the image is stored in PostgreSQL, so it remains after the container sleeps. Calibrate an image with a measured distance, draw the usable boundary, then attach that plan to a survey.
8. Generate a heat map only for a survey that has a floor plan and enough real points on that plan.
9. Ask classmates to submit `/feedback` from phones on the network you are studying.
10. An Analyst generates insights and a report. A Super Admin assigns those roles in **Administration**.

Buildings that already have a floor plan, a survey, an access point, or feedback are kept when campus setup is saved again. A building with none of those can be retired if the new form omits it.

## Publish on Vercel

Vercel runs this project as one container, not as PHP on a serverless function. The container image is `Dockerfile.vercel`. It builds the front end, installs PHP 8.4 with the PostgreSQL driver, and starts FrankenPHP. `vercel.json` sends every path to that container.

You need:

- a Vercel account and the Vercel CLI (`npm install -g vercel`, then `vercel login`)
- a hosted PostgreSQL database, such as [Neon](https://neon.tech)
- an application key from your own computer

SQLite inside the container is not acceptable for the published site. The container's disk is erased when Vercel stops the idle instance. PostgreSQL is the copy that remains.

### 1. Create the database

Create a PostgreSQL database and copy its connection string. The string must require SSL. This shape is the one Laravel reads as `DB_URL`:

```text
postgresql://USER:PASSWORD@HOST/DATABASE?sslmode=require
```

In Neon’s connection box, choose the direct string. Its hostname does not contain `-pooler`. The pooled host breaks the migrations that run when the site starts.

Keep that string in the Vercel project settings. Do not put it in `vercel.json`, `Dockerfile.vercel`, or git.

### 2. Create the application key

On your computer, in the project folder:

```powershell
php artisan key:generate --show
```

Copy the `base64:...` value into Vercel as `APP_KEY`. Do not commit it.

### 3. Set the Vercel environment

In the Vercel project, set:

| Name | Value |
| --- | --- |
| `APP_NAME` | `SignalGrid` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | the key from the command above |
| `APP_URL` | the `https://` address Vercel gives the project |
| `LOG_CHANNEL` | `stderr` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | the PostgreSQL string, including `sslmode=require` |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `FILESYSTEM_DISK` | `local` |
| `AWS_ACCESS_KEY_ID` | object-storage key, or leave unset |
| `AWS_SECRET_ACCESS_KEY` | object-storage secret, or leave unset |
| `AWS_DEFAULT_REGION` | the bucket region, such as `us-east-1` |
| `AWS_BUCKET` | the bucket name, or leave unset |
| `AWS_ENDPOINT` | set only for Cloudflare R2 or another S3-compatible store |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` when that store needs path-style URLs |
| `SIGNALGRID_ADMIN_NAME` | the first administrator's name |
| `SIGNALGRID_ADMIN_EMAIL` | the first administrator's email |
| `SIGNALGRID_ADMIN_PASSWORD` | a private password |

The image already sets `QUEUE_CONNECTION=sync`. Leave it on `sync` in Vercel as well. There is no queue worker, and a `database` queue would store jobs that never run. Heatmaps, imports, and reports run inside the web request.

The container refuses to start unless `APP_KEY` is set and the database is PostgreSQL through `DB_URL`. That check is in `docker/entrypoint.sh`.

Mark `APP_KEY`, `DB_URL`, and `SIGNALGRID_ADMIN_PASSWORD` as sensitive.

`APP_URL` can be updated after the first deploy, once Vercel prints the real address. Use the production address for a production deploy.

### 4. Deploy

From the project folder, after `vercel login`:

```powershell
vercel deploy
```

That creates a preview. When the preview signs in and `/up` returns a success page, publish it:

```powershell
vercel deploy --prod
```

The CLI prints the URL. This repository does not contain a production address until that command succeeds.

On every cold start the container runs migrations and `signalgrid:ensure-workspace`. Migrations are safe to repeat. The workspace command adds missing roles, thresholds, and insight rules. It creates the AAUA record only when the database has no institution, and it creates the Super Admin only when that email does not exist yet. Changing `SIGNALGRID_ADMIN_PASSWORD` later does not reset an account that is already stored. Change that password from the database or by replacing the user on purpose.

A production instance sleeps after about five idle minutes. A preview sleeps after about thirty seconds. The next visit starts the container again, so the first page can be slow.

### 5. Check the published site

- `/up` answers when the application process is up.
- `/health` answers when the application can reach PostgreSQL. A database problem returns HTTP 503.
- `/login` accepts the administrator email.
- `/register` creates a Viewer and continues to `/onboarding`.

## What survives on Vercel

These stay in PostgreSQL when the container stops:

- accounts, roles, and sessions
- the institution, campus, buildings, floors, and survey structure
- measurements, access points, load samples, and feedback
- insight rows, comparison rows, and report rows
- the project profile

The original CSV from an import, and generated report PDF and CSV files, stay only when object storage is configured. Set `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` and `AWS_BUCKET` in the Vercel project. Do not put the key or secret in git. For Cloudflare R2, also set `AWS_ENDPOINT` and `AWS_USE_PATH_STYLE_ENDPOINT=true`.

Leave `FILESYSTEM_DISK=local`. SignalGrid uses the `s3` disk for those files when both the bucket and the access key are set. Without them, import originals and generated report files are written to the container disk and disappear when the instance stops. Floor-plan images are kept in PostgreSQL in that case, so the picture remains after the container sleeps. The admin health check reports the storage gap for the other files until the bucket and key are set.

Measurement rows, report rows and the stored file path remain in PostgreSQL either way. A CSV import still keeps the accepted measurement rows.

## Do not commit secrets

`.env` stays on your computer. It is listed in `.gitignore`. The example file `.env.example` has empty administrator fields and commented production settings on purpose.

Do not paste `APP_KEY`, `DB_URL`, or a password into `vercel.json`, the Dockerfile, the documentation, or a screenshot. `vercel.json` only tells Vercel to build `Dockerfile.vercel`.

## If something fails

| What you see | What to check |
| --- | --- |
| Container logs say `APP_KEY` is missing | Set `APP_KEY` in Vercel, then redeploy |
| Logs say `DB_CONNECTION=pgsql` and `DB_URL` are required | Set both. Include `sslmode=require` in `DB_URL` |
| `/health` returns 503 | The database string, the SSL mode, or the database host |
| Login says too many attempts | Wait one minute. Locally, `php artisan cache:clear` resets the counter |
| Pages have no CSS on your computer | Run `npm run build`, or keep `npm run dev` running. Delete `public/hot` if the dev server is stopped |
| Campus name changed back to AAUA | `php artisan db:seed` was run again. Use `signalgrid:ensure-workspace` instead |
| Administrator password did not change | The email already existed. The setup command does not overwrite that password |
| Floor plan or report download is missing on Vercel | The file was on the container disk. Upload the plan again, or generate the report again. The database rows are still there |
| A button returns **403** | The signed-in role cannot perform that action. A Super Admin can change the role |

## Command reference

```powershell
php artisan migrate --force
php artisan signalgrid:ensure-workspace
php artisan serve
php artisan test --filter=EnsureWorkspace
php artisan key:generate --show
vercel deploy
vercel deploy --prod
```

`signalgrid:ensure-workspace` is the command to teach. It prepares roles, the campus record when none exists, system thresholds, access-point thresholds, insight rules `RF-001`, `PERF-001`, and `UX-001`, and one project profile when none exists.
