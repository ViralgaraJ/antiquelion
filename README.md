# AntiqueLion

Classifieds + auctions marketplace for antiques, built on **Osclass 8.3.1** with the
customised `starter` theme (`oc-content/themes/starter`) and the custom
`antique_bids` plugin.

## Run locally (macOS)

### Requirements

- [XAMPP for macOS](https://www.apachefriends.org/) (PHP 8.2 + MariaDB 10.4).
  On Apple Silicon, XAMPP is an Intel build and needs Rosetta:
  `softwareupdate --install-rosetta --agree-to-license`
- A local database called `antiquelion` (import a dump of the live database with
  phpMyAdmin or the `mysql` client if you don't have one yet).

### 1. Config

`config.php` is git-ignored. Copy the sample and set the local values:

```sh
cp config-sample.php config.php
```

```php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'antiquelion');
define('DB_TABLE_PREFIX', 'oc_');
define('REL_WEB_URL', '/');
define('WEB_PATH', 'http://localhost:8000/');
```

### 2. Start MySQL

XAMPP's data folder belongs to the `_mysql` user, so starting MySQL asks for your
Mac password:

```sh
sudo /Applications/XAMPP/xamppfiles/xampp startmysql   # stop: ... xampp stopmysql
```

The XAMPP manager app (**manager-osx → Manage Servers → MySQL Database → Start**)
does the same thing, but it is an old Intel app and may not open on newer macOS
versions.

The local MySQL login is `root` with an empty password (also used by phpMyAdmin).

### 3. Start the site

```sh
./dev-server.sh
```

Open <http://localhost:8000> (admin panel: <http://localhost:8000/oc-admin/>).

`dev-server.sh` runs PHP's built-in server with `dev-router.php`, which stands in for
the Apache `.htaccess` rewrite rules and blocks `oc-content/uploads/al-private/`.
The script uses XAMPP's PHP and falls back to `php` on your `PATH`.

### After importing a live database dump

The live dump includes the live SMTP settings and real user email addresses.
Right after every import, run the local SQL script that turns mail off
(kept in the git-ignored `_local_tools/sql/local-dev-disable-mail.sql`), or the
local site can send email to real users.

## Deploy

The live `public_html` is a git checkout of this repository (branch `main`).

1. Push to `main`.
2. cPanel → **Git Version Control** → Manage → **Pull or Deploy** → **Update from Remote**.

Every tracked file is publicly served, so:

- Never commit `config.php`, credentials, database dumps or debug helpers.
  Local-only tools go in the git-ignored `_local_tools/` folder.
- A changed tracked file on the server blocks "Update from Remote". The error cPanel
  shows ("could not contact the remote repository") doesn't point to the real cause.

## Licence

Osclass is licensed under the Apache License 2.0. See `LICENSE-2.0.txt`.
