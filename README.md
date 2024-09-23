## Draivi Coding Challenge

### Requirements

- PHP 7+ (`php-sqlite3` package for fast testing, `php-gd` for Excel parser)
- Composer
- Node/NPM
- Chrome (installed with `puppeteer` package)

### Setup

Run the following commands to install required `npm` and `composer` packages:

```
npm install
```

```
composer install
```

After this duplicate `.env.example` file and name it to `.env`. Open `.env` file and add at least value for `CURRENCYLAYER_ACCESS_KEY` (get it from [https://currencylayer.com/](https://currencylayer.com/)). `NODE_BIN_PATH` is optional and can be left empty.

### Running

Run the scraper script, which downloads the latest Alko 
products to a local SQLite database, with the following command:

```
composer scrape
```

Then start the app server with the following command:

```
composer serve
```

...and then visit `http://localhost:8000` on a web browser.

### Demo

[https://draivi-coding-challenge.tunne.la/](https://draivi-coding-challenge.tunne.la/)

### Code

The most important code files can be found at:

- Scraper: [console/scrape.php](https://github.com/tunnela/draivi-coding-challenge/blob/master/console/scrape.php) + [scripts/scrape.js](https://github.com/tunnela/draivi-coding-challenge/blob/master/scripts/scrape.js)
- App: [public/index.php](https://github.com/tunnela/draivi-coding-challenge/blob/master/public/index.php)
- JS: [public/js/app.js](https://github.com/tunnela/draivi-coding-challenge/blob/master/public/js/app.js)
- CSS: [public/css/app.css](https://github.com/tunnela/draivi-coding-challenge/blob/master/public/css/app.css)
- HTML: [resources/views/app.html](https://github.com/tunnela/draivi-coding-challenge/blob/master/resources/views/app.html)
- Core: [src/*](https://github.com/tunnela/draivi-coding-challenge/blob/master/src)

